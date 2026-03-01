<?php
session_start();
require_once '../config.php';
if(!isset($_SESSION['livreur_id'])) { header("Location: login.php"); exit(); }

$id_livreur = $_SESSION['livreur_id'];
$nom_livreur = explode(' ', $_SESSION['livreur_nom'])[0]; // Juste le prénom

// Récupérer les commandes
$stmt = $pdo->prepare("SELECT * FROM commandes WHERE livreur_id = ? AND statut = 'expedie' ORDER BY date_commande ASC");
$stmt->execute([$id_livreur]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Notification
$notif = "";
if(isset($_GET['msg'])) {
    $notif = "<div class='toast'>Message envoyé à l'admin !</div>";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>App Livreur</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="header">
        <div class="welcome">
            <span>Bonne route,</span>
            <h2><?= htmlspecialchars($nom_livreur) ?> 👋</h2>
        </div>
        <a href="logout.php" class="avatar"><i class="fas fa-power-off"></i></a>
    </div>

    <?= $notif ?>

    <div class="container">
        <?php if(empty($courses)): ?>
            <div class="empty-state">
                <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" width="100" style="opacity:0.5; margin-bottom:20px;">
                <h3>Aucune commande en cours</h3>
                <p>Scannez des colis ou attendez l'assignation.</p>
            </div>
        <?php endif; ?>

        <?php foreach($courses as $c): ?>
        <div class="card">
            <div class="card-top">
                <div class="order-id">#<?= $c['id'] ?></div>
                <div class="price-tag"><?= number_format($c['total'], 0) ?> DH</div>
            </div>
            
            <div class="client-info">
                <h3><?= htmlspecialchars($c['nom_client']) ?></h3>
                <div class="client-addr">
                    <i class="fas fa-map-marker-alt"></i>
                    <div><?= htmlspecialchars($c['adresse']) ?> <br> <strong><?= htmlspecialchars($c['ville']) ?></strong></div>
                </div>
            </div>

            <?php if(!empty($c['rapport_livreur'])): ?>
                <div class="msg-sent">
                    <i class="fas fa-info-circle"></i> Signalé : "<?= htmlspecialchars($c['rapport_livreur']) ?>"
                </div>
            <?php endif; ?>

            <div class="actions">
                <a href="tel:<?= $c['telephone'] ?>" class="act-btn act-call">
                    <i class="fas fa-phone"></i> Appeler
                </a>
                <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($c['adresse'].' '.$c['ville']) ?>" target="_blank" class="act-btn act-map">
                    <i class="fas fa-directions"></i> GPS
                </a>
                <button onclick="openModal(<?= $c['id'] ?>)" class="act-btn act-msg">
                    <i class="fas fa-exclamation-triangle"></i> Problème
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php include 'nav.php'; ?>

    <div id="reportModal" class="modal-overlay">
        <div class="modal-box">
            <h3 style="margin-top:0;">Signaler un problème</h3>
            <p style="font-size:13px; color:#666; margin-bottom:10px;">Le client ne répond pas ? Adresse fausse ? Laissez une note à l'admin.</p>
            
            <form action="envoyer_message.php" method="POST">
                <input type="hidden" name="id_cmd" id="modalCmdId">
                <textarea name="message" placeholder="Ex: Client absent, ne répond pas au tel..." required></textarea>
                
                <div class="modal-btns">
                    <button type="button" class="btn-close" onclick="closeModal()">Annuler</button>
                    <button type="submit" class="btn-send">Envoyer</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(id) {
            document.getElementById('modalCmdId').value = id;
            document.getElementById('reportModal').style.display = 'flex';
        }
        function closeModal() {
            document.getElementById('reportModal').style.display = 'none';
        }
    </script>

</body>
</html>