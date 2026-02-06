# 🚀 Configuration SOFCOS sur Google Cloud Platform
## Pour PUBLIC (Externe & Interne)

---

## 📊 EXTERNE vs INTERNE

| Aspect | Externe (Public) | Interne (Privé) |
|--------|------------------|-----------------|
| **Accès** | Internet public | Réseau d'entreprise/VPN |
| **URL** | `https://sofcos.com` | `https://sofcos-interne.company.com` |
| **SSL** | ✅ Certificat public | ✅ Certificat auto-signé ou privé |
| **Authentification** | Google OAuth public | Google OAuth + Single Sign-On |
| **Firewall** | Cloud Armor | Cloud VPN / Private Service Connection |
| **Coûts** | Basique | Coûts réseau supplémentaires |

---

## 🌐 OPTION 1: DÉPLOIEMENT EXTERNE (Public Internet)

### **ÉTAPE 1: Créer le Projet GCP**

```bash
# Via Google Cloud Console
1. Allez: https://console.cloud.google.com/
2. Cliquez: "NEW PROJECT"
3. Project name: "SOFCOS-Production"
4. Cliquez: "CREATE"
```

### **ÉTAPE 2: Activer les APIs**

Menu → "APIs & Services" → "Enabled APIs & services"

Cliquez "+ ENABLE APIS AND SERVICES" et activez:

```
✅ Cloud Run API
✅ Cloud SQL API
✅ Cloud Storage API
✅ Cloud Build API
✅ Artifact Registry API
✅ Container Registry API
```

### **ÉTAPE 3: Créer Cloud SQL (MySQL)**

Menu → "SQL" → "CREATE INSTANCE"

**Configuration:**
```
Database Engine:    MySQL 8.0
Instance ID:        sofcos-mysql
Password:           [Génération automatique]
Region:             europe-west1 (ou votre région)
Tier:               db-f1-micro (économique)
Storage:            10 GB
Backup:             Activé (automatique)
```

### **ÉTAPE 4: Créer l'Utilisateur MySQL**

Onglet "Users" → "CREATE USER ACCOUNT"

```
Username:   sofcos_user
Password:   [Générez fort: Min 16 caractères]
```

### **ÉTAPE 5: Créer la Base de Données**

Onglet "Databases" → "CREATE DATABASE"

```
Database name:  sofcos_db
Character set:  utf8mb4
Collation:      utf8mb4_unicode_ci
```

### **ÉTAPE 6: Configurer le Réseau Public**

Cloud SQL Instance → "Connections"

1. Onglet "Public IP"
2. Ajouter l'IP publique de votre app Cloud Run:
   ```
   0.0.0.0/0  (Pour tester partout)
   OU
   YOUR_CLOUD_RUN_IP/32  (Spécifique)
   ```

### **ÉTAPE 7: Créer le Dockerfile**

À la racine du projet, créez `Dockerfile`:

```dockerfile
FROM php:8.1-apache

# Installer les extensions PHP
RUN docker-php-ext-install pdo pdo_mysql
RUN docker-php-ext-install curl json

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copier le projet
COPY . /var/www/html/

# Installer les dépendances PHP
WORKDIR /var/www/html
RUN composer install --no-dev

# Configuration Apache
RUN a2enmod rewrite
RUN echo "ServerSignature Off\nServerTokens Prod" >> /etc/apache2/apache2.conf

# Permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 8080

# Adapter Apache pour utiliser le port 8080 (Cloud Run)
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf
RUN sed -i 's/:80>/:8080>/' /etc/apache2/sites-enabled/000-default.conf
```

### **ÉTAPE 8: Créer le fichier `.env.production`**

À la racine du projet:

```env
# Google OAuth (même projet GCP)
GOOGLE_CLIENT_ID=YOUR_CLIENT_ID.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=YOUR_CLIENT_SECRET

# Cloud SQL - À REMPLACER par vos valeurs
DB_HOST=CLOUD_SQL_PUBLIC_IP
DB_NAME=sofcos_db
DB_USER=sofcos_user
DB_PASS=YOUR_STRONG_PASSWORD

# Cloud Storage (pour les uploads)
UPLOAD_PATH=/var/www/html/uploads
MAX_UPLOAD_SIZE=10485760

# Email
SMTP_HOST=smtp.gmail.com
SMTP_USER=noreply@sofcos.com
SMTP_PASS=app_password_google

# App
APP_URL=https://sofcos-app-xxxxx.run.app
APP_ENV=production
LOG_LEVEL=error
```

