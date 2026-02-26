<?php
// mes-commandes.php
session_start();
require_once 'config.php';

if(!isset($_SESSION['client_id'])) { header('Location: connexion.php'); exit(); }
$client_id = $_SESSION['client_id'];

try {
    if(!isset($pdo)) {
        $pdo = new PDO("mysql:host=localhost;dbname=sofcos_db;charset=utf8", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    // Récupération Client (pour la sidebar)
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch();

    // Récupération de TOUTES les commandes
    $cmdStmt = $pdo->prepare("SELECT * FROM commandes WHERE client_id = ? ORDER BY date_commande DESC");
    $cmdStmt->execute([$client_id]);
    $commandes = $cmdStmt->fetchAll();
    // garder la dernière commande pour affichage de statut en haut
    $lastCommande = $commandes[0] ?? null;
    // fonction utilitaire unique pour mapper statuts en classe+icone
    function statutBadge($statut) {
        $s = strtolower(trim($statut));
        $map = [
            'en attente'   => ['class'=>'en_attente','icon'=>'<i class="fas fa-clock"></i>'],
            'préparation'  => ['class'=>'preparation','icon'=>'<i class="fas fa-cog"></i>'],
            'expédié'      => ['class'=>'expedie','icon'=>'<i class="fas fa-truck"></i>'],
            'en livraison' => ['class'=>'en_livraison','icon'=>'<i class="fas fa-shipping-fast"></i>'],
            'livré'        => ['class'=>'livre','icon'=>'<i class="fas fa-check"></i>'],
            'annulé'       => ['class'=>'annule','icon'=>'<i class="fas fa-times"></i>'],
        ];
        foreach ($map as $key => $info) {
            if(strpos($s,$key)!==false) return $info;
        }
        return ['class'=>'en_attente','icon'=>'<i class="fas fa-clock"></i>'];
    }

} catch (PDOException $e) { die("Erreur : " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Commandes - SOFCOS</title>
    <link href="https://fonts.googleapis.com/css2?family=Prata&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- DESIGN SYSTEM LUXE (Identique à mon-compte.php) --- */
        :root {
            --primary: #1A3C34; --gold: #C5A059; --bg-light: #F9F7F2;
            --white: #ffffff; --text: #2c2c2c; --gray-light: #e5e5e5;
            --shadow: 0 10px 30px rgba(26, 60, 52, 0.08);
        }
        body { font-family: 'Montserrat', sans-serif; background-color: var(--bg-light); color: var(--text); margin: 0; }

        .dashboard-container { max-width: 1200px; margin: 40px auto; padding: 0 20px; display: grid; grid-template-columns: 280px 1fr; gap: 40px; }
        
        /* SIDEBAR */
        .sidebar { background: var(--white); border-radius: 8px; box-shadow: var(--shadow); padding: 30px 0; height: fit-content; }
        .user-profile-summary { text-align: center; padding-bottom: 20px; border-bottom: 1px solid var(--gray-light); margin-bottom: 10px; }
        .avatar-circle { width: 80px; height: 80px; border-radius: 50%; border: 2px solid var(--gold); color: var(--gold); display: flex; align-items: center; justify-content: center; font-size: 30px; margin: 0 auto 15px; }
        .user-name { font-family: 'Prata', serif; font-size: 18px; color: var(--primary); }
        .menu-list { list-style: none; padding: 0; margin: 0; }
        .menu-link { display: flex; align-items: center; gap: 15px; padding: 16px 30px; text-decoration: none; color: #666; font-weight: 500; font-size: 14px; transition: all 0.3s ease; border-left: 4px solid transparent; }
        .menu-link:hover, .menu-link.active { background: linear-gradient(90deg, rgba(197,160,89,0.1) 0%, rgba(255,255,255,0) 100%); color: var(--primary); border-left-color: var(--gold); }
        .menu-link.logout { color: #d63031; margin-top: 20px; border-top: 1px solid var(--gray-light); }
        .menu-link.logout:hover { background: #fff5f5; border-color: #d63031; }

        /* CONTENU */
        .section-box { background: var(--white); padding: 40px; border-radius: 8px; box-shadow: var(--shadow); }
        .section-header { margin-bottom: 30px; border-bottom: 1px solid var(--gray-light); padding-bottom: 15px; }
        .section-title { font-family: 'Prata', serif; font-size: 24px; color: var(--primary); margin: 0; }

        /* TABLE */
        .cmd-table { width: 100%; border-collapse: collapse; }
        .cmd-table th { text-align: left; padding: 15px; font-size: 11px; text-transform: uppercase; color: #999; border-bottom: 2px solid var(--bg-light); }
        .cmd-table td { padding: 18px 15px; border-bottom: 1px solid #f0f0f0; font-size: 14px; }
        .cmd-table tr:hover { background-color: #fafafa; }

        /* BADGES */
        .status-badge, .cmd-table .badge {
            padding: 6px 12px; border-radius: 30px; font-size: 11px; font-weight: 600; text-transform: uppercase; display: inline-flex; align-items: center; gap: 5px;
        }
        /* nouveaux statuts exemple */
        .status-badge.preparation { background:#fff3cd; color:#856404; }
        .status-badge.en_livraison { background:#d1ecf1; color:#0c5460; }
        .badge.en_attente { background: #FFF8E1; color: #F39C12; border: 1px solid #FFE082; }
        .badge.confirme { background: #E8F5E9; color: #2E7D32; border: 1px solid #A5D6A7; }
        .badge.expedie { background: #E3F2FD; color: #1565C0; border: 1px solid #90CAF9; }
        .badge.livre { background: #1A3C34; color: #C5A059; border: 1px solid #1A3C34; }
        .badge.annule { background: #FFEBEE; color: #C62828; border: 1px solid #EF9A9A; }

        .btn-action { padding: 8px 15px; background: white; border: 1px solid #ddd; border-radius: 4px; color: var(--text); text-decoration: none; font-size: 12px; font-weight: 600; transition: 0.3s; }
        .btn-action:hover { border-color: var(--gold); color: var(--gold); }

        @media (max-width: 900px) { .dashboard-container { grid-template-columns: 1fr; } .cmd-table { display: block; overflow-x: auto; } }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="dashboard-container">
        <div class="sidebar">
            <div class="user-profile-summary">
                <div class="avatar-circle"><?= strtoupper(substr($client['prenom'], 0, 1)) ?></div>
                <div class="user-name"><?= htmlspecialchars($client['prenom']) ?></div>
            </div>
            <ul class="menu-list">
                <li><a href="mon-compte.php" class="menu-link"><i class="fas fa-th-large"></i> Tableau de bord</a></li>
                <li><a href="mes-commandes.php" class="menu-link active"><i class="fas fa-box"></i> Mes commandes</a></li>
                <li><a href="mes-informations.php" class="menu-link"><i class="fas fa-user-edit"></i> Profil</a></li>
                <li><a href="mes-adresses.php" class="menu-link"><i class="fas fa-map-marker-alt"></i> Adresses</a></li>
                <li><a href="changer-mot-de-passe.php" class="menu-link"><i class="fas fa-lock"></i> Sécurité</a></li>
                <li><a href="deconnexion.php" class="menu-link logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="section-box">
                <div class="section-header">
                    <h2 class="section-title">Historique des commandes</h2>
                </div>
                <?php if($lastCommande): 
                    // calculer badge pour statut (fonction déjà déclarée plus haut)
                    $info = statutBadge($lastCommande['statut']);
                    $badgeClass = $info['class'];
                    $icon = $info['icon'];
                ?>
                <p style="margin-bottom:20px;">Votre dernière commande #<?= $lastCommande['id'] ?> est <span class="badge status-badge <?= $badgeClass ?>"><?= $icon ?> <?= ucfirst($lastCommande['statut']) ?></span></p>
                <?php endif; ?>
                
                <?php if(empty($commandes)): ?>
                    <div style="text-align:center; padding:50px;">
                        <i class="fas fa-shopping-basket" style="font-size:40px; color:#ddd; margin-bottom:20px;"></i>
                        <p>Vous n'avez pas encore passé de commande.</p>
                        <a href="produits.php" style="color:var(--gold); font-weight:600; text-decoration:none;">Découvrir la boutique</a>
                    </div>
                <?php else: ?>
                    <table class="cmd-table">
                        <thead>
                            <tr>
                                <th>Référence</th>
                                <th>Date</th>
                                <th>Montant</th>
                                <th>Détails</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($commandes as $cmd): 
                                // statutBadge déjà défini plus haut, on l'utilise directement
                                $info = statutBadge($cmd['statut']);
                                $cls = $info['class'];
                                $icon = $info['icon'];
                            ?> 
                            <tr>
                                <td><strong>#<?= $cmd['id'] ?></strong></td>
                                <td><?= date('d/m/Y', strtotime($cmd['date_commande'])) ?></td>
                                <td style="font-weight:700; color:var(--primary);"><?= number_format($cmd['total'], 2) ?> DH</td>
                                <td><a href="suivi.php?id=<?= $cmd['id'] ?>" class="btn-action">Voir</a></td>
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