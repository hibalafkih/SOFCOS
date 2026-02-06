<?php
session_start();
require_once 'config.php';

// Redirection si panier vide
if(empty($_SESSION['panier'])) {
    header('Location: produits.php');
    exit();
}

// Connexion BDD
if(!isset($pdo)) {
    try { $pdo = new PDO("mysql:host=localhost;dbname=sofcos_db;charset=utf8", "root", ""); } 
    catch(Exception $e) { die("Erreur BDD"); }
}

// Récupération produits
$ids = array_keys($_SESSION['panier']);
if(empty($ids)) { $produits_panier = []; } 
else {
    $in = str_repeat('?,', count($ids) - 1) . '?';
    $stmt = $pdo->prepare("SELECT * FROM produits WHERE id IN ($in)");
    $stmt->execute($ids);
    $produits_panier = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Calcul Totaux
$total_general = 0;
foreach($produits_panier as $p) {
    $qty = $_SESSION['panier'][$p['id']];
    $prix = $p['prix_promo'] ? $p['prix_promo'] : $p['prix'];
    $total_general += ($prix * $qty);
}

// Règle Livraison : Gratuite si > 500 DH, sinon 30 DH
$seuil_gratuit = 500;
$frais_fixe = 30;
$frais_livraison = ($total_general >= $seuil_gratuit) ? 0 : $frais_fixe;
$total_final = $total_general + $frais_livraison;

// Calcul pourcentage pour la barre de progression
$pourcentage_livraison = min(($total_general / $seuil_gratuit) * 100, 100);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Finaliser votre commande | SOFCOS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #1A3C34; /* Vert SOFCOS */
            --accent: #D4AF37;  /* Doré */
            --bg: #FDFCFB;
            --border: #E8E2DA;
        }

        body { font-family: 'Inter', sans-serif; background: var(--bg); color: #333; margin: 0; }
        
        .checkout-wrapper { max-width: 1200px; margin: 40px auto; padding: 0 20px; display: grid; grid-template-columns: 1fr 400px; gap: 40px; }

        /* --- STYLE FORMULAIRE --- */
        .card { background: white; border: 1px solid var(--border); border-radius: 12px; padding: 35px; box-shadow: 0 4px 20px rgba(26,60,52,0.03); }
        .section-title { font-family: 'Playfair Display', serif; font-size: 24px; color: var(--primary); margin-bottom: 25px; display: flex; align-items: center; gap: 15px; }
        .section-title span { background: var(--primary); color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; font-family: 'Inter'; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .full-width { grid-column: span 2; }
        
        .form-group label { display: block; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: #888; margin-bottom: 8px; }
        .form-control { width: 100%; padding: 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 15px; transition: 0.3s; box-sizing: border-box; }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(26,60,52,0.1); }

        /* --- STYLE RÉCAPITULATIF --- */
        .summary-sticky { position: sticky; top: 20px; }
        .summary-card { background: var(--primary); color: white; border-radius: 12px; padding: 30px; }
        .summary-card h3 { font-family: 'Playfair Display', serif; font-size: 22px; margin-top: 0; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; }
        
        .product-list { max-height: 250px; overflow-y: auto; margin-bottom: 20px; padding-right: 10px; }
        .product-item { display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 12px; color: rgba(255,255,255,0.8); }
        
        /* Barre Livraison */
        .shipping-promo { background: rgba(255,255,255,0.05); border-radius: 8px; padding: 15px; margin-bottom: 20px; border: 1px dashed rgba(212,175,55,0.3); }
        .promo-text { font-size: 12px; margin-bottom: 8px; display: block; }
        .progress-bar { background: rgba(255,255,255,0.1); height: 6px; border-radius: 10px; overflow: hidden; }
        .progress-fill { background: var(--accent); height: 100%; transition: 0.5s; }

        .price-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 15px; }
        .total-row { border-top: 1px solid rgba(255,255,255,0.2); margin-top: 15px; padding-top: 15px; display: flex; justify-content: space-between; font-size: 20px; font-weight: 600; color: var(--accent); }

        .btn-pay { width: 100%; background: var(--accent); color: var(--primary); border: none; padding: 18px; border-radius: 8px; font-weight: 700; font-size: 16px; cursor: pointer; text-transform: uppercase; letter-spacing: 1px; transition: 0.3s; margin-top: 20px; }
        .btn-pay:hover { background: #eec14a; transform: translateY(-2px); }

        @media (max-width: 992px) {
            .checkout-wrapper { grid-template-columns: 1fr; }
            .summary-sticky { position: relative; top: 0; }
        }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <div class="checkout-wrapper">
        
        <div class="col-main">
            <form action="valider_commande.php" method="POST" class="card">
                <h2 class="section-title"><span>1</span> Informations de Livraison</h2>
                
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Nom complet</label>
                        <input type="text" name="nom" required class="form-control" placeholder="Votre nom et prénom">
                    </div>

                    <div class="form-group">
                        <label>Email (Pour le reçu)</label>
                        <input type="email" name="email" class="form-control" placeholder="exemple@gmail.com">
                    </div>
                    
                    <div class="form-group">
                        <label>Téléphone (Maroc)</label>
                        <input type="text" name="telephone" required class="form-control" placeholder="06 00 00 00 00">
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Ville</label>
                        <input type="text" name="ville" required class="form-control" placeholder="Casablanca, Rabat, etc.">
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Adresse complète</label>
                        <textarea name="adresse" required class="form-control" rows="2" placeholder="Quartier, Rue, N° d'appartement..."></textarea>
                    </div>
                </div>

                <h2 class="section-title" style="margin-top: 40px;"><span>2</span> Paiement</h2>
                <div style="border: 1px solid var(--primary); padding: 20px; border-radius: 8px; display: flex; align-items: center; gap: 15px; background: #f4f8f6;">
                    <i class="fas fa-money-bill-wave" style="font-size: 24px; color: var(--primary);"></i>
                    <div>
                        <strong style="display: block; color: var(--primary); font-size:16px;">Paiement à la livraison</strong>
                        <small style="color: #666;">Vous ne payez qu'à la réception de la marchandise.</small>
                    </div>
                </div>

                <button type="submit" class="btn-pay">
                    Confirmer la commande (<?= number_format($total_final, 2) ?> DH)
                </button>
            </form>
        </div>

        <div class="col-sidebar">
            <div class="summary-sticky">
                <div class="summary-card">
                    <h3>Votre Panier</h3>
                    
                    <div class="product-list">
                        <?php foreach($produits_panier as $p): 
                            $qty = $_SESSION['panier'][$p['id']];
                            $prix = $p['prix_promo'] ? $p['prix_promo'] : $p['prix'];
                        ?>
                        <div class="product-item">
                            <span><?= htmlspecialchars($p['nom']) ?> (x<?= $qty ?>)</span>
                            <span><?= number_format($prix * $qty, 2) ?> DH</span>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="shipping-promo">
                        <span class="promo-text">
                            <?php if($frais_livraison == 0): ?>
                                <i class="fas fa-check-circle" style="color: var(--accent);"></i> Livraison gratuite validée !
                            <?php else: ?>
                                <i class="fas fa-gift"></i> Manque <strong><?= number_format(500 - $total_general, 2) ?> DH</strong> pour la livraison gratuite.
                            <?php endif; ?>
                        </span>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= $pourcentage_livraison ?>%;"></div>
                        </div>
                    </div>

                    <div class="price-row">
                        <span>Sous-total</span>
                        <span><?= number_format($total_general, 2) ?> DH</span>
                    </div>
                    
                    <div class="price-row">
                        <span>Livraison</span>
                        <span><?= ($frais_livraison == 0) ? 'OFFERTE' : number_format($frais_livraison, 2) . ' DH' ?></span>
                    </div>

                    <div class="total-row">
                        <span>TOTAL</span>
                        <span><?= number_format($total_final, 2) ?> DH</span>
                    </div>
                </div>
                
                <p style="text-align: center; font-size: 11px; color: #888; margin-top: 15px;">
                    <i class="fas fa-lock"></i> Transaction sécurisée. Aucune donnée bancaire requise.
                </p>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>