<?php
session_start();
require_once '../config.php';

if(!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit(); }
if(!isset($_GET['id'])) { header('Location: commandes.php'); exit(); }

$id_commande = (int)$_GET['id'];
$conn = $pdo;
$msg = "";

// --- MISE À JOUR STATUT ---
if(isset($_POST['btn_update_status'])) {
    $nouveau_statut = $_POST['nouveau_statut'];
    $conn->prepare("UPDATE commandes SET statut = ? WHERE id = ?")->execute([$nouveau_statut, $id_commande]);
    header("Location: details_commande.php?id=$id_commande&success=1");
    exit();
}
if(isset($_GET['success'])) {
    $msg = "<div class='alert success'><i class='fas fa-check-circle'></i> Statut mis à jour.</div>";
}

// --- INFO COMMANDE ---
$stmt = $conn->prepare("SELECT * FROM commandes WHERE id = ?");
$stmt->execute([$id_commande]);
$c = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$c) die("Commande introuvable.");

// --- INFO PRODUITS (CORRECTION DE LA LOGIQUE) ---
$produits = [];

// 1. On essaye d'abord la table 'commandes_details' (Priorité selon votre PDF)
try {
    $sql = "SELECT d.*, p.nom as nom_p, p.image as img_p, p.prix as prix_p 
            FROM commandes_details d 
            LEFT JOIN produits p ON d.produit_id = p.id 
            WHERE d.commande_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_commande]);
    $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {}

// 2. Si c'est vide, on essaye l'autre table 'details_commande'
if(empty($produits)) {
    try {
        $sql = "SELECT d.*, p.nom as nom_p, p.image as img_p, p.prix as prix_p 
                FROM details_commande d 
                LEFT JOIN produits p ON d.produit_id = p.id 
                WHERE d.commande_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id_commande]);
        $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $ex) {}
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Commande #<?= $c['id'] ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #1A3C34; --gold: #C5A059; --bg-body: #f3f4f6; --sidebar-width: 270px; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Outfit', sans-serif; background: var(--bg-body); color: #333; display: flex; }
        .main { margin-left: var(--sidebar-width); padding: 40px; width: 100%; }
        
        .header-page { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .btn { padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 500; display: inline-flex; align-items: center; gap: 8px; transition:0.2s; }
        .btn-back { background: white; border: 1px solid #ddd; color: #333; }
        .btn-print { background: var(--primary); color: white; border: none; }
        .btn-print:hover { background: #14302a; }
        
        .grid-container { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
        .card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 5px 15px rgba(0,0,0,0.03); }
        .alert { padding: 15px; background: #dcfce7; color: #166534; border-radius: 8px; margin-bottom: 20px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { text-align: left; padding: 10px; border-bottom: 1px solid #eee; color: #888; font-size: 13px; }
        td { padding: 12px 10px; border-bottom: 1px solid #f9f9f9; vertical-align: middle; }
        .prod-img { width: 40px; height: 40px; border-radius: 6px; object-fit: cover; margin-right: 10px; border: 1px solid #eee; }
        
        .status-box { background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px dashed #cbd5e1; margin-top: 15px; }
        .form-select { width: 100%; padding: 8px; margin: 8px 0; border-radius: 6px; border: 1px solid #ccc; }
        .btn-save { width: 100%; background: var(--gold); color: white; border: none; padding: 8px; border-radius: 6px; cursor: pointer; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main">
        <div class="header-page">
            <h1>Commande #<?= $c['id'] ?></h1>
            <div>
                <a href="commandes.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Retour</a>
                <a href="ticket_commande.php?id=<?= $c['id'] ?>" target="_blank" class="btn btn-print">
                    <i class="fas fa-print"></i> Imprimer Ticket
                </a>
            </div>
        </div>

        <?= $msg ?>

        <div class="grid-container">
            <div class="card">
                <h3 style="color:var(--primary); margin-bottom:15px;">Contenu de la commande</h3>
                
                <?php if(empty($produits)): ?>
                    <div style="text-align:center; padding:30px; color:#ef4444; background:#fef2f2; border-radius:8px;">
                        <i class="fas fa-exclamation-circle"></i> <strong>Aucun produit trouvé.</strong><br>
                        <small>Le système a cherché dans 'commandes_details' et 'details_commande'.</small>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr><th>Produit</th><th>Prix</th><th>Qte</th><th style="text-align:right;">Total</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($produits as $p): 
                                $nom = !empty($p['nom_p']) ? $p['nom_p'] : 'Produit';
                                $qte = !empty($p['quantite']) ? $p['quantite'] : ($p['qte'] ?? 1);
                                $prix = !empty($p['prix_unitaire']) ? $p['prix_unitaire'] : ($p['prix_p'] ?? 0);
                                $img = !empty($p['img_p']) ? "../uploads/produits/".$p['img_p'] : "../assets/img/no-image.png";
                            ?>
                            <tr>
                                <td style="display:flex; align-items:center;">
                                    <img src="<?= $img ?>" class="prod-img" onerror="this.src='https://via.placeholder.com/40'">
                                    <strong><?= htmlspecialchars($nom) ?></strong>
                                </td>
                                <td><?= number_format($prix, 2) ?> DH</td>
                                <td>x<?= $qte ?></td>
                                <td style="text-align:right; font-weight:bold; color:var(--primary);">
                                    <?= number_format($prix * $qte, 2) ?> DH
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <tr>
                                <td colspan="3" style="text-align:right; padding-top:15px;">Total :</td>
                                <td style="text-align:right; padding-top:15px; font-size:18px; font-weight:bold; color:var(--gold);">
                                    <?= number_format($c['total'], 2) ?> DH
                                </td>
                            </tr>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div>
                <div class="card" style="margin-bottom:20px;">
                    <h3 style="color:var(--primary); margin-bottom:10px;">Client</h3>
                    <p><strong><?= htmlspecialchars($c['nom_client']) ?></strong></p>
                    <p><?= htmlspecialchars($c['email_client']) ?></p>
                    <p><?= htmlspecialchars($c['telephone']) ?></p>
                    <hr style="border:0; border-top:1px solid #eee; margin:10px 0;">
                    <p><?= nl2br(htmlspecialchars($c['adresse'])) ?></p>
                    <p><strong><?= htmlspecialchars($c['ville']) ?></strong></p>
                </div>

                <div class="card">
                    <h3 style="color:var(--primary);">Statut</h3>
                    <div class="status-box">
                        <form method="POST">
                            <label style="font-size:12px; font-weight:bold;">État actuel : <span style="color:var(--primary)"><?= $c['statut'] ?></span></label>
                            <select name="nouveau_statut" class="form-select">
                                <option value="en attente" <?= $c['statut']=='en attente'?'selected':'' ?>>En attente</option>
                                <option value="confirme"   <?= $c['statut']=='confirme'?'selected':'' ?>>Confirmée</option>
                                <option value="expedie"    <?= $c['statut']=='expedie'?'selected':'' ?>>Expédiée</option>
                                <option value="livre"      <?= $c['statut']=='livre'?'selected':'' ?>>Livrée</option>
                                <option value="annule"     <?= $c['statut']=='annule'?'selected':'' ?>>Annulée</option>
                            </select>
                            <button type="submit" name="btn_update_status" class="btn-save">Mettre à jour</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>