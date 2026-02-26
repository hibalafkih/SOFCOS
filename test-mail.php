<?php
// test-mail.php - simple script to verify SMTP configuration
require_once 'config.php';
require_once 'includes/EmailManager.php';

$email = 'hibalafkih@email.com'; // remplacez par une adresse valide pour vos essais
$token = bin2hex(random_bytes(16));

$result = EmailManager::envoyerResetPassword($email, $token);

echo '<pre>'; var_dump($result); echo '</pre>';

// si l'envoi échoue, EmailManager affichera déjà l'erreur détaillée
?>