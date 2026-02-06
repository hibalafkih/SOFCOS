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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Livreur</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background: #1A3C34; color: white; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .box { background: white; padding: 30px; border-radius: 15px; width: 80%; max-width: 300px; text-align: center; color: #333; }
        input { width: 100%; padding: 15px; margin: 15px 0; border: 2px solid #eee; border-radius: 8px; font-size: 16px; box-sizing: border-box; text-align: center; }
        button { width: 100%; padding: 15px; background: #C5A059; color: white; border: none; border-radius: 8px; font-weight: bold; font-size: 16px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="box">
        <h2 style="margin-top:0;">Espace Livreur</h2>
        <?php if(isset($error)) echo "<p style='color:red'>$error</p>"; ?>
        <form method="POST">
            <label>Votre Numéro de téléphone</label>
            <input type="tel" name="tel" placeholder="06XXXXXXXX" required>
            <button type="submit">Commencer la tournée</button>
        </form>
    </div>
</body>
</html>