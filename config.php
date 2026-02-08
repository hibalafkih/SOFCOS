<?php
// config.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// --- 1. FONCTION DE CHARGEMENT DU .ENV ---
// Cette méthode force la lecture du fichier .env même sur XAMPP Windows
function chargerEnv($chemin) {
    if (!file_exists($chemin)) {
        return; // Pas de fichier .env (ex: sur le serveur de prod)
    }
    
    $lignes = file($chemin, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lignes as $ligne) {
        if (strpos(trim($ligne), '#') === 0) continue; // Ignore les commentaires
        
        if (strpos($ligne, '=') !== false) {
            list($cle, $valeur) = explode('=', $ligne, 2);
            $cle = trim($cle);
            $valeur = trim($valeur);
            // Enlève les guillemets simples ou doubles
            $valeur = trim($valeur, "'\""); 
            
            // Définit la constante ET putenv (pour getenv())
            if (!defined($cle)) {
                define($cle, $valeur);
            }
            putenv("$cle=$valeur"); // Important pour getenv()
        }
    }
}

// Charger les variables depuis le fichier .env local
chargerEnv(__DIR__ . '/.env');

// --- 2. VÉRIFICATION DE SÉCURITÉ ---
// Si les constantes ne sont pas définies (ex: fichier .env manquant), on met des valeurs vides pour éviter les erreurs fatales
if (!defined('GOOGLE_CLIENT_ID')) define('GOOGLE_CLIENT_ID', '');
if (!defined('GOOGLE_CLIENT_SECRET')) define('GOOGLE_CLIENT_SECRET', '');
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'sofcos_db');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('SMTP_HOST')) define('SMTP_HOST', '');
if (!defined('SMTP_PORT')) define('SMTP_PORT', '');
if (!defined('SMTP_USER')) define('SMTP_USER', '');
if (!defined('SMTP_PASS')) define('SMTP_PASS', '');
if (!defined('EMAIL_FROM_NAME')) define('EMAIL_FROM_NAME', 'SOFCOS');

define('GOOGLE_REDIRECT_URI', 'http://localhost/SOFCOS/google_callback.php');

// --- 3. CONNEXION BDD ---
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
        DB_USER, 
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    // Sur GitHub ou en prod, on ne veut pas afficher le mot de passe dans l'erreur
    die("Erreur de connexion BDD (Vérifiez votre fichier .env)");
}


// Connexion à la base de données
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fonctions utilitaires
function securiser($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function estConnecte() {
    return isset($_SESSION['client_id']);
}

function estAdmin() {
    return isset($_SESSION['admin_id']);
}

function rediriger($url) {
    header("Location: $url");
    exit();
}

function afficherMessage($message, $type = 'info') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

function obtenirMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $type = $_SESSION['message_type'] ?? 'info';
        unset($_SESSION['message'], $_SESSION['message_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

// Configuration du site
define('SITE_NAME', 'SOFCOS');
define('SITE_URL', 'http://localhost/SOFCOS/');
define('DEVISE', 'MAD');
?>