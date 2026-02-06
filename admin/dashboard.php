<?php
session_start();
require_once '../config.php';

// ========== SÉCURITÉ ==========
if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$conn = $pdo;

// ========== API AJAX POUR LE GRAPHIQUE ==========
if(isset($_GET['ajax_chart'])) {
    header('Content-Type: application/json');
    
    $filter = $_GET['ajax_chart'];
    
    // Récupération des dates (ou valeurs par défaut : 7 derniers jours)
    $start_date = !empty($_GET['start']) ? $_GET['start'] : date('Y-m-d', strtotime('-6 days'));
    $end_date   = !empty($_GET['end'])   ? $_GET['end']   : date('Y-m-d');

    $sql_start = $start_date . " 00:00:00";
    $sql_end   = $end_date   . " 23:59:59";
    
    $data = [
        'labels' => [], 
        'values' => [], 
        'type' => 'line', 
        'label' => 'Données', 
        'multiple' => false,
        'color' => '#10b981'
    ];

    if($filter == 'evolution') {
        // --- 1. Évolution CA (UNIQUEMENT LIVRÉ) ---
        $period = new DatePeriod(
            new DateTime($start_date),
            new DateInterval('P1D'),
            new DateTime($end_date . ' +1 day')
        );

        foreach ($period as $dt) {
            $current_date = $dt->format('Y-m-d');
            $data['labels'][] = $dt->format('d/m');
            
            // MODIFICATION ICI : statut LIKE '%Livr%'
            $sql = "SELECT COALESCE(SUM(total), 0) FROM commandes 
                    WHERE DATE(date_commande) = '$current_date' 
                    AND statut LIKE '%Livr%'"; 
            $data['values'][] = $conn->query($sql)->fetchColumn();
        }
        $data['type'] = 'line';
        $data['label'] = 'Chiffre d\'Affaires Livré (DH)';

    } elseif($filter == 'revenue_brand') {
        // --- 2. CA par Marque (UNIQUEMENT LIVRÉ) ---
        $sql = "SELECT p.marque, SUM(cd.quantite * cd.prix_unitaire) as ca_total 
                FROM commandes_details cd 
                JOIN produits p ON cd.produit_id = p.id 
                JOIN commandes c ON cd.commande_id = c.id
                WHERE c.statut LIKE '%Livr%' AND p.marque != ''
                AND c.date_commande BETWEEN '$sql_start' AND '$sql_end'
                GROUP BY p.marque 
                ORDER BY ca_total DESC 
                LIMIT 10";
        
        try {
            $res = $conn->query($sql)->fetchAll();
            if(count($res) > 0) {
                foreach($res as $row) {
                    $data['labels'][] = $row['marque'];
                    $data['values'][] = $row['ca_total'];
                }
            } else {
                $data['labels'][] = "Aucune vente livrée";
                $data['values'][] = 0;
            }
        } catch(Exception $e) { $data['error'] = "Erreur SQL"; }

        $data['type'] = 'bar';
        $data['label'] = 'CA Livré par Marque (DH)';
        $data['color'] = '#8b5cf6';

    } elseif($filter == 'revenue_category') {
        // --- 3. CA par Catégorie (UNIQUEMENT LIVRÉ) ---
        $sql = "SELECT cat.nom, SUM(cd.quantite * cd.prix_unitaire) as ca_total 
                FROM commandes_details cd 
                JOIN produits p ON cd.produit_id = p.id 
                JOIN categories cat ON p.categorie_id = cat.id
                JOIN commandes c ON cd.commande_id = c.id
                WHERE c.statut LIKE '%Livr%'
                AND c.date_commande BETWEEN '$sql_start' AND '$sql_end'
                GROUP BY cat.nom 
                ORDER BY ca_total DESC 
                LIMIT 10";

        try {
            $res = $conn->query($sql)->fetchAll();
            if(count($res) > 0) {
                foreach($res as $row) {
                    $data['labels'][] = $row['nom'];
                    $data['values'][] = $row['ca_total'];
                }
            } else {
                $data['labels'][] = "Aucune vente livrée";
                $data['values'][] = 0;
            }
        } catch(Exception $e) { $data['error'] = "Erreur SQL"; }

        $data['type'] = 'bar';
        $data['label'] = 'CA Livré par Catégorie (DH)';
        $data['color'] = '#0ea5e9';

    } elseif($filter == 'orders_status') {
        // --- 4. Suivi Commandes (Inchangé pour voir le global) ---
        $data['multiple'] = true;
        $data['type'] = 'line';
        $data['val_total'] = []; $data['val_livre'] = []; $data['val_annule'] = [];

        $period = new DatePeriod(new DateTime($start_date), new DateInterval('P1D'), new DateTime($end_date . ' +1 day'));

        foreach ($period as $dt) {
            $d = $dt->format('Y-m-d');
            $data['labels'][] = $dt->format('d/m');
            $data['val_total'][]  = $conn->query("SELECT COUNT(*) FROM commandes WHERE DATE(date_commande)='$d'")->fetchColumn();
            $data['val_livre'][]  = $conn->query("SELECT COUNT(*) FROM commandes WHERE DATE(date_commande)='$d' AND statut LIKE '%Livr%'")->fetchColumn();
            $data['val_annule'][] = $conn->query("SELECT COUNT(*) FROM commandes WHERE DATE(date_commande)='$d' AND statut LIKE '%annul%'")->fetchColumn();
        }

    } elseif($filter == 'bestsellers') {
        // --- 5. Best Sellers (Global ou Livré ? Je laisse Global ici pour voir la tendance de commande) ---
        // Si vous voulez seulement les best sellers livrés, ajoutez "AND c.statut LIKE '%Livr%'"
        $sql = "SELECT p.nom, SUM(cd.quantite) as total_qty 
                FROM commandes_details cd 
                JOIN produits p ON cd.produit_id = p.id 
                JOIN commandes c ON cd.commande_id = c.id
                WHERE c.date_commande BETWEEN '$sql_start' AND '$sql_end'
                GROUP BY p.id 
                ORDER BY total_qty DESC 
                LIMIT 5";
        try {
            $res = $conn->query($sql)->fetchAll();
            if(count($res) > 0) {
                foreach($res as $row) {
                    $data['labels'][] = substr($row['nom'], 0, 15) . '...';
                    $data['values'][] = $row['total_qty'];
                }
            } else {
                $data['labels'][] = "Aucune vente";
                $data['values'][] = 0;
            }
        } catch(Exception $e) {}
        
        $data['type'] = 'bar';
        $data['label'] = 'Unités commandées';
        $data['color'] = '#C5A059';

    } elseif($filter == 'brands_count') {
        $sql = "SELECT marque, COUNT(*) as nb FROM produits WHERE marque != '' AND actif = 1 GROUP BY marque ORDER BY nb DESC LIMIT 7";
        $res = $conn->query($sql)->fetchAll();
        foreach($res as $row) {
            $data['labels'][] = $row['marque'];
            $data['values'][] = $row['nb'];
        }
        $data['type'] = 'doughnut';
        $data['label'] = 'Nombre de produits';
    }

    echo json_encode($data);
    exit;
}

