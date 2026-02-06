<?php
session_start();
require_once '../config.php';

// 1. SÉCURITÉ
if(!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit(); }
if(!isset($_GET['id'])) { header('Location: clients.php'); exit(); }

$id_client = (int)$_GET['id'];
$conn = isset($pdo) ? $pdo : (isset($conn) ? $conn : new PDO("mysql:host=localhost;dbname=sofcos_db", "root", ""));

// 2. RÉCUPÉRATION DU CLIENT
$stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$id_client]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$client) { die("Client introuvable."); }

// 3. RÉCUPÉRATION DE L'HISTORIQUE (AVEC CORRECTION EMAIL_CLIENT)
// On cherche par ID ou par EMAIL_CLIENT pour être sûr de tout trouver
$stmt_orders = $conn->prepare("SELECT * FROM commandes WHERE client_id = ? OR email_client = ? ORDER BY date_commande DESC");
$stmt_orders->execute([$id_client, $client['email']]);
$commandes = $stmt_orders->fetchAll(PDO::FETCH_ASSOC);

// 4. CALCULS STATISTIQUES (NOUVEAU : SÉPARATION PAYÉ / NON PAYÉ)
$total_paye = 0;      // Commandes Livrées
$total_attente = 0;   // Commandes en cours (Non livrées)
$nb_commandes = count($commandes);

