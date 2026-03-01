<?php
session_start();
require_once '../config.php';

if(isset($_POST['tel'])) {
    $tel = $_POST['tel'];
    // Vérification du livreur
    $stmt = $pdo->prepare("SELECT * FROM livreurs WHERE telephone = ? AND actif = 1");
    $stmt->execute([$tel]);
    $user = $stmt->fetch();

    if($user) {
        $_SESSION['livreur_id'] = $user['id'];
        $_SESSION['livreur_nom'] = $user['nom'];
        header("Location: index.php");
        exit();
    } else {
        $error = "Numéro inconnu ou compte inactif.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Connexion Livreur - SOFCOS</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #1A3C34;
            --gold: #C5A059;
            --bg: #f4f7f6;
        }
        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--primary);
            background-image: linear-gradient(135deg, #1A3C34 0%, #0f241f 100%);
            margin: 0;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        /* --- ANIMATIONS --- */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.8); }
            to { opacity: 1; transform: scale(1); }
        }
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
            100% { transform: translateY(0px); }
        }

        .login-card {
            background: white;
            width: 100%;
            max-width: 360px;
            padding: 40px 30px;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
            text-align: center;
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1);
        }
        .logo-icon {
            width: 70px;
            height: 70px;
            background: rgba(26, 60, 52, 0.1);
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            margin: 0 auto 20px;
            animation: scaleIn 0.6s cubic-bezier(0.2, 0.8, 0.2, 1) 0.2s backwards;
        }
        .logo-icon i {
            animation: float 3s ease-in-out infinite;
        }
        h2 { 
            margin: 0 0 10px; color: var(--primary); font-weight: 600; 
            animation: fadeInUp 0.6s ease-out 0.3s backwards;
        }
        p.subtitle { 
            color: #888; font-size: 14px; margin: 0 0 30px; 
            animation: fadeInUp 0.6s ease-out 0.4s backwards;
        }
        
        .input-group { position: relative; margin-bottom: 20px; animation: fadeInUp 0.6s ease-out 0.5s backwards; }
        .input-group i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #aaa; }
        
        input {
            width: 100%; padding: 15px 15px 15px 45px;
            border: 2px solid #eee; border-radius: 12px;
            font-size: 15px; box-sizing: border-box; font-family: inherit;
            transition: 0.3s; outline: none;
        }
        input:focus { border-color: var(--gold); box-shadow: 0 0 0 4px rgba(197, 160, 89, 0.1); }
        
        button {
            width: 100%; padding: 16px; background: var(--primary); color: white;
            border: none; border-radius: 12px; font-weight: 600; font-size: 16px;
            cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 10px;
            animation: fadeInUp 0.6s ease-out 0.6s backwards;
        }
        button:hover { background: #142e28; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        
        .error-msg { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px; font-size: 13px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; text-align: left; }
        .footer-text { margin-top: 30px; font-size: 12px; color: #aaa; animation: fadeInUp 0.6s ease-out 0.7s backwards; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo-icon">
            <i class="fas fa-shipping-fast"></i>
        </div>
        <h2>Espace Livreur</h2>
        <p class="subtitle">Connectez-vous pour gérer vos tournées</p>

        <?php if(isset($error)): ?>
            <div class="error-msg">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <i class="fas fa-phone-alt"></i>
                <input type="tel" name="tel" placeholder="Votre numéro (ex: 0600000000)" required autofocus>
            </div>
            <button type="submit">Commencer <i class="fas fa-arrow-right"></i></button>
        </form>
        
        <div class="footer-text">&copy; <?= date('Y') ?> SOFCOS Logistics</div>
    </div>
</body>
</html>