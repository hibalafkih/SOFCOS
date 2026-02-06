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
    <style>
        :root { --primary: #1A3C34; --gold: #C5A059; --bg: #f8f9fa; }
        body { background: var(--bg); font-family: 'Outfit', sans-serif; margin: 0; padding-bottom: 90px; -webkit-tap-highlight-color: transparent; }
        
        /* HEADER MOBILE */
        .header { background: white; padding: 20px; position: sticky; top: 0; z-index: 50; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .welcome span { font-size: 13px; color: #888; }
        .welcome h2 { margin: 0; font-size: 20px; color: var(--primary); }
        .avatar { width: 40px; height: 40px; background: #e0e7ff; color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }

        /* LISTE DES COMMANDES */
        .container { padding: 15px; }
        .empty-state { text-align: center; margin-top: 50px; color: #aaa; }

        /* CARTE COMMANDE (STYLE MODERNE) */
        .card { background: white; border-radius: 16px; padding: 20px; margin-bottom: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); position: relative; overflow: hidden; }
        
        .card-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; }
        .order-id { background: #eee; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 600; color: #555; }
        .price-tag { font-size: 18px; font-weight: 800; color: var(--gold); }
        
        .client-info h3 { margin: 0 0 5px 0; font-size: 17px; color: #333; }
        .client-addr { font-size: 14px; color: #666; line-height: 1.4; display: flex; gap: 8px; }
        .client-addr i { color: var(--primary); margin-top: 3px; }

        /* BARRE D'ACTIONS */
        .actions { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-top: 20px; border-top: 1px solid #f0f0f0; padding-top: 15px; }
        
        .act-btn { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 5px; text-decoration: none; font-size: 12px; font-weight: 500; color: #555; padding: 8px; border-radius: 8px; background: #f9f9f9; transition: 0.2s; border: none; cursor: pointer; }
        .act-btn:active { transform: scale(0.95); }
        .act-call { color: #3b82f6; background: #eff6ff; }
        .act-map { color: #10b981; background: #dcfce7; }
        .act-msg { color: #f59e0b; background: #fef3c7; }

        /* MESSAGE DEJA ENVOYÉ */
        .msg-sent { font-size: 12px; color: #f59e0b; background: #fffbeb; padding: 8px; border-radius: 6px; margin-top: 10px; border: 1px dashed #fcd34d; display: flex; gap: 5px; align-items: center; }

        /* BOTTOM NAV */
        .bottom-nav { position: fixed; bottom: 0; width: 100%; background: white; padding: 15px 0; display: flex; justify-content: space-around; border-top: 1px solid #eee; z-index: 100; box-shadow: 0 -5px 20px rgba(0,0,0,0.02); }
        .nav-item { text-align: center; text-decoration: none; color: #ccc; font-size: 11px; }
        .nav-item.active { color: var(--primary); font-weight: 600; }
        .nav-item i { font-size: 20px; margin-bottom: 4px; display: block; }
        
        .scan-btn-wrapper { position: relative; top: -35px; }
        .scan-btn { width: 65px; height: 65px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 26px; box-shadow: 0 10px 25px rgba(26, 60, 52, 0.4); text-decoration: none; border: 4px solid #f8f9fa; }

        /* MODAL (POPUP) */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 200; display: none; align-items: center; justify-content: center; backdrop-filter: blur(3px); }
        .modal-box { background: white; width: 85%; max-width: 350px; border-radius: 16px; padding: 25px; animation: slideUp 0.3s; }
        @keyframes slideUp { from { transform: translateY(50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        
        textarea { width: 100%; height: 80px; padding: 10px; border: 1px solid #ddd; border-radius: 8px; margin: 15px 0; font-family: inherit; box-sizing: border-box; resize: none; }
        .modal-btns { display: flex; gap: 10px; }
        .btn-send { flex: 1; background: var(--primary); color: white; border: none; padding: 12px; border-radius: 8px; font-weight: 600; }
        .btn-close { flex: 1; background: #eee; color: #333; border: none; padding: 12px; border-radius: 8px; font-weight: 600; }

        /* TOAST NOTIF */
        .toast { position: fixed; top: 20px; left: 50%; transform: translateX(-50%); background: #333; color: white; padding: 10px 20px; border-radius: 30px; font-size: 13px; z-index: 300; animation: fadeOut 3s forwards; }
        @keyframes fadeOut { 0% { opacity: 1; } 80% { opacity: 1; } 100% { opacity: 0; visibility: hidden; } }
    </style>
</head>
<body>

    <div class="header">
        <div class="welcome">
            <span>Bonne route,</span>
            <h2><?= htmlspecialchars($nom_livreur) ?> 👋</h2>
        </div>
        <a href="login.php" class="avatar"><i class="fas fa-power-off"></i></a>
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

    <div class="bottom-nav">
        <a href="#" class="nav-item active">
            <i class="fas fa-list"></i> Tournée
        </a>
        <div class="scan-btn-wrapper">
            <a href="scan.php" class="scan-btn">
                <i class="fas fa-qrcode"></i>
            </a>
        </div>
        <a href="#" class="nav-item">
            <i class="fas fa-history"></i> Historique
        </a>
    </div>

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