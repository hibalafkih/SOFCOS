<?php
session_start();
require_once 'config.php';

// 1. SÉCURITÉ : Vérifier si l'utilisateur est connecté
if(!isset($_SESSION['client_id'])) {
    header('Location: connexion.php');
    exit();
}

$client_id = $_SESSION['client_id'];

try {
    // Connexion DB si non ouverte
    if(!isset($pdo)) $pdo = new PDO("mysql:host=localhost;dbname=sofcos_db;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. RECUPERATION CLIENT (Avec sécurité anti-suppression)
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch();

    if (!$client) {
        session_destroy();
        header('Location: connexion.php');
        exit();
    }

    // 3. RECUPERATION COMMANDES
    $cmdStmt = $pdo->prepare("SELECT * FROM commandes WHERE client_id = ? ORDER BY date_commande DESC");
    $cmdStmt->execute([$client_id]);
    $commandes = $cmdStmt->fetchAll();

    // 4. STATISTIQUES (Code corrigé et sécurisé)
    // Calcul Total Dépensé
    $sumStmt = $pdo->prepare("SELECT SUM(total) FROM commandes WHERE client_id = ?");
    $sumStmt->execute([$client_id]);
    $totalDepense = $sumStmt->fetchColumn() ?: 0;

    // Calcul Commandes en cours
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM commandes WHERE client_id = ? AND statut IN ('en_attente', 'confirmee', 'expediee')");
    $countStmt->execute([$client_id]);
    $enCours = $countStmt->fetchColumn() ?: 0;

    $stats = [
        'total_commandes' => count($commandes),
        'total_depense' => $totalDepense,
        'en_cours' => $enCours
    ];

} catch (PDOException $e) {
    die("Erreur technique : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Compte - SOFCOS Paris</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Prata&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- DESIGN SYSTEM LUXE (Identique au Login) --- */
        :root {
            --green-luxe: #1A3C34;
            --gold-accent: #C5A059;
            --beige-bg: #fdfbf7;
            --text-main: #2c2c2c;
            --white: #ffffff;
            --border-light: #e0e0e0;
        }

        body {
            margin: 0; padding: 0;
            font-family: 'Montserrat', sans-serif;
            background-color: var(--beige-bg);
            color: var(--text-main);
        }

        .compte-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        /* En-tête de page */
        .compte-header {
            background-color: var(--green-luxe);
            color: var(--white);
            padding: 50px;
            border-radius: 4px;
            margin-bottom: 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        /* Ligne dorée décorative */
        .compte-header::after {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px;
            background: linear-gradient(90deg, #d4af37, #f3e5ab);
        }

        .compte-header h1 {
            font-family: 'Prata', serif; font-size: 38px; margin: 0 0 10px 0; letter-spacing: 1px;
        }
        .compte-header p {
            font-family: 'Montserrat', sans-serif; font-weight: 300; opacity: 0.9;
        }

        /* Statistiques */
        .stats-row {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px; margin-bottom: 40px;
        }

        .stat-box {
            background: var(--white); padding: 30px;
            border: 1px solid rgba(0,0,0,0.05); text-align: center;
            transition: transform 0.3s ease;
        }
        .stat-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border-bottom: 3px solid var(--gold-accent);
        }
        .stat-box i { font-size: 32px; color: var(--gold-accent); margin-bottom: 15px; }
        .stat-box h3 { font-family: 'Prata', serif; font-size: 32px; margin: 5px 0; color: var(--green-luxe); }
        .stat-box p { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: #888; }

        /* Grid Layout */
        .compte-grid {
            display: grid; grid-template-columns: 280px 1fr; gap: 40px;
        }

        /* Menu Sidebar */
        .compte-sidebar {
            background: var(--white); padding: 30px;
            height: fit-content; border: 1px solid rgba(0,0,0,0.05);
        }
        .sidebar-title {
            font-family: 'Prata', serif; font-size: 18px; 
            margin-bottom: 25px; padding-bottom: 15px;
            border-bottom: 1px solid var(--border-light); color: var(--green-luxe);
        }
        .compte-menu { list-style: none; padding: 0; margin: 0; }
        .compte-menu li { margin-bottom: 8px; }
        .compte-menu a {
            display: flex; align-items: center; gap: 15px;
            padding: 14px 20px; color: var(--text-main);
            text-decoration: none; font-size: 13px; font-weight: 500;
            transition: all 0.3s; border-left: 3px solid transparent;
        }
        .compte-menu a:hover, .compte-menu a.active {
            background: #fcfcfc; color: var(--green-luxe);
            border-left: 3px solid var(--gold-accent);
        }
        .compte-menu a.logout { color: #d63031; }
        .compte-menu a.logout:hover { background: #fff5f5; border-color: #d63031; }

        /* Contenu */
        .compte-content {
            background: var(--white); padding: 40px; border: 1px solid rgba(0,0,0,0.05);
        }
        .section-title {
            font-family: 'Prata', serif; font-size: 26px;
            margin-bottom: 30px; padding-bottom: 15px;
            border-bottom: 1px solid var(--border-light); color: var(--green-luxe);
        }

        /* Cards Commandes */
        .commande-card {
            border: 1px solid var(--border-light); padding: 25px;
            margin-bottom: 20px; transition: all 0.3s;
        }
        .commande-card:hover {
            border-color: var(--gold-accent); box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        .commande-header {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;
        }
        
        .badge {
            padding: 6px 14px; font-size: 10px; font-weight: 700; 
            text-transform: uppercase; letter-spacing: 1px;
        }
        .badge-en_attente { background: #fff8e1; color: #f39c12; }
        .badge-confirmee { background: #e8f5e9; color: #27ae60; }
        .badge-expediee { background: #e3f2fd; color: #2980b9; }

        .commande-details {
            display: grid; grid-template-columns: repeat(3, 1fr);
            gap: 20px; padding-top: 20px; border-top: 1px dashed var(--border-light);
        }
        .detail-item { font-size: 13px; color: #666; }
        .detail-item strong { display: block; color: var(--green-luxe); font-size: 11px; text-transform: uppercase; margin-bottom: 4px; }
        
        .btn-small {
            display: inline-block; padding: 8px 20px; background: var(--text-main); color: white;
            font-size: 11px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;
            text-decoration: none; transition: 0.3s;
        }
        .btn-small:hover { background: var(--gold-accent); }
        
        .btn-action {
            display: inline-block; padding: 12px 30px; background: var(--green-luxe); 
            color: white; text-decoration: none; text-transform: uppercase; 
            font-size: 12px; letter-spacing: 1px; margin-top: 20px;
        }
        .empty-state { text-align: center; padding: 50px 20px; }
        .empty-state i { font-size: 48px; color: #ddd; margin-bottom: 20px; }

        @media (max-width: 900px) {
            .compte-grid { grid-template-columns: 1fr; }
            .commande-details { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>
    
    <div class="compte-container">
        
        <div class="compte-header">
            <h1>Bienvenue, <?= htmlspecialchars($client['prenom'] . ' ' . $client['nom']) ?></h1>
            <p>Espace client personnel & suivi des commandes</p>
        </div>
        
        <div class="stats-row">
            <div class="stat-box">
                <i class="fas fa-shopping-bag"></i>
                <h3><?= $stats['total_commandes'] ?></h3>
                <p>Commandes passées</p>
            </div>
            
            <div class="stat-box">
                <i class="fas fa-euro-sign"></i> 
                <h3><?= number_format($stats['total_depense'], 2, ',', ' ') ?> DH</h3>
                <p>Total Achats</p>
            </div>
            
            <div class="stat-box">
                <i class="fas fa-truck-loading"></i>
                <h3><?= $stats['en_cours'] ?></h3>
                <p>En cours de livraison</p>
            </div>
        </div>
        
        <div class="compte-grid">
            
            <div class="compte-sidebar">
                <div class="sidebar-title">Mon Menu</div>
                <ul class="compte-menu">
                    <li><a href="mon-compte.php" class="active"><i class="fas fa-home"></i> Tableau de bord</a></li>
                    <li><a href="mes-commandes.php"><i class="fas fa-box-open"></i> Mes commandes</a></li>
                    <li><a href="mes-informations.php"><i class="far fa-user"></i> Mes informations</a></li>
                    <li><a href="mes-adresses.php"><i class="fas fa-map-marker-alt"></i> Carnet d'adresses</a></li>
                    <li><a href="changer-mot-de-passe.php"><i class="fas fa-lock"></i> Sécurité</a></li>
                    <li style="margin-top: 15px; border-top: 1px solid #eee; padding-top: 15px;">
                        <a href="deconnexion.php" class="logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                    </li>
                </ul>
            </div>
            
            <div class="compte-content">
                <h2 class="section-title">Mes dernières commandes</h2>
                
                <?php if(empty($commandes)): ?>
                    <div class="empty-state">
                        <i class="fas fa-shopping-basket"></i>
                        <h3 style="font-family:'Prata', serif; color:var(--text-main);">Aucune commande</h3>
                        <p style="color:#999; margin-bottom: 20px;">Vous n'avez pas encore succombé à nos produits.</p>
                        <a href="produits.php" class="btn-action">Découvrir la boutique</a>
                    </div>
                <?php else: ?>
                    
                    <div class="commandes-list">
                        <?php foreach(array_slice($commandes, 0, 3) as $cmd): ?>
                            <div class="commande-card">
                                <div class="commande-header">
                                    <h3>CMD #<?= $cmd['id'] ?></h3>
                                    <?php 
                                        $statusClass = 'badge-en_attente';
                                        if(strpos($cmd['statut'], 'confirm') !== false) $statusClass = 'badge-confirmee';
                                        if(strpos($cmd['statut'], 'expedi') !== false) $statusClass = 'badge-expediee';
                                    ?>
                                    <span class="badge <?= $statusClass ?>">
                                        <?= ucfirst(str_replace('_', ' ', $cmd['statut'])) ?>
                                    </span>
                                </div>
                                
                                <div class="commande-details">
                                    <div class="detail-item">
                                        <strong>Date</strong>
                                        <i class="far fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($cmd['date_commande'])) ?>
                                    </div>
                                    <div class="detail-item">
                                        <strong>Montant</strong>
                                        <?= number_format($cmd['total'], 2, ',', ' ') ?> DH
                                    </div>
                                    <div class="detail-item" style="text-align: right;">
                                        <a href="commande-details.php?id=<?= $cmd['id'] ?>" class="btn-small">
                                            Détails
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if(count($commandes) > 3): ?>
                            <div style="text-align: center; margin-top: 30px;">
                                <a href="mes-commandes.php" class="btn-action" style="background:transparent; color:var(--green-luxe); border:1px solid var(--green-luxe);">
                                    Voir tout l'historique
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>