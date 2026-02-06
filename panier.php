<?php
session_start();
require_once 'config.php';

// --- VOTRE LOGIQUE PHP (INTACTE) ---
if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'ajouter':
                $produit_id = (int)$_POST['produit_id'];
                $quantite = (int)$_POST['quantite'];
                if ($produit_id > 0 && $quantite > 0) {
                    if (isset($_SESSION['panier'][$produit_id])) {
                        $_SESSION['panier'][$produit_id] += $quantite;
                    } else {
                        $_SESSION['panier'][$produit_id] = $quantite;
                    }
                    $message = "Produit ajouté au panier !";
                    $message_type = "success";
                }
                break;
            case 'modifier':
                $produit_id = (int)$_POST['produit_id'];
                $quantite = (int)$_POST['quantite'];
                if ($produit_id > 0 && $quantite > 0) {
                    $_SESSION['panier'][$produit_id] = $quantite;
                    $message = "Quantité mise à jour";
                    $message_type = "success";
                } elseif ($quantite == 0) {
                    unset($_SESSION['panier'][$produit_id]);
                    $message = "Produit retiré du panier";
                    $message_type = "info";
                }
                break;
            case 'supprimer':
                $produit_id = (int)$_POST['produit_id'];
                if (isset($_SESSION['panier'][$produit_id])) {
                    unset($_SESSION['panier'][$produit_id]);
                    $message = "Produit retiré du panier";
                    $message_type = "info";
                }
                break;
            case 'vider':
                $_SESSION['panier'] = [];
                unset($_SESSION['code_promo']);
                $message = "Panier vidé";
                $message_type = "info";
                break;
            case 'appliquer_promo':
                $code_promo = trim($_POST['code_promo']);
                if (!empty($code_promo)) {
                    if(!isset($pdo)) $pdo = new PDO("mysql:host=localhost;dbname=sofcos_db;charset=utf8", "root", "");
                    $stmt = $pdo->prepare("SELECT * FROM codes_promo WHERE code = ? AND actif = 1 AND (date_fin IS NULL OR date_fin >= CURDATE())");
                    $stmt->execute([$code_promo]);
                    $promo = $stmt->fetch();
                    if ($promo) {
                        $_SESSION['code_promo'] = $promo;
                        $message = "Code promo appliqué : -" . $promo['valeur'] . ($promo['type'] == 'pourcentage' ? '%' : ' DH');
                        $message_type = "success";
                    } else {
                        $message = "Code promo invalide ou expiré";
                        $message_type = "error";
                    }
                }
                break;
        }
    }
}

// Récupération Produits
$produits_panier = [];
$total = 0;
$total_articles = 0;

if (!empty($_SESSION['panier'])) {
    if(!isset($pdo)) $pdo = new PDO("mysql:host=localhost;dbname=sofcos_db;charset=utf8", "root", "");
    $ids = array_keys($_SESSION['panier']);
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    $stmt = $pdo->prepare("SELECT * FROM produits WHERE id IN ($placeholders) AND actif = 1");
    $stmt->execute($ids);
    while ($produit = $stmt->fetch()) {
        $quantite = $_SESSION['panier'][$produit['id']];
        $prix = $produit['prix_promo'] ?? $produit['prix'];
        $sous_total = $prix * $quantite;
        $produits_panier[] = [
            'id' => $produit['id'], 'nom' => $produit['nom'], 'description' => $produit['description'],
            'prix' => $prix, 'prix_original' => $produit['prix'], 'image' => $produit['image'],
            'stock' => $produit['stock'], 'quantite' => $quantite, 'sous_total' => $sous_total
        ];
        $total += $sous_total;
        $total_articles += $quantite;
    }
}

// Réduction
$reduction = 0;
if (isset($_SESSION['code_promo'])) {
    $promo = $_SESSION['code_promo'];
    if ($promo['type'] == 'pourcentage') {
        $reduction = ($total * $promo['valeur']) / 100;
    } else {
        $reduction = $promo['valeur'];
    }
    if ($total < $promo['montant_minimum']) {
        $reduction = 0;
        unset($_SESSION['code_promo']);
        $message = "Montant minimum non atteint pour ce code promo";
        $message_type = "warning";
    }
}

