<?php
session_start();
require_once '../config.php';
if(!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit(); }
$conn = $pdo;

// --- 1. ASSIGNER UN LIVREUR ---
if(isset($_POST['assign_driver'])) {
    $id_cmd = (int)$_POST['id_cmd'];
    $id_livreur = (int)$_POST['livreur_id'];
    $conn->prepare("UPDATE commandes SET livreur_id = ?, statut = 'expedie' WHERE id = ?")->execute([$id_livreur, $id_cmd]);
}

// --- 2. VALIDER LA LIVRAISON (ENCAISSEMENT) ---
if(isset($_POST['validate_delivery'])) {
    $id_cmd = (int)$_POST['id_cmd'];
    $conn->prepare("UPDATE commandes SET statut = 'livre', statut_paiement = 'paye' WHERE id = ?")->execute([$id_cmd]);
}

// --- 3. NOUVEAU : MARQUER LE RAPPORT COMME TRAITÉ ---
if(isset($_POST['resolve_report'])) {
    $id_cmd = (int)$_POST['id_cmd'];
    // On vide le champ 'rapport_livreur' pour faire disparaitre l'alerte
    $conn->prepare("UPDATE commandes SET rapport_livreur = NULL WHERE id = ?")->execute([$id_cmd]);
}

// --- DONNÉES ---
$livreurs = $conn->query("SELECT * FROM livreurs WHERE actif=1 ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);

$sql = "SELECT c.*, l.nom as nom_livreur 
        FROM commandes c 
        LEFT JOIN livreurs l ON c.livreur_id = l.id
        WHERE c.statut IN ('confirme', 'expedie') 
        ORDER BY c.date_commande DESC";
$livraisons = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$nb_a_assigner = $conn->query("SELECT COUNT(*) FROM commandes WHERE statut='confirme' AND livreur_id IS NULL")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Suivi Livraisons - SOFCOS</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #1A3C34; --gold: #C5A059; --bg-body: #f3f4f6; --sidebar-width: 270px; }
        body { font-family: 'Outfit', sans-serif; background: var(--bg-body); display: flex; margin: 0; }
        .main { margin-left: var(--sidebar-width); padding: 40px; width: 100%; box-sizing: border-box; }
        
        .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-title h1 { margin: 0; color: var(--primary); font-size: 24px; }
        .btn-team { background: white; color: var(--primary); padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; border: 1px solid #ddd; transition:0.2s; }
        .btn-team:hover { background: #f9fafb; border-color: var(--primary); }

        .card { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); overflow: hidden; border: 1px solid #eee; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 18px 20px; background: #f9fafb; font-size: 12px; text-transform: uppercase; color: #6b7280; font-weight: 600; border-bottom: 1px solid #eee; }
        td { padding: 18px 20px; border-bottom: 1px solid #f3f4f6; vertical-align: top; font-size: 14px; color: #374151; }
        
        /* Select & Buttons */
        .select-driver { padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; background: #fff; font-family: inherit; font-size: 13px; outline: none; width: 150px; }
        .btn-go { background: var(--gold); color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; margin-left: 5px; }
        .driver-badge { background: #eff6ff; color: #1e40af; padding: 5px 12px; border-radius: 20px; font-weight: 600; font-size: 12px; display: inline-flex; align-items: center; gap: 6px; border: 1px solid #dbeafe; }
        .btn-validate { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; }

        /* --- STYLE ALERTE AMÉLIORÉ --- */
        .alert-livreur {
            margin-top: 10px; 
            background: #fef2f2; 
            border: 1px solid #fecaca; 
            border-radius: 8px; 
            padding: 10px; 
            animation: pulse 2s infinite;
        }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.2); } 70% { box-shadow: 0 0 0 5px rgba(220, 38, 38, 0); } 100% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0); } }
        
        .alert-content { display: flex; gap: 8px; font-size: 13px; color: #991b1b; }
        .alert-actions { margin-top: 8px; text-align: right; }
        
        .btn-resolve {
            background: white; border: 1px solid #fca5a5; color: #b91c1c;
            padding: 4px 10px; border-radius: 4px; font-size: 11px; cursor: pointer;
            font-weight: 600; text-transform: uppercase;
        }
        .btn-resolve:hover { background: #b91c1c; color: white; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main">
        <div class="header-bar">
            <div class="page-title">
                <h1>Dispatching Livraisons</h1>
                <p>
                    <?php if($nb_a_assigner > 0): ?>
                        <span style="color:#e11d48; font-weight:bold;"><?= $nb_a_assigner ?></span> commandes en attente d'affectation.
                    <?php else: ?>
                        Tout est assigné.
                    <?php endif; ?>
                </p>
            </div>
            <a href="equipe_livreurs.php" class="btn-team"><i class="fas fa-users"></i> Gérer l'équipe</a>
        </div>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th width="20%">Commande</th>
                        <th width="35%">Destination</th>
                        <th width="30%">Livreur</th>
                        <th width="15%" style="text-align:right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($livraisons as $c): ?>
                    <tr>
                        <td>
                            <div style="font-weight:700; color:var(--primary);">#<?= $c['id'] ?></div>
                            <div style="font-size:12px; color:#999;"><?= date('d/m - H:i', strtotime($c['date_commande'])) ?></div>
                            <div style="margin-top:5px; font-weight:600; color:var(--gold);"><?= number_format($c['total'], 2) ?> DH</div>
                        </td>

                        <td>
                            <div style="font-weight:700;"><?= htmlspecialchars($c['ville']) ?></div>
                            <div style="color:#555;"><?= htmlspecialchars($c['nom_client']) ?></div>
                            <div style="font-size:12px; color:#777; margin-bottom:5px;"><?= htmlspecialchars($c['adresse']) ?></div>
                            
                            <?php if(!empty($c['rapport_livreur'])): ?>
                                <div class="alert-livreur">
                                    <div class="alert-content">
                                        <i class="fas fa-bullhorn"></i>
                                        <div>
                                            <strong>Message Livreur :</strong><br>
                                            "<?= htmlspecialchars($c['rapport_livreur']) ?>"
                                        </div>
                                    </div>
                                    <form method="POST" class="alert-actions">
                                        <input type="hidden" name="id_cmd" value="<?= $c['id'] ?>">
                                        <button type="submit" name="resolve_report" class="btn-resolve" title="J'ai géré le problème, effacer le message">
                                            <i class="fas fa-check"></i> Traité
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </td>
                        
                        <td>
                            <?php if($c['livreur_id']): ?>
                                <div class="driver-badge">
                                    <i class="fas fa-motorcycle"></i> <?= htmlspecialchars($c['nom_livreur']) ?>
                                </div>
                            <?php else: ?>
                                <form method="POST" style="display:flex; align-items:center;">
                                    <input type="hidden" name="id_cmd" value="<?= $c['id'] ?>">
                                    <select name="livreur_id" class="select-driver" required>
                                        <option value="">Choisir...</option>
                                        <?php foreach($livreurs as $l): ?>
                                            <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['nom']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" name="assign_driver" class="btn-go"><i class="fas fa-paper-plane"></i></button>
                                </form>
                            <?php endif; ?>
                        </td>

                        <td style="text-align:right;">
                            <?php if($c['statut'] == 'expedie'): ?>
                                <form method="POST">
                                    <input type="hidden" name="id_cmd" value="<?= $c['id'] ?>">
                                    <button type="submit" name="validate_delivery" class="btn-validate">
                                        <i class="fas fa-wallet"></i> Encaisser
                                    </button>
                                </form>
                            <?php else: ?>
                                <span style="font-size:11px; color:#aaa; background:#f3f4f6; padding:4px 8px; border-radius:4px;">En attente</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if(empty($livraisons)): ?>
                        <tr><td colspan="4" style="text-align:center; padding:40px; color:#aaa;">Aucune livraison active.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>