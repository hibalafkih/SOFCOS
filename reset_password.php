<?php
session_start();
require_once 'config.php';

$token = $_GET['token'] ?? '';
$message = '';
$validToken = false;
$user_id = null;

// 1. Vérification du token
if ($token) {
    // Connexion sécurisée si pas déjà faite via config.php
    if(!isset($pdo)) $pdo = new PDO("mysql:host=localhost;dbname=sofcos_db;charset=utf8", "root", "");

    $stmt = $pdo->prepare("SELECT id FROM clients WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        $validToken = true;
        $user_id = $user['id'];
    } else {
        $message = "Ce lien de réinitialisation est invalide ou a expiré.";
    }
} else {
    // Pas de token dans l'URL
    header("Location: connexion.php");
    exit();
}

// 2. Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST" && $validToken) {
    $pass1 = $_POST['pass1'];
    $pass2 = $_POST['pass2'];

    // Validation simple
    if (strlen($pass1) < 6) {
        $message = "Le mot de passe doit contenir au moins 6 caractères.";
    } elseif ($pass1 !== $pass2) {
        $message = "Les mots de passe ne correspondent pas.";
    } else {
        // Tout est bon : Hashage et Update
        $hash = password_hash($pass1, PASSWORD_DEFAULT);

        // On met à jour le mot de passe ET on nettoie le token pour qu'il ne soit plus utilisable
        $update = $pdo->prepare("UPDATE clients SET mot_de_passe = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        
        if($update->execute([$hash, $user_id])) {
            // Succès -> Redirection vers connexion avec un petit paramètre pour afficher un message
            header("Location: connexion.php?succes=password_updated");
            exit();
        } else {
            $message = "Erreur technique lors de la mise à jour.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau mot de passe - SOFCOS Paris</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Prata&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- DESIGN SYSTEM LUXE --- */
        body {
            margin: 0; padding: 0;
            font-family: 'Montserrat', sans-serif;
            overflow-x: hidden;
            background-color: #f0f0f0;
        }

        /* FOND ANIMÉ */
        .animated-background {
            position: fixed; top: 0; left: 0;
            width: 100vw; height: 100vh;
            z-index: -1;
            background: linear-gradient(-45deg, #0f2b24, #1A3C34, #2a5c50, #132e28);
            background-size: 400% 400%;
            animation: gradientSmooth 25s ease infinite;
        }

        @keyframes gradientSmooth {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .page-wrapper {
            display: flex; justify-content: center; align-items: center;
            min-height: calc(100vh - 150px); padding: 40px 20px;
        }

        /* CARTE CENTRÉE */
        .auth-card {
            background: #ffffff; width: 100%; 
            max-width: 450px;
            padding: 50px 40px; border-radius: 8px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            text-align: center;
            position: relative;
            animation: fadeInUp 0.8s ease-out forwards;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ICONE CLÉ ANIMÉE */
        .icon-container {
            width: 80px; height: 80px;
            background: #fdfbf7;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 25px auto;
            border: 1px solid #eee;
        }
        
        .icon-key {
            font-size: 32px; color: #C5A059;
            animation: rotateIcon 4s ease-in-out infinite;
        }

        @keyframes rotateIcon {
            0% { transform: rotate(0deg); }
            25% { transform: rotate(10deg); }
            75% { transform: rotate(-10deg); }
            100% { transform: rotate(0deg); }
        }

        h1 {
            font-family: 'Prata', serif; font-size: 26px; color: #1A3C34; margin: 0 0 10px 0;
        }
        p.subtitle {
            color: #888; font-size: 13px; margin: 0 0 30px 0;
        }

        /* INPUTS */
        .input-group { text-align: left; margin-bottom: 20px; }
        
        label {
            display: block; font-size: 11px; font-weight: 700; color: #1A3C34;
            text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px;
        }

        .input-field {
            width: 100%; padding: 14px; background: #fdfdfd;
            border: 1px solid #ccc; border-radius: 4px;
            font-family: 'Montserrat', sans-serif; font-size: 14px;
            transition: 0.3s; box-sizing: border-box;
        }
        .input-field:focus {
            border-color: #C5A059; background: #fff;
            box-shadow: 0 0 0 4px rgba(197, 160, 89, 0.1); outline: none;
        }

        /* BOUTON */
        .btn-submit {
            width: 100%; padding: 16px; background-color: #1A3C34;
            color: white; border: none; font-size: 13px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 2px; border-radius: 4px; cursor: pointer;
            transition: 0.3s; margin-top: 10px;
        }
        .btn-submit:hover { background-color: #C5A059; transform: translateY(-2px); }
        .btn-retry { background-color: #666; }

        /* ALERTES */
        .alert-error {
            background: #fee2e2; color: #991b1b; padding: 15px;
            border-radius: 4px; font-size: 13px; margin-bottom: 25px;
            border-left: 3px solid #b91c1c;
        }

    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <div class="animated-background"></div>

    <div class="page-wrapper">
        <div class="auth-card">
            
            <div class="icon-container">
                <i class="fas fa-key icon-key"></i>
            </div>
            
            <h1>Sécurité</h1>

            <?php if (!$validToken): ?>
                
                <div class="alert-error">
                    <i class="fas fa-times-circle"></i> <?= htmlspecialchars($message) ?>
                </div>
                <p class="subtitle">Le lien a expiré ou a déjà été utilisé.</p>
                <a href="mot-de-passe-oublie.php" class="btn-submit btn-retry" style="display:block; text-decoration:none;">
                    Demander un nouveau lien
                </a>

            <?php else: ?>

                <p class="subtitle">Créez votre nouveau mot de passe.</p>

                <?php if ($message): ?>
                    <div class="alert-error"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <form method="POST">
                    
                    <div class="input-group">
                        <label>Nouveau mot de passe</label>
                        <input type="password" name="pass1" class="input-field" placeholder="6 caractères minimum" required minlength="6">
                    </div>

                    <div class="input-group">
                        <label>Confirmer le mot de passe</label>
                        <input type="password" name="pass2" class="input-field" placeholder="Répétez le mot de passe" required>
                    </div>
                    
                    <button type="submit" class="btn-submit">Valider le changement</button>
                    
                </form>

            <?php endif; ?>
            
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

</body>
</html>