$total_final = max(0, $total - $reduction);
$frais_livraison = $total_final >= 200 ? 0 : 30;
$total_a_payer = $total_final + $frais_livraison;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Panier - SOFCOS Paris</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Prata&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --green-dark: #1A3C34; --gold: #C5A059; --grey-light: #f9f9f9; --white: #ffffff; }
        body { margin: 0; padding: 0; font-family: 'Montserrat', sans-serif; background-color: var(--grey-light); color: #333; }

        /* HEADER */
        .cart-header { background-color: var(--green-dark); color: var(--white); padding: 60px 20px; text-align: center; margin-bottom: 40px; position: relative; overflow: hidden; }
        .cart-header::after { content: ''; position: absolute; bottom: -20px; left: 0; width: 100%; height: 40px; background: var(--grey-light); border-radius: 50% 50% 0 0; }
        .cart-header h1 { font-family: 'Prata', serif; font-size: 36px; margin: 0; letter-spacing: 1px; }
        .cart-subtitle { color: var(--gold); font-size: 13px; margin-top: 10px; text-transform: uppercase; letter-spacing: 2px; }

        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px 80px 20px; display: flex; gap: 40px; align-items: flex-start; }

        /* ALERTES */
        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; display: flex; align-items: center; gap: 15px; font-size: 14px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .alert-success { background: #e8f5e9; color: #1b5e20; border-left: 4px solid #4caf50; }
        .alert-error { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef5350; }
        .alert-info { background: #e0f2f1; color: #004d40; border-left: 4px solid #26a69a; }
        .alert-warning { background: #fff8e1; color: #f57f17; border-left: 4px solid #ffa000; }

        /* --- STYLE SPÉCIAL PANIER VIDE --- */
        .empty-cart-container {
            width: 100%; text-align: center; background: white; padding: 80px 40px; 
            border-radius: 12px; box-shadow: 0 15px 50px rgba(0,0,0,0.05);
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        .empty-icon-wrapper {
            width: 120px; height: 120px; background: #fdfbf7; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; margin: 0 auto 30px auto;
            position: relative;
        }
        .empty-icon-wrapper i { font-size: 50px; color: var(--gold); }
        .empty-badge {
            position: absolute; top: 0; right: 0; background: var(--green-dark); color: white;
            width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; border: 3px solid white;
        }

        .empty-title { font-family: 'Prata', serif; font-size: 28px; color: var(--green-dark); margin-bottom: 10px; }
        .empty-text { color: #888; margin-bottom: 40px; font-size: 15px; }

        /* Suggestion de liens */
        .quick-links { display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; margin-bottom: 40px; }
        .quick-link-item {
            text-decoration: none; padding: 15px 25px; background: #f9f9f9; border-radius: 8px;
            color: var(--green-dark); font-weight: 600; font-size: 13px; transition: 0.3s;
            display: flex; align-items: center; gap: 8px; border: 1px solid #eee;
        }
        .quick-link-item:hover { background: var(--green-dark); color: white; transform: translateY(-3px); border-color: var(--green-dark); }
        .quick-link-item i { color: var(--gold); }
        .quick-link-item:hover i { color: white; }

        .btn-home {
            display: inline-block; padding: 16px 40px; background: var(--green-dark); color: white;
            text-decoration: none; border-radius: 30px; font-weight: 600; letter-spacing: 1px;
            text-transform: uppercase; font-size: 13px; transition: 0.3s; box-shadow: 0 10px 20px rgba(26,60,52,0.2);
        }
        .btn-home:hover { background: var(--gold); transform: translateY(-2px); }

        /* --- STYLES NORMAUX DU PANIER --- */
        .cart-list { flex: 2; }
        .cart-card { background: var(--white); border-radius: 12px; padding: 25px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 5px 20px rgba(0,0,0,0.03); position: relative; }
        .product-image-wrapper { width: 90px; height: 90px; border-radius: 8px; overflow: hidden; border: 1px solid #eee; flex-shrink: 0; }
        .product-image { width: 100%; height: 100%; object-fit: cover; }
        .product-info { flex: 1; padding: 0 25px; }
        .product-title { font-family: 'Prata', serif; font-size: 18px; color: var(--green-dark); text-decoration: none; display: block; margin-bottom: 8px; }
        .product-price { color: #888; font-size: 14px; font-weight: 500; }
        .old-price { text-decoration: line-through; color: #ccc; margin-right: 8px; font-size: 12px; }
        .qty-form { display: flex; align-items: center; background: #f8f9fa; border-radius: 30px; padding: 5px; border: 1px solid #eee; }
        .qty-btn { background: white; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; color: #333; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 5px rgba(0,0,0,0.05); transition: 0.2s; }
        .qty-btn:hover { background: var(--gold); color: white; }
        .qty-input-display { width: 40px; border: none; background: transparent; text-align: center; font-weight: 600; font-family: 'Montserrat'; font-size: 14px; pointer-events: none; }
        .product-total { font-weight: 700; color: var(--green-dark); font-size: 16px; min-width: 100px; text-align: right; }
        .btn-delete { background: none; border: none; color: #ff6b6b; margin-left: 15px; font-size: 16px; transition: 0.3s; cursor: pointer; padding: 10px; opacity: 0.7; }
        .btn-delete:hover { opacity: 1; transform: scale(1.1); color: #d63031; }
        
        .cart-summary { flex: 1; background: var(--white); padding: 35px; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.05); position: sticky; top: 30px; }
        .summary-title { font-family: 'Prata', serif; font-size: 24px; color: var(--green-dark); border-bottom: 1px solid #eee; padding-bottom: 20px; margin-bottom: 25px; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 15px; color: #666; font-size: 14px; }
        .summary-row.total { margin-top: 25px; padding-top: 20px; border-top: 1px solid #eee; color: var(--green-dark); font-size: 22px; font-weight: 700; }
        .btn-checkout { display: block; width: 100%; padding: 18px; background-color: var(--green-dark); color: white; text-align: center; text-decoration: none; text-transform: uppercase; font-size: 13px; font-weight: 700; letter-spacing: 2px; border-radius: 6px; margin-top: 30px; transition: 0.3s; box-shadow: 0 5px 15px rgba(26, 60, 52, 0.2); }
        .btn-checkout:hover { background-color: var(--gold); transform: translateY(-2px); }
        .btn-empty { background: none; border: none; color: #999; text-decoration: underline; font-size: 12px; cursor: pointer; margin-top: 10px; }
        
        .promo-container { margin-bottom: 25px; }
        .promo-form { display: flex; gap: 10px; }
        .promo-input { flex: 1; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-family: 'Montserrat'; outline: none; }
        .promo-btn { background: #333; color: white; border: none; padding: 0 20px; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600; }
        
        @media (max-width: 900px) {
            .container { flex-direction: column; }
            .quick-links { flex-direction: column; gap: 10px; }
            .cart-card { flex-wrap: wrap; justify-content: center; text-align: center; }
            .product-info { padding: 15px 0; width: 100%; }
            .product-total { width: 100%; text-align: center; margin: 15px 0; }
            .btn-delete { position: absolute; top: 10px; right: 10px; }
        }
        /* --- MODIFICATION : RENDRE L'IMAGE ET LE TITRE INTERACTIFS --- */

/* L'image devient un lien (a) */
.product-image-link {
    display: block;
    width: 110px; 
    height: 110px;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #eee;
    flex-shrink: 0;
    transition: all 0.3s ease;
}

/* Effet au survol de l'image */
.product-image-link:hover {
    border-color: var(--gold); /* Bordure dorée */
    transform: scale(1.05);    /* Léger zoom */
    opacity: 0.9;
}

.product-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Le titre du produit */
.product-title {
    font-family: 'Prata', serif;
    font-size: 20px;
    color: var(--green-dark);
    text-decoration: none;
    display: block;
    margin-bottom: 10px;
    transition: color 0.3s;
}

/* Effet au survol du titre */
.product-title:hover {
    color: var(--gold); /* Change de couleur au survol */
    text-decoration: underline;
}
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <div class="cart-header">
        <h1><i class="fas fa-shopping-bag" style="color: #C5A059; margin-right:10px;"></i> Votre Panier</h1>
        <?php if ($total_articles > 0): ?>
            <p class="cart-subtitle"><?= $total_articles ?> Pièce(s) sélectionnée(s)</p>
        <?php endif; ?>
    </div>

    <div class="container">

        <?php if ($message): ?>
            <div style="width: 100%;">
                <div class="alert alert-<?= $message_type ?>">
                    <?php 
                        $icon = 'info-circle';
                        if($message_type == 'success') $icon = 'check-circle';
                        if($message_type == 'error') $icon = 'times-circle';
                        if($message_type == 'warning') $icon = 'exclamation-triangle';
                    ?>
                    <i class="fas fa-<?= $icon ?>"></i>
                    <?= htmlspecialchars($message) ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($produits_panier)): ?>
            <div class="empty-cart-container">
                <div class="empty-icon-wrapper">
                    <i class="fas fa-shopping-basket"></i>
                    <div class="empty-badge">0</div>
                </div>
                
                <h2 class="empty-title">Votre panier est actuellement vide</h2>
                <p class="empty-text">L'élégance n'attend que vous. Découvrez nos collections exclusives et laissez-vous tenter.</p>

                <div class="quick-links">
                    <a href="produits.php?categorie=nouveaute" class="quick-link-item">
                        <i class="fas fa-star"></i> Nouveautés
                    </a>
                    <a href="produits.php?categorie=promotions" class="quick-link-item">
                        <i class="fas fa-tags"></i> Promotions
                    </a>
                    <a href="produits.php" class="quick-link-item">
                        <i class="fas fa-gem"></i> Best-Sellers
                    </a>
                </div>

                <a href="produits.php" class="btn-home">
                    Découvrir la boutique
                </a>
            </div>
        
        <?php else: ?>
            
            <div class="cart-list">
                <?php foreach ($produits_panier as $item): ?>
    <div class="cart-card">
        
        <a href="produit-detail.php?id=<?= $item['id'] ?>" class="product-image-link" title="Voir la fiche produit">
            <img src="uploads/produits/<?= htmlspecialchars($item['image']) ?>" 
                 alt="<?= htmlspecialchars($item['nom']) ?>" 
                 class="product-image"
                 onerror="this.src='https://via.placeholder.com/110?text=SOFCOS'">
        </a>

        <div class="product-info">
            <a href="produit-detail.php?id=<?= $item['id'] ?>" class="product-title">
                <?= htmlspecialchars($item['nom']) ?>
            </a>
            
            <div class="product-price">
                <?php if ($item['prix'] < $item['prix_original']): ?>
                    <span class="old-price"><?= number_format($item['prix_original'], 2) ?> DH</span>
                <?php endif; ?>
                <?= number_format($item['prix'], 2) ?> DH
            </div>
            
            <?php if ($item['stock'] < 5): ?>
                 <div style="font-size: 11px; color: #e67e22; margin-top:5px;">
                    <i class="fas fa-exclamation-circle"></i> Plus que <?= $item['stock'] ?> en stock
                 </div>
            <?php endif; ?>
        </div>

        <form method="POST" class="qty-form">
            <input type="hidden" name="action" value="modifier">
            <input type="hidden" name="produit_id" value="<?= $item['id'] ?>">
            <button type="submit" name="quantite" value="<?= max(0, $item['quantite'] - 1) ?>" class="qty-btn"><i class="fas fa-minus" style="font-size:10px;"></i></button>
            <input type="text" value="<?= $item['quantite'] ?>" class="qty-input-display" readonly>
            <button type="submit" name="quantite" value="<?= min($item['stock'], $item['quantite'] + 1) ?>" class="qty-btn" <?= $item['quantite'] >= $item['stock'] ? 'disabled' : '' ?>><i class="fas fa-plus" style="font-size:10px;"></i></button>
        </form>

        <div class="product-total"><?= number_format($item['sous_total'], 2) ?> DH</div>

        <form method="POST">
            <input type="hidden" name="action" value="supprimer">
            <input type="hidden" name="produit_id" value="<?= $item['id'] ?>">
            <button type="submit" class="btn-delete" onclick="return confirm('Retirer ce produit ?')"><i class="far fa-trash-alt"></i></button>
        </form>

    </div>
<?php endforeach; ?>
                
                <div style="text-align: right;">
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="vider">
                        <button type="submit" class="btn-empty" onclick="return confirm('Vider tout le panier ?')"><i class="fas fa-times"></i> Vider le panier</button>
                    </form>
                </div>
            </div>

            <div class="cart-summary">
                <div class="summary-title"><i class="fas fa-receipt" style="color: #C5A059; margin-right:10px;"></i> Récapitulatif</div>
                
                <div class="promo-container">
                    <form method="POST" class="promo-form">
                        <input type="hidden" name="action" value="appliquer_promo">
                        <input type="text" name="code_promo" class="promo-input" placeholder="Code Promo" value="<?= isset($_SESSION['code_promo']) ? htmlspecialchars($_SESSION['code_promo']['code']) : '' ?>">
                        <button type="submit" class="promo-btn">OK</button>
                    </form>
                </div>

                <div class="summary-row"><span>Sous-total</span><span><?= number_format($total, 2) ?> DH</span></div>
                <?php if ($reduction > 0): ?>
                    <div class="summary-row reduction"><span>Réduction</span><span>-<?= number_format($reduction, 2) ?> DH</span></div>
                <?php endif; ?>
                <div class="summary-row"><span>Livraison</span><span><?= $frais_livraison > 0 ? number_format($frais_livraison, 2) . ' DH' : '<span style="color:#27ae60">Gratuite</span>' ?></span></div>

                <div class="summary-row total"><span>Total</span><span><?= number_format($total_a_payer, 2) ?> DH</span></div>

                <a href="commande.php" class="btn-checkout"><i class="fas fa-lock"></i> Finaliser votre commande</a>
                
                <div style="margin-top:20px; text-align:center; font-size:12px; color:#aaa;">
                    <i class="fas fa-truck"></i> Livraison rapide 24h/48h
                </div>
            </div>

        <?php endif; ?>

    </div>

    <?php include 'includes/footer.php'; ?>

</body>
</html>