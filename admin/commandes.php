<?php
session_start();
require_once '../config.php';

// Sécurité
if(!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit(); }

$conn = $pdo; // Alias pour $pdo

// REQUÊTE SQL (Optimisée pour tout récupérer)
$sql = "SELECT * FROM commandes ORDER BY date_commande DESC";
$commandes = $conn->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Commandes - SOFCOS</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- VARIABLES & RESET --- */
        :root { 
            --primary: #1A3C34; 
            --gold: #C5A059; 
            --bg-body: #f3f4f6; 
            --text-main: #374151;
            --sidebar-width: 270px;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Outfit', sans-serif; background: var(--bg-body); color: var(--text-main); display: flex; min-height: 100vh; }
        a { text-decoration: none; color: inherit; }

        /* --- SIDEBAR STYLE (Intégré pour que ça marche direct) --- */
        

        /* --- MAIN CONTENT --- */
        .main { margin-left: var(--sidebar-width); flex: 1; padding: 40px; width: calc(100% - var(--sidebar-width)); }
        
        /* HEADER DE PAGE */
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; }
        .page-title h1 { font-size: 26px; font-weight: 600; color: var(--primary); margin-bottom: 5px; }
        .page-title p { color: #6b7280; font-size: 14px; }
        
        .btn-print { background: white; color: var(--primary); border: 1px solid #e5e7eb; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 8px; transition: 0.3s; font-family: 'Outfit', sans-serif; box-shadow: 0 2px 5px rgba(0,0,0,0.03); }
        .btn-print:hover { background: var(--primary); color: white; border-color: var(--primary); }

        /* --- TABLE DESIGN --- */
        .card { background: white; border-radius: 16px; padding: 0; box-shadow: 0 10px 30px rgba(0,0,0,0.04); overflow: hidden; border: 1px solid #f0f0f0; }
        
        table { width: 100%; border-collapse: collapse; }
        thead { background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
        th { text-align: left; padding: 18px 25px; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 18px 25px; border-bottom: 1px solid #f3f4f6; font-size: 14px; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #fafafa; }

        /* STATUS BADGES */
        .badge { padding: 6px 14px; border-radius: 30px; font-size: 11px; font-weight: 700; text-transform: uppercase; display: inline-block; letter-spacing: 0.5px; }
        
        .st-attente { background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; }
        .st-valide  { background: #eff6ff; color: #1d4ed8; border: 1px solid #dbeafe; }
        .st-expedie { background: #e0e7ff; color: #4338ca; border: 1px solid #c7d2fe; }
        .st-livre   { background: #ecfccb; color: #365314; border: 1px solid #bef264; }
        .st-annule  { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }

        /* BUTTONS */
        .btn-action { width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; transition: 0.2s; background: #f3f4f6; color: #64748b; }
        .btn-action:hover { background: var(--primary); color: white; }

        /* Client Info Style */
        .client-info div { line-height: 1.4; }
        .client-name { font-weight: 600; color: var(--primary); }
        .client-email { font-size: 12px; color: #94a3b8; }

        /* IMPRESSION */
        @media print {
            .sidebar, .btn-print, .btn-action { display: none !important; }
            .main { margin: 0; padding: 0; width: 100%; }
            .card { box-shadow: none; border: 1px solid #000; }
            th, td { border: 1px solid #000; }
        }
    </style>
</head>
<body>
    
    <?php include 'sidebar.php'; ?>

    <main class="main">
        
        <header class="page-header">
            <div class="page-title">
                <h1>Commandes</h1>
                <p>Suivi et gestion des commandes clients</p>
            </div>
            <button onclick="window.print()" class="btn-print">
                <i class="fas fa-print"></i> Imprimer la liste
            </button>
        </header>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th width="80">N°</th>
                        <th>Client</th>
                        <th>Date</th>
                        <th>Montant Total</th>
                        <th>Statut</th>
                        <th style="text-align:right;">Détails</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($commandes) > 0): ?>
                        <?php foreach($commandes as $c): 
                            // Gestion des couleurs et labels
                            $statut_brut = strtolower($c['statut'] ?? 'en attente');
                            
                            $badge_class = 'st-attente'; // Défaut
                            if(strpos($statut_brut, 'valid') !== false || strpos($statut_brut, 'confirm') !== false) $badge_class = 'st-valide';
                            if(strpos($statut_brut, 'exped') !== false) $badge_class = 'st-expedie';
                            if(strpos($statut_brut, 'livr') !== false) $badge_class = 'st-livre';
                            if(strpos($statut_brut, 'annul') !== false) $badge_class = 'st-annule';

                            // Données sécurisées
                            $nom = !empty($c['nom_client']) ? $c['nom_client'] : 'Invité';
                            $email = !empty($c['email_client']) ? $c['email_client'] : '';
                            $total = isset($c['total']) ? $c['total'] : 0;
                        ?>
                        <tr>
                            <td style="font-weight:bold; color:#888;">#<?= $c['id'] ?></td>
                            <td class="client-info">
                                <div class="client-name"><?= htmlspecialchars($nom) ?></div>
                                <?php if($email): ?>
                                    <div class="client-email"><?= htmlspecialchars($email) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-weight:500;"><?= date('d/m/Y', strtotime($c['date_commande'])) ?></div>
                                <div style="font-size:11px; color:#aaa;"><?= date('H:i', strtotime($c['date_commande'])) ?></div>
                            </td>
                            <td style="font-weight:700; color:var(--primary); font-size:15px;">
                                <?= number_format($total, 2) ?> <small>DH</small>
                            </td>
                            <td>
                                <span class="badge <?= $badge_class ?>">
                                    <?= htmlspecialchars($c['statut']) ?>
                                </span>
                            </td>
                            <td style="text-align:right;">
                                <div style="display:flex; justify-content:flex-end;">
                                    <a href="details_commande.php?id=<?= $c['id'] ?>" class="btn-action" title="Voir les détails">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding: 40px; color:#888;">
                                <i class="fas fa-inbox" style="font-size:40px; margin-bottom:10px; opacity:0.3;"></i><br>
                                Aucune commande trouvée.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>

</body>
</html>