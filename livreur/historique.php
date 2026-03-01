<?php
session_start();
require_once '../config.php';

if(!isset($_SESSION['livreur_id'])) { header("Location: login.php"); exit(); }

$id_livreur = $_SESSION['livreur_id'];

// Récupérer l'historique des commandes (Livrées ou Annulées)
$stmt = $pdo->prepare("SELECT * FROM commandes WHERE livreur_id = ? AND statut IN ('livre', 'annule') ORDER BY date_commande DESC");
$stmt->execute([$id_livreur]);
$historique = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Historique Livreur</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="header">
        <h2>Historique</h2>
        <span class="count-badge"><?= count($historique) ?> courses</span>
    </div>

    <div class="container">
        <?php if(empty($historique)): ?>
            <div style="text-align:center; padding:50px; color:#aaa;">
                <i class="fas fa-box-open" style="font-size:40px; margin-bottom:15px; opacity:0.5;"></i>
                <p>Aucune course terminée.</p>
            </div>
        <?php else: ?>
            <?php foreach($historique as $h): 
                $cls = ($h['statut'] == 'livre') ? 'bg-livre' : 'bg-annule';
                $icon = ($h['statut'] == 'livre') ? 'check' : 'times';
            ?>
            <div class="card">
                <div class="card-header">
                    <span class="order-ref">#<?= $h['id'] ?></span>
                    <span class="date"><?= date('d/m - H:i', strtotime($h['date_commande'])) ?></span>
                </div>
                
                <div class="client-name"><?= htmlspecialchars($h['nom_client']) ?></div>
                <div class="client-loc">
                    <i class="fas fa-map-marker-alt" style="color:var(--gold)"></i> 
                    <?= htmlspecialchars($h['ville']) ?>
                </div>

                <div class="card-footer">
                    <span class="badge <?= $cls ?>">
                        <i class="fas fa-<?= $icon ?>"></i> <?= ucfirst($h['statut']) ?>
                    </span>
                    <div class="price"><?= number_format($h['total'], 0) ?> DH</div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php include 'nav.php'; ?>

</body>
</html>