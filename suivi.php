<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['client'])) {
    header("Location: connexion.php");
    exit();
}

$client_id = $_SESSION['client']['id'];
if(!isset($pdo)) $pdo = new PDO("mysql:host=localhost;dbname=sofcos_db;charset=utf8", "root", "");

// Récupérer les commandes
$stmt = $pdo->prepare("SELECT * FROM commandes WHERE client_id = ? ORDER BY date_creation DESC");
$stmt->execute([$client_id]);
$commandes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes Commandes - SOFCOS</title>
    <link href="https://fonts.googleapis.com/css2?family=Prata&family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Montserrat', sans-serif; background: #f9f9f9; }
        .container { max-width: 1000px; margin: 50px auto; padding: 20px; }
        h1 { font-family: 'Prata', serif; color: #1A3C34; text-align: center; margin-bottom: 40px; }
        
        .order-card { background: white; border-radius: 8px; padding: 25px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-left: 5px solid #1A3C34; }
        .order-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 15px; }
        .order-id { font-weight: bold; color: #1A3C34; font-size: 18px; }
        .order-date { color: #888; font-size: 14px; }
        
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .status-attente { background: #fff3cd; color: #856404; }
        .status-validee { background: #d4edda; color: #155724; }
        
        .btn-view { color: #C5A059; text-decoration: none; font-weight: 600; font-size: 14px; }
        .btn-view:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <h1>Suivi de mes Commandes</h1>

        <?php if (empty($commandes)): ?>
            <div style="text-align:center; padding: 50px;">
                <p>Vous n'avez pas encore passé de commande.</p>
                <a href="produits.php" style="color:#C5A059;">Découvrir la boutique</a>
            </div>
        <?php else: ?>
            <?php foreach ($commandes as $cmd): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <span class="order-id">Commande #<?= $cmd['id'] ?></span>
                            <span class="order-date"> • <?= date('d/m/Y', strtotime($cmd['date_creation'])) ?></span>
                        </div>
                        <span class="status-badge status-attente"><?= htmlspecialchars($cmd['statut']) ?></span>
                    </div>
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <strong>Total : <?= number_format($cmd['montant_total'], 2) ?> DH</strong><br>
                            <span style="font-size:13px; color:#666;">Paiement : <?= $cmd['methode_paiement'] ?></span>
                        </div>
                        <button class="btn-view" style="background:none; border:none; cursor:default;">
                            <i class="fas fa-box"></i> En cours de traitement
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>