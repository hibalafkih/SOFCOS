# 📋 Configuration du Projet SOFCOS

## ⚙️ Setup Rapide

### 1️⃣ Configuration Google OAuth

#### Obtenir vos Credentials Google:

1. Allez sur [Google Cloud Console](https://console.cloud.google.com/)
2. **Créer un nouveau projet** (ou en sélectionner un existant)
3. **Activer l'API Google+:**
   - Allez dans "APIs & Services" → "APIs"
   - Recherchez "Google+" et activez-la
   - Recherchez "Gmail API" et activez-la (si vous utilisez l'authentification Gmail)

4. **Créer une identité OAuth:**
   - Allez dans "APIs & Services" → "OAuth consent screen"
   - Cliquez sur "Create" si besoin
   - Configurez:
     - **App name:** SOFCOS
     - **User type:** External
     - **Autorisations demandées:** 
       - `email`
       - `profile`
       - `openid`

5. **Créer des Credentials:**
   - "APIs & Services" → "Credentials"
   - Click "+ Create Credentials" → "OAuth client ID"
   - Choose **Web application**
   - **Authorized redirect URIs** (IMPORTANT!):
     ```
     http://localhost/SOFCOS/google_callback.php
     ```
   - Click "Create"

6. **Copier vos credentials:**
   - Vous verrez un popup avec:
     - **Client ID** → Copier
     - **Client Secret** → Copier
   - Cliquez sur "Download JSON" pour sauvegarder les détails

---

### 2️⃣ Configuration du Fichier `.env`

1. **Ouvrir le fichier `.env`** à la racine du projet
2. **Remplacer les valeurs:**

```env
# Google OAuth
GOOGLE_CLIENT_ID=YOUR_CLIENT_ID_XXXXXXXXXXXX.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-XXXXXXXXXXXXXXXXXXX

# Bases de données (XAMPP par défaut)
DB_HOST=localhost
DB_NAME=sofcos_db
DB_USER=root
DB_PASS=

# Email SMTP (pour Gmail)
SMTP_USER=your_email@gmail.com
SMTP_PASS=your_app_password_16_chars
```

**❗ IMPORTANT:** Ne jamais committer le `.env` dans Git!  
Le `.gitignore` le protège automatiquement.

---

### 3️⃣ Configuration Email (Optionnel)

Si vous utilisez Gmail:

1. **Activer "Less secure apps"** ou **Authentification 2FA:**
   - Allez à [https://myaccount.google.com/security](https://myaccount.google.com/security)

2. **Si vous avez 2FA activé:** Créer un "App Password"
   - Dans "Security" → "App passwords"
   - Select: Mail & Windows Computer
   - Copier les 16 caractères générés
   - Mettre dans `.env` sous `SMTP_PASS`

3. **Si vous n'avez pas 2FA:**
   - Autoriser les "Less secure apps"
   - Utiliser votre mot de passe Gmail normal

---

### 4️⃣ Tester la Configuration

#### Tester Google OAuth:
Allez sur: `http://localhost/SOFCOS/google_login.php`

#### Tester la Base de Données:
```bash
php -r "require 'config.php'; echo 'DB OK';"
```

#### Tester les Variables d'Environnement:
```bash
php -r "require 'config.php'; echo getenv('GOOGLE_CLIENT_ID');"
```

---

## 🔒 Sécurité

✅ **Fichier `.env`:** Ignoré par Git (dans `.gitignore`)  
✅ **Secrets Google:** Chargés depuis variables d'environnement  
✅ **Pas de hardcoding:** Credentials en dur = JAMAIS dans le code  

---

## 📁 Structure des fichiers de config

```
SOFCOS/
├── .env                 ← Credentials LOCAUX (ne pas committer) 
├── .env.example         ← Template (safe à committer)
├── config.php           ← Chargeur .env + configuration DB
├── google_callback.php  ← Utilise getenv('GOOGLE_CLIENT_ID')
└── google_login.php     ← Utilise getenv('GOOGLE_CLIENT_ID')
```

---

## 🆘 Troubleshooting

### ❌ "GOOGLE_CLIENT_ID manquant"
→ Vérifiez que le fichier `.env` existe et contient `GOOGLE_CLIENT_ID=...`

### ❌ "Erreur de connexion base de données"
→ Vérifiez dans `.env`:
```
DB_HOST=localhost
DB_USER=root
DB_NAME=sofcos_db
```

### ❌ "gmail.com: Authentication failed"
→ Vérifiez votre `SMTP_PASS` - devrait être l'app password (16 chars)

---

## 📞 Support

Pour plus d'infos:
- [Google OAuth Documentation](https://developers.google.com/identity/protocols/oauth2)
- [PHPMailer SMTP Config](https://github.com/PHPMailer/PHPMailer/wiki/Troubleshooting)