### **ÉTAPE 9: Déployer sur Cloud Run**

#### **Option A: Via Google Cloud Console**

1. Menu → "Cloud Run"
2. Cliquez "+ CREATE SERVICE"
3. Remplissez:
   ```
   Service name:           sofcos-app
   Region:                 europe-west1
   Authentication:         Allow unauthenticated invocations ✅
   Require HTTPS:          ✅
   ```
4. Cliquez "CREATE"

#### **Option B: Via Cloud Build (Recommended)**

1. Menu → "Cloud Build" → "Repositories"
2. Connectez votre GitHub
3. Sélectionnez le repo SOFCOS
4. Configurez le trigger:
   ```
   Branch:     ^main$
   Build type: Dockerfile
   Dockerfile: ./Dockerfile
   ```

### **ÉTAPE 10: Configurer les Variables d'Environnement Cloud Run**

Cloud Run Service → "EDIT & DEPLOY NEW REVISION"

Onglet "Runtime settings" → Environment variables:

```
GOOGLE_CLIENT_ID=YOUR_CLIENT_ID
GOOGLE_CLIENT_SECRET=YOUR_SECRET
DB_HOST=CLOUD_SQL_PUBLIC_IP
DB_NAME=sofcos_db
DB_USER=sofcos_user
DB_PASS=YOUR_PASSWORD
APP_URL=https://sofcos-app-xxxxx.run.app
SMTP_USER=noreply@sofcos.com
SMTP_PASS=your_app_password
```

### **ÉTAPE 11: Mettre à jour Google OAuth**

Google Cloud Console → APIs & Services → Credentials

Modifiez OAuth Client ID:

```
Authorized redirect URIs:
  ✅ https://sofcos-app-xxxxx.run.app/google_callback.php
  ✅ https://votre-domaine.com/google_callback.php (si custom domain)
```

### **ÉTAPE 12: Configurer un Domaine Custom (optionnel)**

Cloud Run Service → "MANAGE CUSTOM DOMAINS"

1. Cliquez "+ ADD MAPPING"
2. Entrez: `sofcos.votre-domaine.com`
3. Pointez le DNS de votre domaine vers:
   ```
   ghs.googlehosted.com
   ```
4. Attendez la validation (15-30 min)

---

## 🔒 OPTION 2: DÉPLOIEMENT INTERNE (Privé/VPN)

### **Configuration pour Accès Interne Uniquement**

#### **ÉTAPE 1: Créer le VPC Network**

Menu → "VPC Network" → "VPCs"

1. Cliquez "+ CREATE VPC NETWORK"
2. Remplissez:
   ```
   Name:              sofcos-vpc
   Subnet name:       sofcos-subnet
   Region:            europe-west1
   IP range:          10.0.0.0/24
   ```

#### **ÉTAPE 2: Créer Cloud SQL en Privé**

Cloud SQL Instance → "EDIT"

1. Onglet "Connectivity"
2. **Désactivez** "Public IP"
3. **Activez** "Private IP"
4. Sélectionnez: `sofcos-vpc`
5. Cliquez "SAVE"

#### **ÉTAPE 3: Créer Cloud Run en Privé**

Cloud Run → "CREATE SERVICE"

1. Onglet "Networking"
2. Sélectionnez: `Require HTTPS` ✅
3. Ingress: `Internal`
4. VPC connector: Créez une nouvelle
   ```
   Name:           sofcos-connector
   Network:        sofcos-vpc
   Subnet:         sofcos-subnet
   Min instances:  2
   Max instances:  10
   ```

#### **ÉTAPE 4: Configurer le Firewall**

Menu → "VPC Network" → "Firewalls"

Créez deux règles:

**Règle 1: Entrée (Interne)**
```
Name:           allow-internal
Direction:      Ingress
Priority:       1000
Target tags:    sofcos-internal
Source IPs:     10.0.0.0/24
Protocol:       TCP 8080
Action:         Allow
```

**Règle 2: Cloud SQL Interne**
```
Name:           allow-cloudsql-internal
Direction:      Ingress
Priority:       1000
Source IPs:     10.0.0.0/24
Protocol:       TCP 3306
Action:         Allow
```

#### **ÉTAPE 5: Créer Identity-Aware Proxy (IAP)**

Menu → "Security" → "Identity-Aware Proxy"

1. Configure OAuth consent screen
2. Créez une OAuth app (Google service account)
3. Associez-la à Cloud Run
4. Seuls les utilisateurs authentifiés peuvent accéder

