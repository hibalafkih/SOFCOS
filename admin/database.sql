-- Table des administrateurs
CREATE TABLE IF NOT EXISTS administrateurs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin', 'gestionnaire') DEFAULT 'admin',
    actif BOOLEAN DEFAULT TRUE,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    derniere_connexion DATETIME
);
DROP TABLE IF EXISTS admins;

-- Compte administrateur par défaut
-- Email: admin@sofcos.com
-- Mot de passe: admin123
INSERT INTO administrateurs (nom, email, mot_de_passe, role) VALUES
('Administrateur Principal', 'admin@sofcos.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin'),
('Gestionnaire', 'gestionnaire@sofcos.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'gestionnaire');

-- Table des livraisons
CREATE TABLE IF NOT EXISTS livraisons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    commande_id INT NOT NULL,
    transporteur VARCHAR(100),
    numero_suivi VARCHAR(100),
    statut ENUM('en_preparation', 'expedie', 'en_transit', 'livre', 'echec') DEFAULT 'en_preparation',
    adresse_livraison TEXT NOT NULL,
    date_expedition DATETIME,
    date_livraison_estimee DATE,
    date_livraison_reelle DATETIME,
    notes TEXT,
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE
);

-- Ajout de colonnes manquantes dans la table commandes si nécessaire
ALTER TABLE commandes 
ADD COLUMN IF NOT EXISTS adresse_livraison TEXT,
ADD COLUMN IF NOT EXISTS ville VARCHAR(100),
ADD COLUMN IF NOT EXISTS code_postal VARCHAR(20),
ADD COLUMN IF NOT EXISTS notes_client TEXT;

-- Table des logs d'activité admin
CREATE TABLE IF NOT EXISTS logs_admin (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    date_action TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES administrateurs(id) ON DELETE CASCADE
);

-- Insertion de quelques livraisons d'exemple
INSERT INTO livraisons (commande_id, transporteur, numero_suivi, statut, adresse_livraison) VALUES
(1, 'Amana Express', 'AMN123456789', 'en_transit', '123 Rue Mohammed V, Casablanca'),
(2, 'CTM Messagerie', 'CTM987654321', 'expedie', '45 Avenue Hassan II, Rabat');