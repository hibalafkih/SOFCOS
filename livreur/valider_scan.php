<?php
session_start();
require_once '../config.php';

if(!isset($_SESSION['livreur_id']) || !isset($_GET['id'])) { 
    header("Location: index.php"); exit(); 
}

$id_cmd = (int)$_GET['id'];
$livreur_id = $_SESSION['livreur_id'];

// 1. Vérifier si cette commande existe et appartient à ce livreur
$stmt = $pdo->prepare("SELECT * FROM commandes WHERE id = ? AND livreur_id = ?");
$stmt->execute([$id_cmd, $livreur_id]);
$commande = $stmt->fetch();

if($commande) {
    // 2. Mise à jour : Statut = LIVRE
    $update = $pdo->prepare("UPDATE commandes SET statut = 'livre', statut_paiement = 'paye' WHERE id = ?");
    $update->execute([$id_cmd]);
    $msg = "✅ Commande #$id_cmd validée !";
    $color = "#10b981"; // Vert
    $icon = "fa-check-circle";
} else {
    // Erreur : Ce n'est pas sa commande ou elle n'existe pas
    $msg = "❌ Erreur : Commande non assignée à vous.";
    $color = "#ef4444"; // Rouge
    $icon = "fa-times-circle";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultat</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { 
            font-family: 'Outfit', sans-serif; 
            background: <?= $color ?>; 
            color: white; 
            height: 100vh; 
            display: flex; flex-direction: column; 
            align-items: center; justify-content: center; 
            text-align: center; margin: 0;
        }
        h1 { font-size: 24px; margin-top: 20px; }
        .icon { font-size: 80px; }
        .btn { 
            background: white; color: <?= $color ?>; 
            padding: 15px 40px; border-radius: 30px; 
            text-decoration: none; font-weight: bold; margin-top: 40px; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <i class="fas <?= $icon ?> icon"></i>
    <h1><?= $msg ?></h1>
    <a href="index.php" class="btn">Continuer la tournée</a>
</body>
</html>