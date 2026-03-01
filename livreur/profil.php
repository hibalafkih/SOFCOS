<?php
session_start();
require_once '../config.php';

if(!isset($_SESSION['livreur_id'])) { header("Location: login.php"); exit(); }

$id_livreur = $_SESSION['livreur_id'];

// Récupérer les infos du livreur
$stmt = $pdo->prepare("SELECT * FROM livreurs WHERE id = ?");
$stmt->execute([$id_livreur]);
$livreur = $stmt->fetch(PDO::FETCH_ASSOC);

// Statistiques : Livrées aujourd'hui
$stmt_today = $pdo->prepare("SELECT COUNT(*) FROM commandes WHERE livreur_id = ? AND statut = 'livre' AND DATE(date_commande) = CURDATE()");
$stmt_today->execute([$id_livreur]);
$livre_today = $stmt_today->fetchColumn();

// Statistiques : Total historique
$stmt_total = $pdo->prepare("SELECT COUNT(*) FROM commandes WHERE livreur_id = ? AND statut = 'livre'");
$stmt_total->execute([$id_livreur]);
$livre_total = $stmt_total->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Mon Profil - Livreur</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="header-profile">
        <div class="avatar-circle">
            <?= strtoupper(substr($livreur['nom'], 0, 1)) ?>
        </div>
        <h1 class="driver-name"><?= htmlspecialchars($livreur['nom']) ?></h1>
        <div class="driver-phone"><?= htmlspecialchars($livreur['telephone']) ?></div>
    </div>

    <div class="stats-container">
        <div class="stat-card">
            <span class="stat-num"><?= $livre_today ?></span>
            <span class="stat-label">Aujourd'hui</span>
        </div>
        <div class="stat-card">
            <span class="stat-num"><?= $livre_total ?></span>
            <span class="stat-label">Total Livré</span>
        </div>
    </div>

    <div class="menu-list">
        <a href="historique.php" class="menu-item">
            <div class="menu-left">
                <div class="menu-icon"><i class="fas fa-history"></i></div>
                <span>Historique complet</span>
            </div>
            <i class="fas fa-chevron-right" style="color:#ccc; font-size:12px;"></i>
        </a>
        
        <a href="logout.php" class="menu-item logout-btn">
            <div class="menu-left">
                <div class="menu-icon"><i class="fas fa-sign-out-alt"></i></div>
                <span>Se déconnecter</span>
            </div>
        </a>
    </div>

    <?php include 'nav.php'; ?>

</body>
</html>
