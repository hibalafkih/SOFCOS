-- Base de données pour SOFCOS - Boutique Cosmétiques
-- À exécuter dans phpMyAdmin

CREATE DATABASE IF NOT EXISTS sofcos_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sofcos_db;

-- Table des catégories
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des produits
CREATE TABLE produits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(200) NOT NULL,
    description TEXT,
    prix DECIMAL(10,2) NOT NULL,
    prix_promo DECIMAL(10,2),
    categorie_id INT,
    stock INT DEFAULT 0,
    image VARCHAR(255),
    actif TINYINT(1) DEFAULT 1,
    date_ajout TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categorie_id) REFERENCES categories(id)
);

-- Table des clients
CREATE TABLE clients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    telephone VARCHAR(20),
    adresse TEXT,
    ville VARCHAR(100),
    code_postal VARCHAR(10),
    pays VARCHAR(100) DEFAULT 'Maroc',
    date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des commandes
CREATE TABLE commandes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT,
    montant_total DECIMAL(10,2) NOT NULL,
    statut ENUM('en_attente', 'confirme', 'expedie', 'livre', 'annule') DEFAULT 'en_attente',
    adresse_livraison TEXT,
    date_commande TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id)
);

-- Table des détails de commande
CREATE TABLE details_commande (
    id INT PRIMARY KEY AUTO_INCREMENT,
    commande_id INT,
    produit_id INT,
    quantite INT NOT NULL,
    prix_unitaire DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (commande_id) REFERENCES commandes(id),
    FOREIGN KEY (produit_id) REFERENCES produits(id)
);

-- Insertion de données de test

-- Catégories
INSERT INTO categories (nom, description, image) VALUES
('Soins du Visage', 'Crèmes, sérums et masques pour le visage', 'visage.jpg'),
('Maquillage', 'Rouge à lèvres, fond de teint, mascara', 'maquillage.jpg'),
('Soins du Corps', 'Lotions, huiles et gommages corporels', 'corps.jpg'),
('Parfums', 'Eaux de parfum et eaux de toilette', 'parfums.jpg'),
('Soins des Cheveux', 'Shampoings, après-shampoings et masques', 'cheveux.jpg');

-- Produits
INSERT INTO produits (nom, description, prix, prix_promo, categorie_id, stock, image) VALUES('Crème Hydratante Bio', 'Crème visage hydratante à l\'acide hyaluronique et aloe vera', 299.00, 249.00, 1, 50, 'creme_hydratante.jpg'),
('Sérum Anti-Âge', 'Sérum concentré en vitamine C et collagène', 450.00, NULL, 1, 30, 'serum_antiage.jpg'),
('Rouge à Lèvres Mat', 'Rouge à lèvres longue tenue, plusieurs teintes disponibles', 150.00, 120.00, 2, 100, 'rouge_levres.jpg'),
('Mascara Volume', 'Mascara effet volume intense, waterproof', 180.00, NULL, 2, 75, 'mascara.jpg'),
('Huile d\'Argan Bio', 'Huile pure d\'argan du Maroc pour corps et cheveux', 280.00, 250.00, 3, 40, 'huile_argan.jpg'),
('Gommage Corps Doux', 'Gommage exfoliant au sucre et huiles essentielles', 220.00, NULL, 3, 60, 'gommage.jpg'),
('Eau de Parfum Fleurie', 'Parfum aux notes florales et fruitées', 650.00, 599.00, 4, 25, 'parfum_fleurie.jpg'),
('Shampoing Réparateur', 'Shampoing sans sulfate pour cheveux abîmés', 195.00, NULL, 5, 80, 'shampoing.jpg'),
('Masque Capillaire Nourrissant', 'Masque riche en kératine et huile de coco', 240.00, 199.00, 5, 45, 'masque_cheveux.jpg'),
('Fond de Teint Naturel', 'Fond de teint léger, plusieurs teintes', 320.00, NULL, 2, 55, 'fond_teint.jpg');

