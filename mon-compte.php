<?php
// mon-compte.php
session_start();
require_once 'config.php';

// SÉCURITÉ
if(!isset($_SESSION['client_id'])) {
    header('Location: connexion.php');
    exit();
}
$client_id = $_SESSION['client_id'];

try {
    if(!isset($pdo)) {
        $pdo = new PDO("mysql:host=localhost;dbname=sofcos_db;charset=utf8", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    // Infos Client
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch();

    // Commandes récentes
    $cmdStmt = $pdo->prepare("SELECT * FROM commandes WHERE client_id = ? ORDER BY date_commande DESC LIMIT 5");
    $cmdStmt->execute([$client_id]);
    $commandes = $cmdStmt->fetchAll();

    // Stats
    $countAll = $pdo->prepare("SELECT COUNT(*) FROM commandes WHERE client_id = ?");
    $countAll->execute([$client_id]);
    $totalCmd = $countAll->fetchColumn();

    $sumStmt = $pdo->prepare("SELECT SUM(total) FROM commandes WHERE client_id = ? AND statut != 'annule'");
    $sumStmt->execute([$client_id]);
    $totalDepense = $sumStmt->fetchColumn() ?: 0;

    $countEnCours = $pdo->prepare("SELECT COUNT(*) FROM commandes WHERE client_id = ? AND statut NOT IN ('livre', 'annule')");
    $countEnCours->execute([$client_id]);
    $enCours = $countEnCours->fetchColumn() ?: 0;

} catch (PDOException $e) { die("Erreur : " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Espace - SOFCOS</title>
    <link href="https://fonts.googleapis.com/css2?family=Prata&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #1A3C34; /* Vert Luxe */
            --gold: #C5A059;    /* Or */
            --bg-light: #F9F7F2; /* Beige très clair */
            --white: #ffffff;
            --text: #2c2c2c;
            --gray-light: #e5e5e5;
            --shadow: 0 10px 30px rgba(26, 60, 52, 0.08);
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--bg-light);
            color: var(--text);
            margin: 0;
        }

        /* CONTAINER GLOBAL */
        .dashboard-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 40px;
        }

        /* SIDEBAR DE LUXE */
        .sidebar {
            background: var(--white);
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 30px 0;
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        .user-profile-summary {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--gray-light);
            margin-bottom: 10px;
        }
        .avatar-circle {
            width: 80px; height: 80px;
            background: var(--bg-light);
            color: var(--gold);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 30px; margin: 0 auto 15px;
            border: 2px solid var(--gold);
        }
        .user-name { font-family: 'Prata', serif; font-size: 18px; color: var(--primary); }

        .menu-list { list-style: none; padding: 0; margin: 0; }
        .menu-link {
            display: flex; align-items: center; gap: 15px;
            padding: 16px 30px;
            text-decoration: none;
            color: #666;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        .menu-link:hover, .menu-link.active {
            background: linear-gradient(90deg, rgba(197,160,89,0.1) 0%, rgba(255,255,255,0) 100%);
            color: var(--primary);
            border-left-color: var(--gold);
        }
        .menu-link.logout { color: #d63031; margin-top: 20px; border-top: 1px solid var(--gray-light); }
        .menu-link.logout:hover { background: #fff5f5; border-color: #d63031; }

        /* CONTENU PRINCIPAL */
        .main-content { display: flex; flex-direction: column; gap: 30px; }

        /* HEADER SECTION */
        .welcome-header {
            background: var(--primary);
            color: var(--white);
            padding: 40px;
            border-radius: 8px;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow);
        }
        .welcome-header h1 { font-family: 'Prata', serif; margin: 0 0 10px 0; font-size: 28px; }
        .welcome-header p { margin: 0; opacity: 0.8; font-weight: 300; }
        .welcome-header::after {
            content: ''; position: absolute; top: -50%; right: -10%; width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(197,160,89,0.3) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%;
        }

        /* STATS CARDS */
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .stat-card {
            background: var(--white); padding: 25px;
            border-radius: 8px; box-shadow: var(--shadow);
            display: flex; align-items: center; justify-content: space-between;
            transition: transform 0.3s;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-info h3 { font-size: 24px; color: var(--primary); margin: 5px 0 0 0; font-family: 'Prata', serif; }
        .stat-info p { margin: 0; font-size: 11px; text-transform: uppercase; color: #888; letter-spacing: 1px; }
        .stat-icon {
            width: 50px; height: 50px; background: rgba(197,160,89,0.1);
            color: var(--gold); border-radius: 50%;
            display: flex; align-items: center; justify-content: center; font-size: 20px;
        }

        /* LISTE DES COMMANDES */
        .section-box { background: var(--white); padding: 30px; border-radius: 8px; box-shadow: var(--shadow); }
        .section-title {
            font-family: 'Prata', serif; font-size: 20px; color: var(--primary);
            padding-bottom: 15px; border-bottom: 1px solid var(--gray-light); margin-bottom: 20px;
        }

        .cmd-table { width: 100%; border-collapse: collapse; }
        .cmd-table th { text-align: left; padding: 15px; font-size: 11px; text-transform: uppercase; color: #999; }
        .cmd-table td { padding: 15px; border-bottom: 1px solid #f0f0f0; font-size: 14px; }
        .cmd-table tr:last-child td { border-bottom: none; }
        
        /* Badges */
        .badge { padding: 6px 12px; border-radius: 30px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .badge.en_attente { background: #FFF8E1; color: #F39C12; }
        .badge.livre { background: #E8F5E9; color: #2E7D32; }
        .badge.annule { background: #FFEBEE; color: #C62828; }
        .badge.expedie { background: #E3F2FD; color: #1565C0; }

        .btn-view {
            padding: 8px 16px; background: var(--white); border: 1px solid var(--gray-light);
            color: var(--text); text-decoration: none; font-size: 12px; font-weight: 600;
            border-radius: 4px; transition: 0.3s;
        }
        .btn-view:hover { border-color: var(--gold); color: var(--gold); }
        /* Styles des Badges Statuts */
.badge { 
    padding: 6px 12px; border-radius: 30px; font-size: 11px; 
    font-weight: 600; text-transform: uppercase; display: inline-flex; align-items: center; gap: 5px;
}
.badge.en_attente { background: #FFF8E1; color: #F39C12; border: 1px solid #FFE082; }
.badge.confirme   { background: #E8F5E9; color: #2E7D32; border: 1px solid #A5D6A7; } /* Vert clair */
.badge.expedie    { background: #E3F2FD; color: #1565C0; border: 1px solid #90CAF9; } /* Bleu */
.badge.livre      { background: #1A3C34; color: #C5A059; border: 1px solid #1A3C34; } /* Luxe : Fond vert foncé, texte or */
.badge.annule     { background: #FFEBEE; color: #C62828; border: 1px solid #EF9A9A; }

        @media (max-width: 900px) {
            .dashboard-container { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr; }
            .cmd-table { display: block; overflow-x: auto; }
        }
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<div class="dashboard-container">
    
    <div class="sidebar">
        <div class="user-profile-summary">
            <div class="avatar-circle">
                <?= strtoupper(substr($client['prenom'], 0, 1)) ?>
            </div>
            <div class="user-name"><?= htmlspecialchars($client['prenom']) ?></div>
        </div>
        <ul class="menu-list">
            <li><a href="mon-compte.php" class="menu-link active"><i class="fas fa-th-large"></i> Tableau de bord</a></li>
            <li><a href="mes-commandes.php" class="menu-link"><i class="fas fa-box"></i> Mes commandes</a></li>
            <li><a href="mes-informations.php" class="menu-link"><i class="fas fa-user-edit"></i> Profil</a></li>
            <li><a href="mes-adresses.php" class="menu-link"><i class="fas fa-map-marker-alt"></i> Adresses</a></li>
            <li><a href="changer-mot-de-passe.php" class="menu-link"><i class="fas fa-lock"></i> Sécurité</a></li>
            <li><a href="deconnexion.php" class="menu-link logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
        </ul>
    </div>

    <div class="main-content">
        
        <div class="welcome-header">
            <h1>Bonjour, <?= htmlspecialchars($client['prenom']) ?></h1>
            <p>Heureux de vous revoir chez SOFCOS. Voici l'état de votre compte.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info"><p>Total Commandes</p><h3><?= $totalCmd ?></h3></div>
                <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info"><p>Total Dépensé</p><h3><?= number_format($totalDepense, 0, ',', ' ') ?> DH</h3></div>
                <div class="stat-icon"><i class="fas fa-wallet"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info"><p>En cours</p><h3><?= $enCours ?></h3></div>
                <div class="stat-icon"><i class="fas fa-truck-loading"></i></div>
            </div>
        </div>

        <div class="section-box">
            <h2 class="section-title">Dernières commandes</h2>
            <?php if(empty($commandes)): ?>
                <p style="text-align:center; color:#999; padding:20px;">Aucune commande pour le moment.</p>
            <?php else: ?>
                <table class="cmd-table">
                    <thead>
                        <tr>
                            <th>N° Com.</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
    <?php foreach($commandes as $cmd): 
        // 1. Nettoyage du statut pour éviter les erreurs
        $st = strtolower(trim($cmd['statut'])); // tout en minuscule, sans espaces
        
        // 2. Définition de la classe CSS et du texte
        $badgeClass = 'en_attente'; // Par défaut
        $icon = '<i class="fas fa-clock"></i>';

        if(strpos($st, 'livre') !== false) {
            $badgeClass = 'livre';
            $icon = '<i class="fas fa-check-circle"></i>';
        }
        elseif(strpos($st, 'expedi') !== false) {
            $badgeClass = 'expedie';
            $icon = '<i class="fas fa-truck"></i>';
        }
        elseif(strpos($st, 'confirm') !== false) {
            $badgeClass = 'confirme';
            $icon = '<i class="fas fa-thumbs-up"></i>';
        }
        elseif(strpos($st, 'annul') !== false) {
            $badgeClass = 'annule';
            $icon = '<i class="fas fa-times-circle"></i>';
        }
    ?>
    <tr>
        <td><strong>#<?= $cmd['id'] ?></strong></td>
        <td><?= date('d/m/Y', strtotime($cmd['date_commande'])) ?></td>
        <td style="font-weight:600;"><?= number_format($cmd['total'], 2) ?> DH</td>
        <td>
            <span class="badge <?= $badgeClass ?>">
                <?= $icon ?> <?= ucfirst($cmd['statut']) ?>
            </span>
        </td>
        <td><a href="suivi.php?id=<?= $cmd['id'] ?>" class="btn-view">Gérer</a></td>
    </tr>
    <?php endforeach; ?>
</tbody>
                </table>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>