// ========== REQUÊTES PAGE STANDARD (KPI) ==========

// MODIFICATION ICI : On ne compte que les statuts 'Livré'
$ca = $conn->query("SELECT COALESCE(SUM(total), 0) FROM commandes WHERE statut LIKE '%Livr%'")->fetchColumn();

// KPI Commandes : On peut garder le total ou mettre seulement les livrées. 
// Ici je laisse le total des commandes pour voir le volume de travail.
$nb_cmd = $conn->query("SELECT COUNT(*) FROM commandes")->fetchColumn();
$nb_clt = $conn->query("SELECT COUNT(*) FROM clients")->fetchColumn();
$nb_prd = $conn->query("SELECT COUNT(*) FROM produits WHERE actif = 1")->fetchColumn();

$commandes = $conn->query("SELECT c.*, cl.nom, cl.prenom FROM commandes c LEFT JOIN clients cl ON c.client_id = cl.id ORDER BY c.date_commande DESC LIMIT 5")->fetchAll();
$stock_alert = $conn->query("SELECT * FROM produits WHERE stock < 5 ORDER BY stock ASC LIMIT 4")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard SOFCOS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --sidebar-bg: #1A3C34;
            --accent: #10b981;
            --gold: #C5A059;
            --bg-body: #f6fcf8;
            --white: #ffffff;
            --text-dark: #1f2937;
            --text-grey: #6b7280;
            --danger: #ef4444;
            --success: #059669;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Open Sans', sans-serif; background: var(--bg-body); color: var(--text-dark); display: flex; min-height: 100vh; }
        a { text-decoration: none; color: inherit; }
        h1, h2, h3 { font-family: 'Poppins', sans-serif; }

        .main { margin-left: 260px; flex: 1; padding: 30px; width: calc(100% - 260px); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; }
        .page-title { font-size: 26px; font-weight: 600; color: var(--sidebar-bg); }
        .btn-action { background: var(--white); border: 1px solid #e5e7eb; padding: 10px 20px; border-radius: 50px; color: var(--sidebar-bg); font-weight: 600; font-size: 13px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }

        /* KPI */
        .grid-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 25px; margin-bottom: 30px; }
        @media(max-width: 1100px) { .grid-stats { grid-template-columns: 1fr 1fr; } }
        .stat-card { background: var(--white); padding: 25px; border-radius: 16px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05); display: flex; flex-direction: column; height: 150px; }
        .stat-head { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .stat-title { color: var(--text-grey); font-size: 12px; font-weight: 700; text-transform: uppercase; }
        .stat-icon { width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .stat-value { font-size: 32px; font-weight: 700; color: var(--sidebar-bg); margin-top: auto; }
        
        .c-gold .stat-icon { background: #fef3c7; color: var(--gold); }
        .c-green .stat-icon { background: #d1fae5; color: var(--success); }
        .c-blue .stat-icon { background: #e0f2fe; color: #0284c7; }
        .c-dark .stat-icon { background: #f3f4f6; color: var(--sidebar-bg); }

        /* CONTENT */
        .grid-content { display: grid; grid-template-columns: 2fr 1fr; gap: 25px; }
        @media(max-width: 1000px) { .grid-content { grid-template-columns: 1fr; } }
        .panel { background: var(--white); border-radius: 16px; padding: 25px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05); margin-bottom: 25px; border: 1px solid #f0f0f0; }
        
        /* FILTRES HEADER */
        .panel-header { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f3f4f6; gap: 10px; }
        .panel-title { font-size: 18px; font-weight: 600; color: var(--sidebar-bg); margin-right: auto; }
        
        .filters-group { display: flex; gap: 10px; align-items: center; }
        .chart-filter, .date-filter { padding: 8px 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 13px; background: #f9fafb; outline: none; cursor: pointer; color: var(--text-dark); }
        .chart-filter:focus, .date-filter:focus { border-color: var(--accent); background: #fff; }

        /* TABLES & STOCK */
        .clean-table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        .clean-table th { text-align: left; padding: 0 15px; color: var(--text-grey); font-size: 12px; text-transform: uppercase; }
        .clean-table td { padding: 15px; background: #fafafa; border-top: 1px solid #eee; border-bottom: 1px solid #eee; font-size: 14px; }
        .clean-table tr td:first-child { border-left: 1px solid #eee; border-radius: 8px 0 0 8px; }
        .clean-table tr td:last-child { border-right: 1px solid #eee; border-radius: 0 8px 8px 0; }
        
        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; }
        .st-ok { background: #d1fae5; color: #065f46; }
        .st-wait { background: #fef3c7; color: #92400e; }
        .st-ko { background: #fee2e2; color: #991b1b; }

        .stock-item { display: flex; align-items: center; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid #f3f4f6; }
        .stock-box { width: 40px; height: 40px; background: #f0fdf4; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--sidebar-bg); }
        .stock-qty { font-weight: 700; color: var(--danger); background: #fee2e2; padding: 4px 10px; border-radius: 8px; font-size: 13px;}
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="main">
        <header class="header">
            <div>
                <h1 class="page-title">Tableau de bord</h1>
                <p style="color:var(--text-grey); font-size:14px; margin-top:5px;">Aperçu de l'activité commerciale.</p>
            </div>
            <a href="../index.php" target="_blank" class="btn-action">
                <i class="fas fa-globe"></i> Voir la boutique
            </a>
        </header>

        <div class="grid-stats">
            <div class="stat-card c-gold">
                <div class="stat-head"><span class="stat-title">Revenus (Livrés)</span><div class="stat-icon"><i class="fas fa-coins"></i></div></div>
                <div class="stat-value"><?= number_format($ca, 2) ?> <span style="font-size:16px;">DH</span></div>
            </div>
            <div class="stat-card c-green">
                <div class="stat-head"><span class="stat-title">Commandes</span><div class="stat-icon"><i class="fas fa-shopping-bag"></i></div></div>
                <div class="stat-value"><?= $nb_cmd ?></div>
            </div>
            <div class="stat-card c-blue">
                <div class="stat-head"><span class="stat-title">Clients</span><div class="stat-icon"><i class="fas fa-user-friends"></i></div></div>
                <div class="stat-value"><?= $nb_clt ?></div>
            </div>
            <div class="stat-card c-dark">
                <div class="stat-head"><span class="stat-title">Produits</span><div class="stat-icon"><i class="fas fa-pump-soap"></i></div></div>
                <div class="stat-value"><?= $nb_prd ?></div>
            </div>
        </div>

        <div class="grid-content">
            <div class="col-left">
                <div class="panel">
                    <div class="panel-header">
                        <h3 class="panel-title">Analyses</h3>
                        
                        <div class="filters-group">
                            <input type="date" id="dateStart" class="date-filter" value="<?= date('Y-m-d', strtotime('-6 days')) ?>" onchange="refreshChart()">
                            <span style="font-size:12px; color:#aaa;">à</span>
                            <input type="date" id="dateEnd" class="date-filter" value="<?= date('Y-m-d') ?>" onchange="refreshChart()">

                            <select id="chartFilter" class="chart-filter" onchange="refreshChart()">
                                <option value="evolution">Évolution CA (Livré)</option>
                                <option value="revenue_brand"> CA par Marque (Livré)</option>
                                <option value="revenue_category"> CA par Catégorie (Livré)</option>
                                <option value="orders_status"> Suivi Commandes</option>
                                <option value="bestsellers"> Best Sellers</option>
                                <option value="brands_count"> Stock par Marque</option>
                            </select>
                        </div>
                    </div>
                    <div style="height: 300px; position: relative;">
                        <canvas id="natureChart"></canvas>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-header">
                        <h3 class="panel-title">Dernières Commandes</h3>
                        <a href="commandes.php" style="color:var(--success); font-weight:600; font-size:13px;">Voir tout</a>
                    </div>
                    <table class="clean-table">
                        <thead>
                            <tr><th>Client</th><th>Date</th><th>Total</th><th>État</th><th></th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($commandes as $c): 
                                $st = strtolower($c['statut']);
                                $cls = (strpos($st, 'livr')!==false || strpos($st, 'valid')!==false) ? 'st-ok' : ((strpos($st, 'annul')!==false) ? 'st-ko' : 'st-wait');
                            ?>
                            <tr>
                                <td style="font-weight:600;"><?= htmlspecialchars($c['nom_client']) ?></td>
                                <td><?= date('d/m', strtotime($c['date_commande'])) ?></td>
                                <td><?= number_format($c['total'], 2) ?></td>
                                <td><span class="status-badge <?= $cls ?>"><?= $c['statut'] ?></span></td>
                                <td><a href="commandes.php" style="color:#1A3C34;"><i class="fas fa-eye"></i></a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-right">
                <div class="panel">
                    <div class="panel-header"><h3 class="panel-title" style="color:var(--danger);">Stocks Critiques</h3></div>
                    <?php if(count($stock_alert) > 0): ?>
                        <?php foreach($stock_alert as $prod): ?>
                        <div class="stock-item">
                            <div style="display:flex; gap:12px; align-items:center;">
                                <div class="stock-box"><i class="fas fa-box"></i></div>
                                <div><div style="font-size:14px; font-weight:600;"><?= htmlspecialchars($prod['nom']) ?></div></div>
                            </div>
                            <div class="stock-qty"><?= $prod['stock'] ?></div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align:center; padding:20px; color:var(--success);">
                            <i class="fas fa-check-circle" style="font-size:30px; margin-bottom:10px;"></i>
                            <p>Stocks Impeccables</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="panel">
                    <div class="panel-header"><h3 class="panel-title">Raccourcis</h3></div>
                    <div style="display:flex; flex-direction:column; gap:12px;">
                        <a href="produits_ajout.php" style="padding:15px; background:#f0fdf4; border:1px solid #dcfce7; border-radius:12px; display:flex; align-items:center; gap:12px; font-weight:600; color:#166534;">
                            <div style="width:30px; height:30px; background:white; border-radius:50%; display:flex; align-items:center; justify-content:center;"><i class="fas fa-plus"></i></div>
                            Ajouter un produit
                        </a>
                        <a href="clients.php" style="padding:15px; background:#f3f4f6; border-radius:12px; display:flex; align-items:center; gap:12px; font-weight:600; color:#4b5563;">
                            <div style="width:30px; height:30px; background:white; border-radius:50%; display:flex; align-items:center; justify-content:center;"><i class="fas fa-search"></i></div>
                            Rechercher un client
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        let myChart = null;
        const ctx = document.getElementById('natureChart').getContext('2d');
        const palette = ['#1A3C34', '#10b981', '#C5A059', '#3b82f6', '#ef4444', '#f59e0b', '#8b5cf6'];

        function getGradient() {
            const gradient = ctx.createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, 'rgba(16, 185, 129, 0.4)');
            gradient.addColorStop(1, 'rgba(16, 185, 129, 0)');
            return gradient;
        }

        function refreshChart() {
            const filterType = document.getElementById('chartFilter').value;
            const start = document.getElementById('dateStart').value;
            const end = document.getElementById('dateEnd').value;

            // On envoie les dates dans l'URL
            fetch(`dashboard.php?ajax_chart=${filterType}&start=${start}&end=${end}`)
                .then(response => response.json())
                .then(data => {
                    if(data.error) { alert(data.error); return; }
                    if(myChart) { myChart.destroy(); }

                    let finalDatasets = [];

                    if(data.multiple === true) {
                        finalDatasets = [
                            { label: 'Total Commandes', data: data.val_total, borderColor: '#3b82f6', backgroundColor: 'rgba(59, 130, 246, 0.1)', tension: 0.3, fill: false },
                            { label: 'Livrées', data: data.val_livre, borderColor: '#10b981', backgroundColor: 'rgba(16, 185, 129, 0.1)', tension: 0.3, fill: false },
                            { label: 'Annulées', data: data.val_annule, borderColor: '#ef4444', backgroundColor: 'rgba(239, 68, 68, 0.1)', tension: 0.3, fill: false }
                        ];
                    } else {
                        let datasetConfig = {};
                        if(data.type === 'line') {
                            datasetConfig = {
                                label: data.label,
                                data: data.values,
                                borderColor: data.color || '#10b981',
                                backgroundColor: getGradient(),
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4,
                                pointRadius: 4,
                                pointBackgroundColor: '#fff'
                            };
                        } else if (data.type === 'bar') {
                            datasetConfig = {
                                label: data.label,
                                data: data.values,
                                backgroundColor: data.color || '#C5A059',
                                borderRadius: 6,
                                barPercentage: 0.6
                            };
                        } else if (data.type === 'doughnut') {
                            datasetConfig = {
                                label: data.label,
                                data: data.values,
                                backgroundColor: palette,
                                borderWidth: 0,
                                hoverOffset: 10
                            };
                        }
                        finalDatasets = [datasetConfig];
                    }

                    myChart = new Chart(ctx, {
                        type: data.type,
                        data: {
                            labels: data.labels,
                            datasets: finalDatasets
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { 
                                    display: (data.type === 'doughnut' || data.multiple === true), 
                                    position: 'bottom'
                                }
                            },
                            scales: {
                                y: {
                                    display: data.type !== 'doughnut',
                                    beginAtZero: true,
                                    grid: { borderDash: [5, 5], color: '#e5e7eb' }
                                },
                                x: {
                                    display: data.type !== 'doughnut',
                                    grid: { display: false }
                                }
                            }
                        }
                    });
                })
                .catch(err => console.error('Erreur:', err));
        }

        // Lancer au chargement
        refreshChart();
    </script>
</body>
</html>