-- Client de test (mot de passe: test123)
INSERT INTO clients (nom, prenom, email, mot_de_passe, telephone, adresse, ville, code_postal) VALUES
('Alami', 'Fatima', 'fatima@test.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0612345678', '123 Rue Mohammed V', 'Casablanca', '20000');

-- Ajouter les colonnes manquantes à la table commandes
ALTER TABLE commandes 
ADD COLUMN IF NOT EXISTS mode_paiement VARCHAR(50),
ADD COLUMN IF NOT EXISTS statut_paiement ENUM('en_attente', 'paye', 'rembourse', 'annule') DEFAULT 'en_attente',
ADD COLUMN IF NOT EXISTS date_paiement DATETIME;

-- Ajouter les colonnes manquantes à la table clients
ALTER TABLE clients 
ADD COLUMN IF NOT EXISTS date_derniere_connexion DATETIME;

-- Table des transactions de paiement
CREATE TABLE IF NOT EXISTS transactions_paiement (
    id INT PRIMARY KEY AUTO_INCREMENT,
    commande_id INT NOT NULL,
    montant DECIMAL(10,2) NOT NULL,
    mode_paiement VARCHAR(50) NOT NULL,
    statut ENUM('en_attente', 'reussie', 'echec', 'rembourse') DEFAULT 'en_attente',
    reference_transaction VARCHAR(255),
    date_transaction DATETIME DEFAULT CURRENT_TIMESTAMP,
    details_transaction TEXT,
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE
);

-- Table des historiques d'emails
CREATE TABLE IF NOT EXISTS historique_emails (
    id INT PRIMARY KEY AUTO_INCREMENT,
    destinataire VARCHAR(150) NOT NULL,
    sujet VARCHAR(255) NOT NULL,
    type_email ENUM('bienvenue', 'confirmation_commande', 'expedition', 'livraison', 'newsletter', 'autre') NOT NULL,
    commande_id INT,
    date_envoi DATETIME DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('envoye', 'echec') DEFAULT 'envoye',
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE SET NULL
);

-- Table des codes promo
CREATE TABLE IF NOT EXISTS codes_promo (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) UNIQUE NOT NULL,
    type ENUM('pourcentage', 'montant_fixe') NOT NULL,
    valeur DECIMAL(10,2) NOT NULL,
    montant_minimum DECIMAL(10,2) DEFAULT 0,
    date_debut DATE,
    date_fin DATE,
    nombre_utilisations_max INT,
    nombre_utilisations INT DEFAULT 0,
    actif BOOLEAN DEFAULT TRUE,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table d'utilisation des codes promo
CREATE TABLE IF NOT EXISTS utilisation_codes_promo (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code_promo_id INT NOT NULL,
    client_id INT NOT NULL,
    commande_id INT NOT NULL,
    montant_reduction DECIMAL(10,2) NOT NULL,
    date_utilisation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (code_promo_id) REFERENCES codes_promo(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE
);

-- Insérer quelques codes promo d'exemple
INSERT INTO codes_promo (code, type, valeur, montant_minimum, date_debut, date_fin, nombre_utilisations_max) VALUES
('BIENVENUE10', 'pourcentage', 10.00, 0, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 100),
('PROMO20', 'pourcentage', 20.00, 100, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 60 DAY), 50),
('SOLDES50', 'montant_fixe', 50.00, 200, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 15 DAY), 200);

-- Index pour améliorer les performances
CREATE INDEX idx_commandes_client ON commandes(client_id);
CREATE INDEX idx_commandes_statut ON commandes(statut);
CREATE INDEX idx_commandes_date ON commandes(date_commande);
CREATE INDEX idx_transactions_commande ON transactions_paiement(commande_id);
CREATE INDEX idx_emails_destinataire ON historique_emails(destinataire);
CREATE INDEX idx_codes_promo_code ON codes_promo(code);