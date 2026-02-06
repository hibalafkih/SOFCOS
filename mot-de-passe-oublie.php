<?php
session_start();
require_once 'config.php';
// On inclut le gestionnaire d'email
if(file_exists('includes/EmailManager.php')) {
    require_once 'includes/EmailManager.php';
}

$message = '';
$messageType = ''; // 'success' ou 'error'

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    if (!empty($email)) {
        // 1. Vérifier si l'email existe
        if(!isset($pdo)) $pdo = new PDO("mysql:host=localhost;dbname=sofcos_db;charset=utf8", "root", "");

        $stmt = $pdo->prepare("SELECT id, prenom FROM clients WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // 2. Générer un token unique
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // 3. Sauvegarder le token dans la BDD
            $update = $pdo->prepare("UPDATE clients SET reset_token = ?, reset_expires = ? WHERE id = ?");
            $update->execute([$token, $expires, $user['id']]);

            // 4. ENVOI RÉEL DE L'EMAIL (Correction ici)
            // On vérifie que la classe existe et on appelle la méthode statique
            if (class_exists('EmailManager')) {
                
                // Appel de la fonction définie dans votre EmailManager.php
                $envoiReussi = EmailManager::envoyerResetPassword($email, $token);

                if ($envoiReussi) {
                    $message = "Un email de réinitialisation a été envoyé à <strong>$email</strong>.<br>Vérifiez vos spams (et attendez quelques secondes).";
                    $messageType = 'success';
                } else {
                    $message = "Erreur technique lors de l'envoi de l'email. Vérifiez la configuration SMTP.";
                    $messageType = 'error';
                }

            } else {
                $message = "Erreur : Le système d'envoi d'email (EmailManager) est introuvable.";
                $messageType = 'error';
            }
            
        } else {
            // Message générique pour la sécurité
            $message = "Si cet email existe, un lien vous a été envoyé.";
            $messageType = 'success';
        }
    } else {
        $message = "Veuillez entrer une adresse email.";
        $messageType = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié - SOFCOS Paris</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Prata&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- STYLE GLOBAL --- */
        body { margin: 0; padding: 0; font-family: 'Montserrat', sans-serif; overflow-x: hidden; background-color: #f0f0f0; }
        .animated-background { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: -1; background: linear-gradient(-45deg, #0f2b24, #1A3C34, #2a5c50, #132e28); background-size: 400% 400%; animation: gradientSmooth 25s ease infinite; }
        @keyframes gradientSmooth { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
        .page-wrapper { display: flex; justify-content: center; align-items: center; min-height: calc(100vh - 150px); padding: 40px 20px; }
        .auth-card { background: #ffffff; width: 100%; max-width: 450px; padding: 50px 40px; border-radius: 8px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4); text-align: center; position: relative; animation: fadeInUp 0.8s ease-out forwards; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .icon-container { width: 80px; height: 80px; background: #fdfbf7; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px auto; border: 1px solid #eee; }
        .icon-lock { font-size: 32px; color: #C5A059; animation: floatIcon 3s ease-in-out infinite; }
        @keyframes floatIcon { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-5px); } }
        h1 { font-family: 'Prata', serif; font-size: 28px; color: #1A3C34; margin: 0 0 15px 0; }
        p.description { color: #666; font-size: 14px; margin: 0 0 30px 0; line-height: 1.6; }
        .input-group { text-align: left; margin-bottom: 25px; }
        label { display: block; font-size: 11px; font-weight: 700; color: #1A3C34; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px; }
        .input-field { width: 100%; padding: 14px; background: #fdfdfd; border: 1px solid #ccc; border-radius: 4px; font-family: 'Montserrat', sans-serif; font-size: 14px; transition: 0.3s; box-sizing: border-box; }
        .input-field:focus { border-color: #C5A059; background: #fff; box-shadow: 0 0 0 4px rgba(197, 160, 89, 0.1); outline: none; }
        .btn-submit { width: 100%; padding: 16px; background-color: #1A3C34; color: white; border: none; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; border-radius: 4px; cursor: pointer; transition: 0.3s; }
        .btn-submit:hover { background-color: #C5A059; transform: translateY(-2px); }
        .alert { padding: 15px; border-radius: 4px; margin-bottom: 25px; font-size: 13px; line-height: 1.5; }
        .alert-success { background-color: #e8f5e9; color: #1b5e20; border: 1px solid #c8e6c9; }
        .alert-error { background-color: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .back-link { display: inline-block; margin-top: 25px; color: #999; text-decoration: none; font-size: 13px; font-weight: 500; transition: 0.3s; }
        .back-link:hover { color: #1A3C34; }
        .back-link i { margin-right: 5px; transition: 0.3s; }
        .back-link:hover i { transform: translateX(-3px); }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="animated-background"></div>
    <div class="page-wrapper">
        <div class="auth-card">
            <div class="icon-container"><i class="fas fa-unlock-alt icon-lock"></i></div>
            <h1>Mot de passe oublié ?</h1>
            <p class="description">Pas de panique. Entrez votre adresse email ci-dessous et nous vous enverrons un lien sécurisé pour récupérer votre accès.</p>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>"> <?= $message ?> </div>
            <?php endif; ?>

            <form method="POST">
                <div class="input-group">
                    <label>Votre adresse Email</label>
                    <input type="email" name="email" class="input-field" placeholder="exemple@email.com" required>
                </div>
                <button type="submit" class="btn-submit">Envoyer le lien</button>
            </form>

            <a href="connexion.php" class="back-link"><i class="fas fa-arrow-left"></i> Retour à la connexion</a>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>