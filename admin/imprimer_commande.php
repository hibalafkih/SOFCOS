<?php
require_once '../config.php'; 

$id_commande = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Récupération des données depuis la table 'commandes' [cite: 22, 23]
$stmt = $pdo->prepare("SELECT * FROM commandes WHERE id = ?");
$stmt->execute([$id_commande]);
$commande = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$commande) { die("Commande introuvable."); }

// Récupération des articles depuis 'commandes_details' et 'produits' [cite: 28, 61]
$stmt_details = $pdo->prepare("SELECT cd.*, p.nom, p.marque FROM commandes_details cd JOIN produits p ON cd.produit_id = p.id WHERE cd.commande_id = ?");
$stmt_details->execute([$id_commande]);
$details = $stmt_details->fetchAll(PDO::FETCH_ASSOC);

$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=" . urlencode("CMD-".$commande['id']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        /* CONFIGURATION LUXE & COMPACTE */
        :root { --sofcos-green: #1A3C34; --gold: #D4AF37; }
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; padding: 20px; background: #f4f4f4; color: #333; }
        
        .ticket {
            width: 180mm; /* Largeur réduite pour éviter les coupures A4 [cite: 91] */
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        /* EN-TÊTE */
        .header { text-align: center; border-bottom: 2px solid var(--sofcos-green); padding-bottom: 20px; margin-bottom: 20px; }
        .logo { font-size: 32px; font-weight: bold; color: var(--sofcos-green); letter-spacing: 5px; margin: 0; }
        .subtitle { font-size: 10px; color: var(--gold); text-transform: uppercase; letter-spacing: 2px; }

        /* INFOS CLIENT & PAIEMENT */
        .section-info { display: flex; justify-content: space-between; margin-bottom: 25px; font-size: 13px; }
        .info-box h4 { font-size: 11px; color: #999; text-transform: uppercase; margin-bottom: 5px; }
        .payment-badge { 
            background: var(--sofcos-green); color: white; padding: 5px 10px; 
            border-radius: 3px; font-weight: bold; display: inline-block; margin-top: 5px;
        }

        /* TABLEAU PRODUITS [cite: 28, 91] */
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { text-align: left; font-size: 11px; color: #999; text-transform: uppercase; border-bottom: 1px solid #eee; padding: 10px 0; }
        td { padding: 12px 0; border-bottom: 1px solid #f9f9f9; font-size: 13px; }
        .prod-name { font-weight: 600; color: var(--sofcos-green); }
        .prod-brand { font-size: 10px; color: var(--gold); text-transform: uppercase; }

        /* TOTAL [cite: 24, 100] */
        .total-area { margin-left: auto; width: 250px; border-top: 2px solid var(--sofcos-green); padding-top: 10px; }
        .total-row { display: flex; justify-content: space-between; padding: 5px 0; }
        .grand-total { font-size: 20px; font-weight: bold; color: var(--sofcos-green); }

        /* FOOTER & QR [cite: 98, 99] */
        .footer { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 30px; padding-top: 20px; border-top: 1px dashed #eee; }
        .legal { font-size: 10px; color: #999; line-height: 1.4; }
        .qr img { width: 70px; height: 70px; }

        @media print {
            body { background: none; padding: 0; }
            .ticket { width: 100%; box-shadow: none; padding: 10mm; zoom: 95%; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div style="text-align:center;" class="no-print">
    <button onclick="window.print()" style="padding:10px 20px; cursor:pointer; background:var(--sofcos-green); color:white; border:none; border-radius:5px; margin-bottom:20px;">
        IMPRIMER LE BON DE LIVRAISON
    </button>
</div>

<div class="ticket">
    <div class="header">
        <h1 class="logo">SOFCOS</h1>
        <div class="subtitle">Cosmétiques Naturels • Safi, Maroc</div>
    </div>

    <div class="section-info">
        <div class="info-box">
            <h4>Client</h4>
            [cite_start]<strong><?= htmlspecialchars($commande['nom_client']) ?></strong> [cite: 23]<br>
            [cite_start]<?= htmlspecialchars($commande['telephone']) ?> [cite: 23]<br>
            [cite_start]<?= htmlspecialchars($commande['ville']) ?>, Maroc [cite: 24]
        </div>
        <div class="info-box" style="text-align: right;">
            [cite_start]<h4>Commande #<?= $commande['id'] ?></h4> [cite: 23]
            [cite_start]Date: <?= date('d/m/Y', strtotime($commande['date_commande'])) ?> [cite: 24]<br>
            <div class="payment-badge">
                [cite_start]<?= strtoupper($commande['mode_paiement']) ?> [cite: 24]
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Article</th>
                <th style="text-align:center;">Qté</th>
                <th style="text-align:right;">Prix</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($details as $item): ?>
            <tr>
                <td>
                    [cite_start]<div class="prod-brand"><?= htmlspecialchars($item['marque'] ?? 'SOFCOS') ?></div> [cite: 61]
                    [cite_start]<div class="prod-name"><?= htmlspecialchars($item['nom']) ?></div> [cite: 61]
                </td>
                [cite_start]<td style="text-align:center;">x<?= $item['quantite'] ?></td> [cite: 28]
                [cite_start]<td style="text-align:right;"><?= number_format($item['prix_unitaire'] * $item['quantite'], 2) ?> DH</td> [cite: 28]
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="total-area">
        <div class="total-row">
            <span>Sous-total</span>
            [cite_start]<span><?= number_format($commande['total'], 2) ?> DH</span> [cite: 24]
        </div>
        <div class="total-row">
            <span>Frais de livraison</span>
            <span>0.00 DH</span>
        </div>
        <div class="total-row grand-total">
            <span>TOTAL À PAYER</span>
            [cite_start]<span><?= number_format($commande['total'], 2) ?> DH</span> [cite: 24, 101]
        </div>
    </div>

    <?php if(!empty($commande['notes_client'])): ?>
    <div style="margin-top:20px; font-size:11px; font-style:italic; color:#666; border-left:3px solid var(--gold); padding-left:10px;">
        [cite_start]Note : <?= htmlspecialchars($commande['notes_client']) ?> [cite: 24]
    </div>
    <?php endif; ?>

    <div class="footer">
        <div class="legal">
            [cite_start]Merci de votre confiance. [cite: 97]<br>
            Veuillez préparer le montant exact pour le livreur.<br>
            [cite_start]ICE: 001234567000088 [cite: 99]
        </div>
        <div class="qr">
            [cite_start]<img src="<?= $qr_url ?>" alt="QR Code"> [cite: 98]
        </div>
    </div>
</div>

</body>
</html>