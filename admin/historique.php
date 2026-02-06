<?php
session_start();
require_once '../config.php';
if(!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit(); }
$conn = $pdo;

// --- 1. GESTION DES FILTRES ---
$date_debut = isset($_GET['date_debut']) ? $_GET['date_debut'] : date('Y-m-01'); // 1er du mois par défaut
$date_fin   = isset($_GET['date_fin'])   ? $_GET['date_fin']   : date('Y-m-d');
$statut     = isset($_GET['statut'])     ? $_GET['statut']     : 'all';

// Construction de la requête SQL
$sql_cond = "WHERE statut IN ('livre', 'annule')";
$params = [];

if($date_debut && $date_fin) {
    $sql_cond .= " AND date_commande BETWEEN ? AND ?";
    $params[] = $date_debut . " 00:00:00";
    $params[] = $date_fin . " 23:59:59";
}

if($statut != 'all') {
    $sql_cond .= " AND statut = ?";
    $params[] = $statut;
}

// --- 2. EXPORT CSV (Si demandé) ---
if(isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="historique_commandes.csv"');
    $output = fopen('php://output', 'w');
    
    // En-têtes des colonnes Excel
    fputcsv($output, ['ID', 'Date', 'Client', 'Ville', 'Telephone', 'Livreur', 'Statut', 'Total (DH)']);
    
    // Récupération des données pour l'export
    $sql_export = "SELECT c.*, l.nom as nom_livreur 
                   FROM commandes c 
                   LEFT JOIN livreurs l ON c.livreur_id = l.id 
                   $sql_cond ORDER BY date_commande DESC";
    $stmt = $conn->prepare($sql_export);
    $stmt->execute($params);
    
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['id'], 
            $row['date_commande'], 
            $row['nom_client'], 
            $row['ville'], 
            $row['telephone'], 
            $row['nom_livreur'] ?? 'Aucun',
            $row['statut'],
            $row['total']
        ]);
    }
    fclose($output);
    exit();
}

// --- 3. REQUÊTE D'AFFICHAGE ---
$sql = "SELECT c.*, l.nom as nom_livreur 
        FROM commandes c 
        LEFT JOIN livreurs l ON c.livreur_id = l.id 
        $sql_cond 
        ORDER BY c.date_commande DESC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- 4. CALCULS STATISTIQUES (Sur la sélection) ---
$ca_total = 0;
$nb_livre = 0;
$nb_annule = 0;

