<?php
session_start();

// Connexion BDD
try {
    if(file_exists('config.php')) { require_once 'config.php'; } 
    elseif(file_exists('../config.php')) { require_once '../config.php'; } 
    else { $pdo = new PDO("mysql:host=localhost;dbname=sofcos_db;charset=utf8", "root", ""); }
} catch(Exception $e) { die("Erreur BDD"); }

// 1. On récupère l'ID
$commande_id = $_GET['id'] ?? null;

if(!$commande_id) {
    header('Location: produits.php');
    exit();
}

// 2. Récupérer la commande
$stmt = $pdo->prepare("SELECT * FROM commandes WHERE id = ?");
$stmt->execute([$commande_id]);
$commande = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$commande) {
    die("Commande introuvable.");
}

// 3. Récupérer les produits
$stmt = $pdo->prepare("
    SELECT d.*, p.nom, p.image 
    FROM commandes_details d 
    JOIN produits p ON d.produit_id = p.id 
    WHERE d.commande_id = ?
");
$stmt->execute([$commande_id]);
$produits = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Calcul pour l'affichage (Sous-total vs Total)
$sous_total_produits = 0;
foreach($produits as $p) {
    $sous_total_produits += ($p['prix_unitaire'] * $p['quantite']);
}
// La différence entre le TOTAL PAYÉ et le PRIX DES PRODUITS correspond à la livraison
$frais_livraison_payes = $commande['total'] - $sous_total_produits;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commande confirmée - SOFCOS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    
    <style>
        :root { --primary: #1A3C34; --gold: #D4AF37; --bg: #f9f9f9; }
        
        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; padding: 0; color: #333; }
        
        .confirmation-container { max-width: 800px; margin: 40px auto; padding: 0 20px; }
        
        .confirmation-card {
            background: white; padding: 40px; border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05); text-align: center;
            border-top: 5px solid var(--primary);
        }

        .success-icon {
            width: 80px; height: 80px; background: #27ae60; color: white;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px; font-size: 35px; animation: popIn 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }
        @keyframes popIn { 0% { transform: scale(0); } 100% { transform: scale(1); } }

        h1 { color: var(--primary); margin: 0 0 10px; font-family: 'Playfair Display', serif; }
        .order-ref { font-size: 16px; color: #888; margin-bottom: 30px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }

        /* INFO BOX */
        .info-box { background: #fcfcfc; border: 1px solid #eee; border-radius: 8px; padding: 30px; text-align: left; margin: 30px 0; }
        .info-title { font-size: 14px; text-transform: uppercase; font-weight: 700; color: var(--primary); border-bottom: 2px solid var(--gold); padding-bottom: 10px; margin-bottom: 20px; letter-spacing: 1px; }
        
        .info-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14px; }
        .info-label { color: #777; }
        .info-value { font-weight: 600; color: #333; text-align: right; }
        
        .total-row { border-top: 1px solid #ddd; padding-top: 15px; margin-top: 15px; font-size: 18px; color: var(--primary); font-weight: 700; display: flex; justify-content: space-between; align-items: center; }

        /* PRODUITS */
        .prod-list { text-align: left; margin-bottom: 20px; }
        .prod-item { display: flex; align-items: center; gap: 15px; padding: 15px 0; border-bottom: 1px solid #f0f0f0; }
        .prod-img { width: 60px; height: 60px; object-fit: cover; border-radius: 6px; background: #eee; border: 1px solid #eee; }
        .prod-details { flex: 1; }
        .prod-name { font-weight: 600; font-size: 14px; color: var(--primary); margin-bottom: 4px; }
        .prod-qty { font-size: 12px; color: #999; }
        .prod-price { font-weight: 600; font-size: 14px; }

        /* BOUTONS */
        .actions { display: flex; gap: 15px; justify-content: center; margin-top: 30px; }
        .btn { padding: 14px 25px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: #122b25; transform: translateY(-2px); }
        .btn-outline { border: 1px solid #ccc; color: #555; }
        .btn-outline:hover { border-color: var(--primary); color: var(--primary); }

        @media(max-width: 600px) { .actions { flex-direction: column; } }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>
    
    <div class="confirmation-container">
        <div class="confirmation-card">
            <div class="success-icon"><i class="fas fa-check"></i></div>
            
            <h1>Merci <?= htmlspecialchars($commande['nom_client']) ?> !</h1>
            <p class="order-ref">Commande #<?= $commande['id'] ?></p>
            <p style="color:#666; font-size: 14px; line-height: 1.6;">
                Votre commande a bien été enregistrée.<br>
                Vous recevrez un appel de confirmation avant la livraison.
            </p>
            
            <div class="info-box">
                <div class="info-title"><i class="fas fa-truck"></i> Livraison</div>
                
                <div class="info-row">
                    <span class="info-label">Date :</span>
                    <span class="info-value"><?= date('d/m/Y', strtotime($commande['date_commande'])) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Adresse :</span>
                    <span class="info-value">
                        <?= htmlspecialchars($commande['adresse']) ?>, 
                        <?= htmlspecialchars($commande['ville']) ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Paiement :</span>
                    <span class="info-value" style="color: var(--primary); font-weight:bold;">
                        <i class="fas fa-hand-holding-dollar"></i> <?= htmlspecialchars($commande['mode_paiement']) ?>
                    </span>
                </div>

                <div class="prod-list">
                    <div class="info-title" style="margin-top:30px; border:none; padding-bottom:5px;">Résumé</div>
                    <?php foreach($produits as $p): ?>
                        <div class="prod-item">
                            <?php 
                                $img = !empty($p['image']) ? 'uploads/produits/' . $p['image'] : 'images/default.jpg';
                                // Petit fix pour éviter les erreurs si l'image n'existe pas
                                if (!file_exists($img)) $img = 'images/default.jpg';
                            ?>
                            <img src="<?= $img ?>" alt="" class="prod-img">
                            <div class="prod-details">
                                <div class="prod-name"><?= htmlspecialchars($p['nom']) ?></div>
                                <div class="prod-qty">Quantité : <?= $p['quantite'] ?></div>
                            </div>
                            <div class="prod-price">
                                <?= number_format($p['prix_unitaire'] * $p['quantite'], 2) ?> DH
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="border-top: 1px dashed #ddd; margin-top: 10px; padding-top: 10px;">
                    <div class="info-row">
                        <span class="info-label">Sous-total</span>
                        <span class="info-value"><?= number_format($sous_total_produits, 2) ?> MAD</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Frais de livraison</span>
                        <span class="info-value">
                            <?php if($frais_livraison_payes <= 0): ?>
                                <span style="color: #27ae60;">Offerts</span>
                            <?php else: ?>
                                <?= number_format($frais_livraison_payes, 2) ?> MAD
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

                <div class="total-row">
                    <span>Total à payer</span>
                    <span style="font-size: 22px;"><?= number_format($commande['total'], 2) ?> MAD</span>
                </div>
            </div>

            <div class="actions">
                <a href="produits.php" class="btn btn-primary">
                    <i class="fas fa-shopping-bag"></i> Continuer mes achats
                </a>
                
                </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

</body>
</html>