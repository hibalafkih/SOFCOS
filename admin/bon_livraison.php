<?php
session_start();
require_once '../config.php';

if(!isset($_SESSION['admin_id']) || !isset($_GET['id'])) { die("Accès refusé"); }

$id = (int)$_GET['id'];
$conn = $pdo;

// --- 1. TRAITEMENT : CHANGER STATUT EN "EXPÉDIÉ" ---
$message_update = "";
if(isset($_POST['marquer_expedie'])) {
    $stmt = $conn->prepare("UPDATE commandes SET statut = 'expedie' WHERE id = ?");
    $stmt->execute([$id]);
    $message_update = "✅ Commande marquée comme EXPÉDIÉE !";
    // On recharge les infos
}

// --- 2. RÉCUPÉRATION DONNÉES ---
$stmt = $conn->prepare("SELECT * FROM commandes WHERE id = ?");
$stmt->execute([$id]);
$c = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$c) die("Commande introuvable");

// --- 3. PRODUITS ---
$produits = [];
$sql_table = "commandes_details";
try {
    $stmt = $conn->prepare("SELECT d.*, p.nom as nom_p FROM $sql_table d LEFT JOIN produits p ON d.produit_id = p.id WHERE d.commande_id = ?");
    $stmt->execute([$id]);
    $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {}

if(empty($produits)) {
    $sql_table = "details_commande";
    try {
        $stmt = $conn->prepare("SELECT d.*, p.nom as nom_p FROM $sql_table d LEFT JOIN produits p ON d.produit_id = p.id WHERE d.commande_id = ?");
        $stmt->execute([$id]);
        $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e) {}
}

// --- 4. CALCULS (Pour le montant à encaisser) ---
$sous_total = 0;
foreach($produits as $p) {
    $prix = !empty($p['prix_unitaire']) ? $p['prix_unitaire'] : ($p['prix_p'] ?? 0);
    $qte = !empty($p['quantite']) ? $p['quantite'] : ($p['qte'] ?? 1);
    $sous_total += ($prix * $qte);
}
// Logique livraison
$livraison = ($sous_total > 500) ? 0 : 30;
$total_a_payer = $sous_total + $livraison;

// Génération QR Code Livreur (Adresse GPS simplifiée ou Lien Waze si vous aviez les coords, ici on met l'adresse texte)
$qr_data = "LIVRAISON #" . $c['id'] . " | " . $c['telephone'] . " | " . $total_a_payer . " DH";
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=" . urlencode($qr_data);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bon de Livraison #<?= $c['id'] ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        body { background: #eef2f5; font-family: 'Outfit', sans-serif; padding: 20px; color: #111; }
        
        .sheet {
            background: white;
            width: 100%;
            max-width: 700px; /* Format A4 plus large */
            margin: 0 auto;
            padding: 40px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-left: 10px solid #1A3C34; /* Vert SOFCOS */
        }

        /* HEADER */
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; padding-bottom: 20px; margin-bottom: 30px; }
        .company h1 { margin: 0; color: #1A3C34; font-size: 24px; text-transform: uppercase; }
        .doc-title { text-align: right; }
        .doc-title h2 { margin: 0; font-size: 28px; color: #1A3C34; }
        .doc-title span { font-size: 14px; color: #777; }

        /* ZONES */
        .info-grid { display: flex; gap: 30px; margin-bottom: 30px; }
        .box { flex: 1; border: 1px solid #ddd; padding: 15px; border-radius: 4px; background: #f9f9f9; }
        .box h3 { margin: 0 0 10px 0; font-size: 12px; text-transform: uppercase; color: #888; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        
        .delivery-info p { margin: 5px 0; font-size: 14px; }
        .big-phone { font-size: 18px; font-weight: bold; color: #1A3C34; margin-top: 5px; display: block;}

        /* TABLEAU */
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { background: #1A3C34; color: white; text-align: left; padding: 10px; font-size: 12px; text-transform: uppercase; }
        td { border-bottom: 1px solid #eee; padding: 10px; font-size: 14px; }
        .col-check { width: 40px; text-align: center; border: 1px solid #ddd; color: #ddd; font-size: 20px; }

        /* CASH ON DELIVERY BOX */
        .cod-box {
            border: 2px dashed #1A3C34;
            background: #eefcf6;
            padding: 20px;
            text-align: center;
            margin-bottom: 30px;
        }
        .cod-amount { font-size: 32px; font-weight: 800; color: #1A3C34; }
        .cod-label { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }

        /* SIGNATURES */
        .signatures { display: flex; justify-content: space-between; margin-top: 50px; }
        .sig-box { width: 45%; height: 100px; border: 1px solid #ccc; position: relative; }
        .sig-label { position: absolute; top: -10px; left: 10px; background: white; padding: 0 5px; font-size: 11px; font-weight: bold; }

        /* NO PRINT */
        .actions { max-width: 700px; margin: 0 auto 20px auto; display: flex; justify-content: space-between; align-items: center; }
        .btn { padding: 10px 20px; border: none; cursor: pointer; border-radius: 4px; font-weight: 600; text-decoration: none; display: inline-block;}
        .btn-print { background: #1A3C34; color: white; }
        .btn-status { background: #C5A059; color: white; }
        .alert { background: #d1fae5; color: #065f46; padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; }

        @media print {
            body { background: white; padding: 0; }
            .sheet { box-shadow: none; margin: 0; max-width: 100%; border-left: none; }
            .actions, .alert { display: none; }
            /* Force l'impression du fond pour la case COD */
            .cod-box { -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>

    <div class="actions">
        <div>
            <a href="commandes.php" style="color:#666; text-decoration:none;">&larr; Retour</a>
        </div>
        <div>
            <form method="POST" style="display:inline;">
                <button type="submit" name="marquer_expedie" class="btn btn-status">
                    <i class="fas fa-truck"></i> Marquer "Expédié"
                </button>
            </form>
            <button onclick="window.print()" class="btn btn-print">Imprimer BL</button>
        </div>
    </div>

    <?php if($message_update): ?>
        <div class="actions"><div class="alert" style="width:100%;"><?= $message_update ?></div></div>
    <?php endif; ?>

    <div class="sheet">
        
        <div class="header">
            <div class="company">
                <h1>SOFCOS</h1>
                <div style="font-size:11px; color:#666;">Service Logistique</div>
            </div>
            <div class="doc-title">
                <h2>BON DE LIVRAISON</h2>
                <span>Référence: BL-<?= date('Y') ?>-<?= $c['id'] ?></span>
            </div>
        </div>

        <div class="info-grid">
            
            <div class="box" style="flex:0.6;">
                <h3>Expéditeur</h3>
                <strong style="color:#1A3C34">SOFCOS MAROC</strong><br>
                <small>Safi, Maroc</small><br>
                <small>06 00 00 00 00</small>
            </div>

            <div class="box delivery-info" style="flex:1.4; border: 2px solid #333;">
                <h3>Adresse de Livraison</h3>
                <div style="font-size:18px; font-weight:bold; margin-bottom:5px;"><?= strtoupper(htmlspecialchars($c['nom_client'])) ?></div>
                
                <div><?= nl2br(htmlspecialchars($c['adresse'] ?? '')) ?></div>
                <div style="font-weight:bold; margin-top:5px;"><?= htmlspecialchars($c['code_postal']) ?> <?= htmlspecialchars($c['ville']) ?></div>
                
                <span class="big-phone">📞 <?= htmlspecialchars($c['telephone']) ?></span>
                
                <?php if(!empty($c['notes_client'])): ?>
                    <div style="margin-top:10px; font-style:italic; background:#eee; padding:5px; font-size:12px;">
                        Note: <?= htmlspecialchars($c['notes_client']) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div style="width: 80px; text-align: center;">
                 <img src="<?= $qr_url ?>" style="width:100%;">
                 <div style="font-size:9px;">Scan Livreur</div>
            </div>

        </div>

        <table>
            <thead>
                <tr>
                    <th width="10%">Qté</th>
                    <th width="70%">Désignation</th>
                    <th width="20%">Contrôle</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($produits as $p): 
                     $nom = !empty($p['nom_p']) ? $p['nom_p'] : 'Article Réf. ' . $p['produit_id'];
                     $qte = !empty($p['quantite']) ? $p['quantite'] : ($p['qte'] ?? 1);
                ?>
                <tr>
                    <td style="font-weight:bold; font-size:16px; text-align:center;"><?= $qte ?></td>
                    <td><?= htmlspecialchars($nom) ?></td>
                    <td class="col-check">⬜</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="cod-box">
            <div class="cod-label">Montant à encaisser auprès du client (Espèces / Chèque)</div>
            <div class="cod-amount"><?= number_format($total_a_payer, 2) ?> DH</div>
            <div style="font-size:10px; color:#666;">Incluant frais de livraison</div>
        </div>

        <div class="signatures">
            <div class="sig-box">
                <span class="sig-label">Signature Livreur</span>
            </div>
            <div class="sig-box">
                <span class="sig-label">Signature Client (Reçu conforme)</span>
            </div>
        </div>

        <div style="margin-top:30px; text-align:center; font-size:10px; color:#999;">
            SOFCOS - Document Logistique Interne - Ne tient pas lieu de facture.
        </div>

    </div>

</body>
</html>