foreach($commandes as $c) {
    if($c['statut'] == 'livre') {
        $ca_total += $c['total'];
        $nb_livre++;
    }
    if($c['statut'] == 'annule') {
        $nb_annule++;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Historique & Archives - SOFCOS</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #1A3C34; --gold: #C5A059; --bg-body: #f3f4f6; --sidebar-width: 270px; }
        body { font-family: 'Outfit', sans-serif; background: var(--bg-body); display: flex; margin: 0; }
        .main { margin-left: var(--sidebar-width); padding: 40px; width: 100%; box-sizing: border-box; }
        
        /* KPIS */
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); display: flex; align-items: center; justify-content: space-between; }
        .stat-val { font-size: 24px; font-weight: 700; color: var(--primary); }
        .stat-label { font-size: 13px; color: #6b7280; text-transform: uppercase; }
        
        /* FILTRES */
        .filter-bar { background: white; padding: 20px; border-radius: 12px; display: flex; gap: 15px; align-items: flex-end; margin-bottom: 25px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); flex-wrap: wrap; }
        .form-group { display: flex; flex-direction: column; gap: 5px; }
        label { font-size: 12px; font-weight: 600; color: #555; }
        input[type="date"], select { padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit; }
        
        .btn-filter { background: var(--primary); color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; height: 40px; }
        .btn-export { background: #10b981; color: white; text-decoration: none; padding: 10px 20px; border-radius: 6px; display: flex; align-items: center; gap: 8px; font-size: 14px; height: 38px; box-sizing: border-box;}

        /* TABLEAU */
        .card { background: white; border-radius: 12px; overflow: hidden; border: 1px solid #eee; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; background: #f9fafb; font-size: 12px; text-transform: uppercase; color: #6b7280; border-bottom: 1px solid #eee; }
        td { padding: 15px; border-bottom: 1px solid #f3f4f6; font-size: 14px; }
        
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
        .b-livre { background: #dcfce7; color: #166534; }
        .b-annule { background: #fee2e2; color: #991b1b; }

        .btn-view { color: #6b7280; background: #f3f4f6; width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; transition: 0.2s; }
        .btn-view:hover { background: var(--primary); color: white; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main">
        <h1 style="color:var(--primary); margin-bottom:5px;">Historique des Commandes</h1>
        <p style="color:#666; margin-bottom:30px;">Archives des commandes terminées (Livrées ou Annulées).</p>

        <div class="stats-grid">
            <div class="stat-card">
                <div>
                    <div class="stat-val"><?= number_format($ca_total, 2) ?> DH</div>
                    <div class="stat-label">Chiffre d'Affaires</div>
                </div>
                <i class="fas fa-coins" style="font-size:30px; color:#C5A059; opacity:0.3;"></i>
            </div>
            <div class="stat-card">
                <div>
                    <div class="stat-val" style="color:#166534;"><?= $nb_livre ?></div>
                    <div class="stat-label">Commandes Livrées</div>
                </div>
                <i class="fas fa-check-circle" style="font-size:30px; color:#166534; opacity:0.3;"></i>
            </div>
            <div class="stat-card">
                <div>
                    <div class="stat-val" style="color:#991b1b;"><?= $nb_annule ?></div>
                    <div class="stat-label">Commandes Annulées</div>
                </div>
                <i class="fas fa-times-circle" style="font-size:30px; color:#991b1b; opacity:0.3;"></i>
            </div>
        </div>

        <form method="GET" class="filter-bar">
            <div class="form-group">
                <label>Du :</label>
                <input type="date" name="date_debut" value="<?= $date_debut ?>">
            </div>
            <div class="form-group">
                <label>Au :</label>
                <input type="date" name="date_fin" value="<?= $date_fin ?>">
            </div>
            <div class="form-group">
                <label>Statut :</label>
                <select name="statut">
                    <option value="all" <?= $statut == 'all' ? 'selected' : '' ?>>Tout afficher</option>
                    <option value="livre" <?= $statut == 'livre' ? 'selected' : '' ?>>Livrées (Payées)</option>
                    <option value="annule" <?= $statut == 'annule' ? 'selected' : '' ?>>Annulées</option>
                </select>
            </div>
            <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Filtrer</button>
            
            <a href="?date_debut=<?= $date_debut ?>&date_fin=<?= $date_fin ?>&statut=<?= $statut ?>&export=1" class="btn-export">
                <i class="fas fa-file-csv"></i> Exporter CSV
            </a>
        </form>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Date</th>
                        <th>Client / Ville</th>
                        <th>Livreur</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($commandes) > 0): ?>
                        <?php foreach($commandes as $c): ?>
                        <tr>
                            <td style="font-weight:bold;">#<?= $c['id'] ?></td>
                            <td><?= date('d/m/Y', strtotime($c['date_commande'])) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($c['nom_client']) ?></strong><br>
                                <small style="color:#888;"><?= htmlspecialchars($c['ville']) ?></small>
                            </td>
                            <td>
                                <?php if($c['nom_livreur']): ?>
                                    <i class="fas fa-motorcycle" style="color:#ccc;"></i> <?= htmlspecialchars($c['nom_livreur']) ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td style="font-weight:700;"><?= number_format($c['total'], 2) ?> DH</td>
                            <td>
                                <?php if($c['statut'] == 'livre'): ?>
                                    <span class="badge b-livre">LIVRÉE</span>
                                <?php else: ?>
                                    <span class="badge b-annule">ANNULÉE</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:right;">
                                <a href="details_commande.php?id=<?= $c['id'] ?>" class="btn-view" title="Voir">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding:40px; color:#aaa;">
                                Aucun historique trouvé pour cette période.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>