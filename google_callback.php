<?php
// google_callback.php
require_once 'config.php'; 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Charger les variables d'environnement depuis config.php (qui charge .env)
// Les constantes sont définies dans config.php: GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET
$client_id = GOOGLE_CLIENT_ID ?? '';
$client_secret = GOOGLE_CLIENT_SECRET ?? '';
$redirect_uri = GOOGLE_REDIRECT_URI ?? 'http://localhost/SOFCOS/google_callback.php';

// Vérifier que les credentials sont configurées
if (!$client_id || !$client_secret) {
    die('Erreur: Variables Google OAuth manquantes. Configurez GOOGLE_CLIENT_ID et GOOGLE_CLIENT_SECRET dans votre fichier .env');
}

if (isset($_GET['code'])) {
    // 1. Échange du code contre un token
    $token_url = "https://oauth2.googleapis.com/token";
    $post_data = [
        'code'          => $_GET['code'],
        'client_id'     => $client_id,
        'client_secret' => $client_secret,
        'redirect_uri'  => $redirect_uri,
        'grant_type'    => 'authorization_code'
    ];

    $ch = curl_init($token_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (isset($response['access_token'])) {
        // 2. Récupération des infos Utilisateur depuis Google
        $user_info_url = "https://www.googleapis.com/oauth2/v3/userinfo?access_token=" . $response['access_token'];
        
        $ch_info = curl_init($user_info_url);
        curl_setopt($ch_info, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch_info, CURLOPT_SSL_VERIFYPEER, false);
        $user_info = json_decode(curl_exec($ch_info), true);
        curl_close($ch_info);

        $email = $user_info['email'];
        $nom = $user_info['family_name'] ?? '';
        $prenom = $user_info['given_name'] ?? '';

        // Connexion BDD si nécessaire
        if(!isset($pdo)) $pdo = new PDO("mysql:host=localhost;dbname=sofcos_db;charset=utf8", "root", "");

        // On vérifie si le client existe déjà
        $stmt = $pdo->prepare("SELECT * FROM clients WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // --- CAS 1 : IL EXISTE DÉJÀ -> ON LE CONNECTE ---
            
            // CORRECTION 2 : On utilise 'client_id' pour correspondre à mon-compte.php
            $_SESSION['client_id'] = $user['id'];
            $_SESSION['client_nom'] = $user['nom'];
            $_SESSION['client_prenom'] = $user['prenom'];
            $_SESSION['client_email'] = $user['email'];

        } else {
            // --- CAS 2 : NOUVEAU CLIENT -> INSCRIPTION AUTOMATIQUE ---
            // On l'inscrit directement pour éviter de le perdre
            
            // Mot de passe aléatoire (car connexion Google)
            $random_pass = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
            
            $insert = $pdo->prepare("INSERT INTO clients (nom, prenom, email, mot_de_passe, date_inscription) VALUES (?, ?, ?, ?, NOW())");
            $insert->execute([$nom, $prenom, $email, $random_pass]);
            
            // On récupère l'ID créé et on connecte
            $new_id = $pdo->lastInsertId();
            $_SESSION['client_id'] = $new_id;
            $_SESSION['client_nom'] = $nom;
            $_SESSION['client_prenom'] = $prenom;
            $_SESSION['client_email'] = $email;
        }

        // CORRECTION 3 : Redirection vers MON COMPTE (et pas index)
        header('Location: mon-compte.php');
        exit();
    }
}

// Si échec, retour connexion
header('Location: connexion.php');
exit();
?>