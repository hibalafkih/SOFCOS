<?php
require_once '../config.php'; 

// Vérification ID
if (!isset($_GET['id']) || empty($_GET['id'])) { die("Commande introuvable."); }
$id_commande = (int)$_GET['id'];

// Récupération Commande
$stmt = $pdo->prepare("SELECT * FROM commandes WHERE id = ?");
$stmt->execute([$id_commande]);
$commande = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$commande) { die("Commande inexistante."); }

// Récupération Produits
$stmt_details = $pdo->prepare("SELECT cd.*, p.nom FROM commandes_details cd JOIN produits p ON cd.produit_id = p.id WHERE cd.commande_id = ?");
$stmt_details->execute([$id_commande]);
$details = $stmt_details->fetchAll(PDO::FETCH_ASSOC);

// Génération QR Code
$qr_content = "CMD-" . $commande['id'] . "-" . $commande['total'] . "DH";
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=100x100&color=1A3C34&data=" . urlencode($qr_content);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture SOFCOS #<?= $commande['id'] ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    
    <style>
        /* --- VARIABLES & BASE --- */
        :root {
            --vert-fonce: #1A3C34; /* Votre couleur de marque */
            --dore: #D4AF37;       /* Touche de luxe */
            --gris-clair: #f9f9f9;
            --gris-texte: #555;
            --bordure: #eee;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #555; /* Fond gris foncé à l'écran pour bien voir la feuille */
            margin: 0;
            padding: 40px;
            color: #333;
        }

        /* --- CONTENEUR A4 --- */
        .ticket-page {
            width: 210mm;      /* Largeur exacte A4 */
            min-height: 297mm; /* Hauteur A4 */
            background: white;
            margin: 0 auto;
            padding: 15mm 20mm; /* Marges intérieures élégantes */
            box-sizing: border-box;
            position: relative;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        /* --- EN-TÊTE --- */
        header { text-align: center; margin-bottom: 40px; }
        .brand-name {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            color: var(--vert-fonce);
            letter-spacing: 4px;
            margin: 0;
            text-transform: uppercase;
        }
        .tagline {
            font-size: 10px;
            color: var(--dore);
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-top: 5px;
            font-weight: 600;
        }
        .contact-info {
            font-size: 10px;
            color: var(--gris-texte);
            margin-top: 10px;
            line-height: 1.5;
        }

        /* --- INFO CLIENT & COMMANDE (GRID) --- */
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 1px dashed var(--bordure);
        }
        
        .info-col h3 {
            font-size: 10px;
            color: var(--dore);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }
        .info-col p { margin: 0; font-size: 12px; line-height: 1.5; color: #333; }
        .info-col strong { color: var(--vert-fonce); font-weight: 600; }

        /* --- TABLEAU PRODUITS --- */
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        
        th {
            text-align: left;
            font-size: 9px;
            color: #999;
            text-transform: uppercase;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--bordure);
            font-weight: 600;
        }
        
        td {
            padding: 15px 0;
            font-size: 12px;
            border-bottom: 1px solid var(--gris-clair);
            vertical-align: top;
        }

        /* Gestion intelligente des noms longs */
        .col-produit { 
            width: 60%; 
            padding-right: 10px; 
        }
        .product-name {
            font-weight: 500;
            color: var(--vert-fonce);
            display: -webkit-box;
            -webkit-line-clamp: 2; /* Coupe proprement après 2 lignes */
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .col-qty { text-align: center; width: 10%; color: #777; }
        .col-prix { text-align: right; width: 20%; font-weight: 600; }

        /* --- SECTION TOTAUX --- */
        .total-container {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 40px;
        }
        .total-box { width: 250px; }
        
        .row-total {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #666;
            margin-bottom: 8px;
        }
        
        .grand-total {
            display: flex;
            justify-content: space-between;
            font-size: 18px;
            font-weight: 700;
            color: var(--vert-fonce);
            border-top: 2px solid var(--vert-fonce);
            padding-top: 15px;
            margin-top: 10px;
        }

        /* --- PIED DE PAGE & QR --- */
        .footer {
            position: absolute;
            bottom: 20mm; /* Fixé en bas de la feuille A4 */
            left: 20mm;
            right: 20mm;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        
        .legal-text {
            font-size: 9px;
            color: #999;
            line-height: 1.6;
            max-width: 60%;
        }
        
        .qr-box img {
            width: 80px;
            height: 80px;
            /* Une petite bordure dorée pour le style */
            border: 1px solid var(--bordure); 
            padding: 5px;
        }

        /* --- BOUTON IMPRIMER (Invisible à l'impression) --- */
        .btn-print {
            position: fixed; top: 30px; right: 30px;
            background: var(--vert-fonce); color: white;
            padding: 15px 30px; border-radius: 50px;
            border: none; cursor: pointer; font-weight: 600;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: transform 0.2s;
            z-index: 1000;
        }
        .btn-print:hover { transform: scale(1.05); background: #14302a; }

        /* --- RÈGLES D'IMPRESSION STRICTES --- */
        @media print {
            body { background: white; padding: 0; margin: 0; }
            .btn-print { display: none; }
            
            .ticket-page {
                margin: 0;
                width: 100%;
                height: auto;
                box-shadow: none;
                /* Suppression des marges forcées pour laisser l'imprimante gérer */
                padding: 10mm 15mm; 
            }

            /* On cache les en-têtes/pieds de page du navigateur */
            @page {
                margin: 0;
                size: auto;
            }
        }
    </style>
</head>
<body>

<button onclick="window.print()" class="btn-print">🖨️ IMPRIMER</button>

<div class="ticket-page">
    
    <header>
        <h1 class="brand-name">SOFCOS</h1>
        <div class="tagline">Cosmétiques Naturels</div>
        <div class="contact-info">
            www.sofcos.com • contact@sofcos.com<br>
            Safi, Maroc • +212 6 00 00 00 00
        </div>
    </header>

    <div class="info-section">
        <div class="info-col">
            <h3>Destinataire</h3>
            <strong><?= htmlspecialchars(strtoupper($commande['nom_client'])) ?></strong><br>
            <?= htmlspecialchars($commande['telephone']) ?><br>
            <?= htmlspecialchars($commande['ville']) ?><br>
            <span style="color:#999; font-size:11px;"><?= htmlspecialchars($commande['adresse']) ?></span>
        </div>
        
        <div class="info-col" style="text-align: right;">
            <h3>Détails Commande</h3>
            <strong>N° #<?= $commande['id'] ?></strong><br>
            Date : <?= date('d/m/Y', strtotime($commande['date_commande'])) ?><br>
            Statut : <?= ucfirst($commande['statut']) ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="col-produit">Description</th>
                <th class="col-qty">Qté</th>
                <th class="col-prix">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($details as $d): ?>
            <tr>
                <td class="col-produit">
                    <div class="product-name"><?= htmlspecialchars($d['nom']) ?></div>
                </td>
                <td class="col-qty">x<?= $d['quantite'] ?></td>
                <td class="col-prix"><?= number_format($d['prix_unitaire'] * $d['quantite'], 2) ?> DH</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="total-container">
        <div class="total-box">
            <div class="row-total">
                <span>Sous-total</span>
                <span><?= number_format($commande['total'], 2) ?> DH</span>
            </div>
            <div class="row-total">
                <span>Livraison</span>
                <span>0.00 DH</span>
            </div>
            <div class="grand-total">
                <span>TOTAL</span>
                <span><?= number_format($commande['total'], 2) ?> DH</span>
            </div>
        </div>
    </div>

    <div class="footer">
        <div class="legal-text">
            <strong>Merci de votre confiance.</strong><br>
            Les marchandises vendues ne sont ni reprises ni échangées après 7 jours.<br>
            ICE: 001234567000088 • RC: 12345
        </div>
        <div class="qr-box">
            <img src="<?= $qr_url ?>" alt="QR Code">
        </div>
    </div>

</div>

</body>
</html>