foreach($commandes as $c) {
    // On sécurise la récupération du montant
    $montant = isset($c['montant_total']) ? $c['montant_total'] : ($c['total'] ?? 0);
    $statut = strtolower($c['statut']); // On met en minuscule pour comparer facilement

    // Si annulé, on ignore tout
    if(strpos($statut, 'annul') !== false) {
        continue;
    }

    // Si le statut contient "livr" (ex: "livrée", "livré"), c'est payé
    if(strpos($statut, 'livr') !== false) {
        $total_paye += $montant;
    } 
    // Sinon (en attente, confirmé, expédié...), c'est en attente de paiement
    else {
        $total_attente += $montant;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails Client - SOFCOS Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --primary: #1A3C34; --bg-body: #f3f4f6; --sidebar-width: 270px; }
        body { font-family: 'Outfit', sans-serif; background: var(--bg-body); color: #444; display: flex; margin:0; }
        
        .sidebar { width: var(--sidebar-width); background: var(--primary); color: white; position: fixed; height: 100vh; z-index: 100; }
        .main { margin-left: var(--sidebar-width); padding: 40px; width: 100%; box-sizing: border-box; }
        
        .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .btn-back { text-decoration: none; color: #666; font-weight: 500; display: inline-flex; align-items: center; gap: 8px; }
        .btn-back:hover { color: var(--primary); }

        .client-header { background: white; padding: 30px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.04); display: flex; align-items: center; gap: 20px; margin-bottom: 30px; }
        .avatar-lg { width: 80px; height: 80px; background: #f0fdf4; color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: bold; border: 2px solid #dcfce7; }
        
        /* GRILLE DE 4 CARTES */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 16px; box-shadow: 0 5px 15px rgba(0,0,0,0.03); text-align: center; }
        .stat-val { font-size: 24px; font-weight: 700; color: var(--primary); margin: 10px 0; }
        .stat-label { font-size: 13px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; }
        
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; background: #f8fafc; color: #64748b; font-size: 12px; text-transform: uppercase; font-weight: 600; }
        td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        
        .card-table { background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.04); }
        
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .badge-en_attente { background: #fff7ed; color: #c2410c; }
        .badge-livree { background: #dcfce7; color: #166534; }
        .badge-annulee { background: #fee2e2; color: #991b1b; }
        
        .btn-view { color: #64748b; text-decoration: none; font-size: 16px; }
        .btn-view:hover { color: var(--primary); }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main">
        <div class="header-bar">
            <a href="clients.php" class="btn-back"><i class="fas fa-arrow-left"></i> Retour aux clients</a>
        </div>

        <div class="client-header">
            <div class="avatar-lg">
                <?= strtoupper(substr($client['nom'],0,1)) ?>
            </div>
            <div>
                <h1 style="margin:0; font-size:24px; color:#1A3C34;"><?= htmlspecialchars($client['prenom'] . ' ' . $client['nom']) ?></h1>
                <p style="margin:5px 0 0 0; color:#666;">
                    <i class="fas fa-envelope"></i> <?= htmlspecialchars($client['email']) ?> &nbsp;|&nbsp; 
                    <i class="fas fa-phone"></i> <?= htmlspecialchars($client['telephone']) ?> &nbsp;|&nbsp; 
                    <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($client['ville']) ?>
                </p>
                <p style="margin-top:5px;">
                    Statut : 
                    <?php if($client['actif']): ?>
                        <span style="color:#166534; font-weight:bold; font-size:13px;">Actif</span>
                    <?php else: ?>
                        <span style="color:#991b1b; font-weight:bold; font-size:13px;">Bloqué</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-shopping-bag" style="color:#C5A059; font-size:24px;"></i>
                <div class="stat-val"><?= $nb_commandes ?></div>
                <div class="stat-label">Commandes Totales</div>
            </div>

            <div class="stat-card">
                <i class="fas fa-check-circle" style="color:#10b981; font-size:24px;"></i>
                <div class="stat-val" style="color:#10b981;">
                    <?= number_format($total_paye, 2) ?> <span style="font-size:14px;">DH</span>
                </div>
                <div class="stat-label">Total Payé (Livrées)</div>
            </div>

            <div class="stat-card">
                <i class="fas fa-clock" style="color:#f59e0b; font-size:24px;"></i>
                <div class="stat-val" style="color:#f59e0b;">
                    <?= number_format($total_attente, 2) ?> <span style="font-size:14px;">DH</span>
                </div>
                <div class="stat-label">En cours (Non payées)</div>
            </div>

            <div class="stat-card">
                <i class="fas fa-calendar-alt" style="color:#3b82f6; font-size:24px;"></i>
                <div class="stat-val" style="font-size:20px; margin-top:14px;">
                    <?= date('d/m/Y', strtotime($client['date_inscription'])) ?>
                </div>
                <div class="stat-label">Client depuis le</div>
            </div>
        </div>

        <h3 style="color:#1A3C34; margin-bottom:15px;">Historique des Commandes</h3>
        <div class="card-table">
            <?php if(count($commandes) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>N° Commande</th>
                        <th>Date</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th style="text-align:right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($commandes as $c): 
                        // On sécurise l'affichage du montant
                        $montant = isset($c['montant_total']) ? $c['montant_total'] : ($c['total'] ?? 0);
                        
                        // Style badge
                        $st = strtolower($c['statut']);
                        $cls = 'badge-en_attente';
                        if(strpos($st, 'livr')!==false) $cls = 'badge-livree';
                        if(strpos($st, 'annul')!==false) $cls = 'badge-annulee';
                    ?>
                    <tr>
                        <td style="font-weight:bold;">#<?= $c['id'] ?></td>
                        <td><?= date('d/m/Y', strtotime($c['date_commande'])) ?></td>
                        <td style="font-weight:600; color:#1A3C34;"><?= number_format($montant, 2) ?> DH</td>
                        <td>
                            <span class="badge <?= $cls ?>"><?= htmlspecialchars($c['statut']) ?></span>
                        </td>
                        <td style="text-align:right;">
                            <a href="details_commande.php?id=<?= $c['id'] ?>" class="btn-view" title="Voir détails">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div style="text-align:center; padding:30px; color:#888;">
                    <i class="fas fa-shopping-basket" style="font-size:30px; margin-bottom:10px; color:#ddd;"></i><br>
                    Aucune commande trouvée pour ce client (ID ou Email).
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>