#### **ÉTAPE 6: VPN pour l'Accès Externe (optionnel)**

Si les employés accèdent de l'extérieur:

Menu → "Hybrid Connectivity" → "VPN"

1. Créez une connection "Cloud VPN"
2. Connectez le bureau/laptop de l'utilisateur
3. Ils accèdent via: `https://sofcos-interne.company.com`

---

## 📋 VARIABLES D'ENVIRONNEMENT POUR LES 2 OPTIONS

### **EXTERNE (Public)**

```env
GOOGLE_CLIENT_ID=PUBLIC_CLIENT_ID
GOOGLE_CLIENT_SECRET=PUBLIC_SECRET
DB_HOST=CLOUD_SQL_PUBLIC_IP
DB_NAME=sofcos_db
DB_USER=sofcos_user
DB_PASS=password
APP_URL=https://sofcos-app-xxxxx.run.app
APP_ENV=production
ALLOWED_ORIGINS=*
```

### **INTERNE (VPC Privé)**

```env
GOOGLE_CLIENT_ID=INTERNAL_CLIENT_ID
GOOGLE_CLIENT_SECRET=INTERNAL_SECRET
DB_HOST=10.0.0.2:3306  # Private IP
DB_NAME=sofcos_db
DB_USER=sofcos_user
DB_PASS=password
APP_URL=https://sofcos-interne.company.com
APP_ENV=production
ALLOWED_ORIGINS=https://sofcos-interne.company.com
SESSION_SECURITY=strict
```

---

## 💰 COÛTS ESTIMÉS (Google Cloud)

### **Configuration Externe (Public)**
```
Cloud Run:              $0-40/mois (pay-per-use)
Cloud SQL (f1-micro):   $10-15/mois
Backup & Storage:       $2-5/mois
TOTAL:                  ~$15-60/mois
```

### **Configuration Interne (VPC Privé)**
```
Cloud Run:              $0-40/mois
Cloud SQL:              $10-15/mois
VPC Connector:          $0.10/hour = ~$7.50/mois
Firewall (free):        $0
Cloud VPN:              $0.05/hour = ~$35/mois (optionnel)
TOTAL:                  ~$20-100/mois
```

---

## ✅ CHECKLIST DÉPLOIEMENT

### **Avant de déployer:**
- ✅ Projet GCP créé
- ✅ APIs activées (Run, SQL, Build)
- ✅ Cloud SQL créée et accessible
- ✅ Utilisateur MySQL créé
- ✅ Base `sofcos_db` importée
- ✅ Dockerfile configuré
- ✅ Variables d'env `.env.production` prêtes
- ✅ Repository GitHub connecté

### **Après déploiement:**
- ✅ Cloud Run service actif
- ✅ Google OAuth mis à jour
- ✅ SSL/HTTPS fonctionnant
- ✅ Base de données accessible
- ✅ Emails envoyés correctement
- ✅ Uploads fonctionnent
- ✅ Monitoring activé

---

## 🔗 COMMANDES UTILES

```bash
# Deploy via gcloud CLI
gcloud run deploy sofcos-app \
  --source . \
  --platform managed \
  --region europe-west1 \
  --allow-unauthenticated

# Voir les logs
gcloud run services describe sofcos-app --region europe-west1

# Connecter à Cloud SQL (local)
cloud_sql_proxy -instances=PROJECT:REGION:INSTANCE=tcp:3306

# Importer la base
mysql -h 127.0.0.1 -u sofcos_user -p sofcos_db < database.sql
```

---

## 🆘 TROUBLESHOOTING

| Erreur | Solution |
|--------|----------|
| `Error: permission denied` | Vérifier IAM roles et scopes |
| `Connection refused (DB)` | Vérifier Network firewall rules |
| `OAuth redirect mismatch` | Vérifier URL exacte dans Google Console |
| `502 Bad Gateway` | Vérifier logs Cloud Run |
| `Out of memory` | Augmenter Cloud Run memory (128MB → 512MB) |

---

## 📞 SUPPORT & DOCUMENTATION

- [Google Cloud Run Docs](https://cloud.google.com/run/docs)
- [Cloud SQL for MySQL](https://cloud.google.com/sql/docs/mysql)
- [VPC Networking](https://cloud.google.com/vpc/docs)
- [Identity-Aware Proxy](https://cloud.google.com/iap/docs)

---

**Quelle option choisir?**
- **Externe:** Pour une app publique accessible à tous
- **Interne:** Pour une app d'entreprise avec accès restreint

Laquelle vous voulez configurer? 🚀
