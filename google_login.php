<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <?php
// google_login.php
session_start();
require_once 'config.php'; // Charger les variables d'environnement depuis .env

// Utiliser les constantes définies dans config.php
$client_id = GOOGLE_CLIENT_ID ?? '';
$redirect_uri = GOOGLE_REDIRECT_URI ?? 'http://localhost/SOFCOS/google_callback.php';

// Vérifier que le client_id est configuré
if (!$client_id) {
    die('Erreur: GOOGLE_CLIENT_ID manquant. Configurez-le dans votre fichier .env');
} 

// On demande l'accès à l'email et au profil
$scope = 'email profile';

// Création de l'URL de connexion Google
$login_url = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
    'client_id'     => $client_id,
    'redirect_uri'  => $redirect_uri,
    'response_type' => 'code',
    'scope'         => $scope,
    'access_type'   => 'online'
]);

// Redirection vers Google
header('Location: ' . $login_url);
exit();
?>
</body>
</html>