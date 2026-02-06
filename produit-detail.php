<?php
session_start();
require_once 'config.php';

// --- LOGIQUE PHP ---

// 1. Vérification ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: produits.php');
    exit();
}
$id = (int)$_GET['id'];

// 2. Connexion et Récupération du produit
try {
    if(!isset($pdo)) {
        $pdo = new PDO("mysql:host=localhost;dbname=sofcos_db;charset=utf8", "root", "");
    }

    $stmt = $pdo->prepare("
        SELECT p.*, c.nom as categorie_nom, c.id as categorie_id 
        FROM produits p 
        LEFT JOIN categories c ON p.categorie_id = c.id 
        WHERE p.id = ? AND p.actif = 1
    ");
    $stmt->execute([$id]);
    $produit = $stmt->fetch();

    if (!$produit) { header('Location: produits.php'); exit(); }
// --- AJOUT : Récupérer la galerie d'images ---
    $stmt_galerie = $pdo->prepare("SELECT * FROM produits_images WHERE produit_id = ?");
    $stmt_galerie->execute([$id]);
    $galerie_images = $stmt_galerie->fetchAll();
    // Produits similaires (Même catégorie, exclure le produit actuel)
    $stmt_sim = $pdo->prepare("
        SELECT * FROM produits 
        WHERE categorie_id = ? AND id != ? AND actif = 1 
        LIMIT 4
    ");
    $stmt_sim->execute([$produit['categorie_id'], $id]);
    $produits_similaires = $stmt_sim->fetchAll();

} catch (PDOException $e) { die("Erreur : " . $e->getMessage()); }

// Gestion image principale
$img_path = !empty($produit['image']) ? 'uploads/produits/' . $produit['image'] : 'images/default.jpg';
if (!file_exists($img_path)) { $img_path = 'images/default.jpg'; }

// Gestion Marque
$marque = isset($produit['marque']) && !empty($produit['marque']) ? $produit['marque'] : 'SOFCOS Paris';

// --- SIMULATION AVIS CLIENTS ---
$avis_clients = [
    ['user' => 'Sarah B.', 'note' => 5, 'date' => '12 Oct 2023', 'text' => 'Une merveille ! La texture est incroyable et l\'odeur divine. Je recommande.'],
    ['user' => 'Karim L.', 'note' => 4, 'date' => '05 Sept 2023', 'text' => 'Très bon produit, livraison rapide. Le packaging est très élégant.'],
    ['user' => 'Mouna K.', 'note' => 5, 'date' => '28 Août 2023', 'text' => 'Je ne peux plus m\'en passer. Ma peau est beaucoup plus douce.']
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($produit['nom']) ?> - SOFCOS</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- CHARTE GRAPHIQUE LUXE --- */
        :root {
            --shop-green: #1A3C34;
            --shop-gold: #d4c4a8;
            --bg-body: #fdfdfd;
            --text-dark: #222;
            --border-light: #e5e5e5;
        }

        body { background-color: var(--bg-body); font-family: 'Roboto', sans-serif; color: var(--text-dark); margin: 0; padding: 0; }
        
        /* Layout global */
        .container { max-width: 1250px; margin: 0 auto; padding: 0 20px; }

        /* Fil d'Ariane */
        .breadcrumb { 
            padding: 25px 0; margin-bottom: 30px; font-size: 13px; color: #888; text-transform: uppercase; letter-spacing: 1px;
            border-bottom: 1px solid var(--border-light);
        }
        .breadcrumb a { color: #888; text-decoration: none; transition: 0.3s; }
        .breadcrumb a:hover { color: var(--shop-green); }
        .breadcrumb span { color: var(--shop-green); font-weight: 500; }
        .breadcrumb i { margin: 0 10px; font-size: 10px; color: #ccc; }

        /* --- FICHE PRODUIT --- */
        .product-wrapper { display: flex; flex-wrap: wrap; gap: 60px; margin-bottom: 80px; }
        .product-gallery { flex: 1; min-width: 350px; }
        .main-img-box { width: 100%; background-color: #f9f9f9; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 1px solid var(--border-light); }
        .main-img-box img { width: 100%; height: auto; max-height: 600px; object-fit: contain; display: block; transition: transform 0.5s ease; }
        .main-img-box:hover img { transform: scale(1.05); }

        .product-info { flex: 1; min-width: 350px; padding-top: 10px; }
        .pi-brand { color: var(--shop-gold); font-family: 'Playfair Display', serif; font-style: italic; font-size: 18px; margin-bottom: 15px; display: block; }
        .pi-title { font-family: 'Playfair Display', serif; font-size: 38px; font-weight: 400; margin: 0 0 20px 0; line-height: 1.2; text-transform: uppercase; letter-spacing: 1px; color: var(--shop-green); }
        
        .pi-price-box { display: flex; align-items: baseline; gap: 15px; margin-bottom: 25px; padding-bottom: 25px; border-bottom: 1px solid var(--border-light); }
        .pi-price { font-size: 26px; font-weight: 500; color: var(--shop-green); }
        .pi-old-price { text-decoration: line-through; color: #bbb; font-size: 16px; font-weight: 300; }
        .pi-badge { background: var(--shop-gold); color: white; padding: 4px 10px; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; border-radius: 0; }
        
        .pi-desc-short { color: #555; line-height: 1.8; margin-bottom: 35px; font-weight: 300; font-size: 15px; }

        /* Actions */
        .pi-actions { display: flex; gap: 20px; align-items: stretch; margin-bottom: 40px; }
        .qty-input { width: 70px; text-align: center; border: 1px solid var(--shop-green); font-family: 'Roboto', sans-serif; font-size: 16px; color: var(--shop-green); outline: none; border-radius: 0; background: transparent; }
        .btn-add-luxe { flex-grow: 1; background: var(--shop-green); color: white; border: 1px solid var(--shop-green); text-transform: uppercase; font-size: 13px; font-weight: 700; letter-spacing: 2px; cursor: pointer; transition: 0.3s; padding: 16px 30px; border-radius: 0; }
        .btn-add-luxe:hover { background: white; color: var(--shop-green); }
        .btn-disabled { background: #eee; border-color: #eee; color: #aaa; cursor: not-allowed; }

        /* Réassurance */
        .reassurance-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; border-top: 1px solid var(--border-light); padding-top: 30px; }
        .rea-item { display: flex; align-items: flex-start; gap: 15px; }
        .rea-item i { color: var(--shop-gold); font-size: 20px; margin-top: 2px; }
        .rea-content h4 { margin: 0 0 5px 0; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: var(--shop-green); font-weight: 700; }
        .rea-content p { margin: 0; font-size: 13px; color: #888; font-weight: 300; }

        /* --- SECTION CONTENU (Description & Avis) --- */
        .content-sections { margin-top: 60px; margin-bottom: 100px; display: grid; grid-template-columns: 1.5fr 1fr; gap: 60px; }
        .sec-header { border-bottom: 1px solid var(--border-light); margin-bottom: 25px; }
        .sec-title-sm { display: inline-block; font-family: 'Playfair Display', serif; font-size: 22px; padding-bottom: 10px; border-bottom: 2px solid var(--shop-green); margin-bottom: -1px; color: var(--shop-green); text-transform: uppercase; }
        .text-content { color: #555; line-height: 1.8; font-weight: 300; font-size: 15px; text-align: justify; }

        /* Avis Clients */
        .reviews-list { margin-bottom: 40px; }
        .review-item { margin-bottom: 25px; border-bottom: 1px solid #f0f0f0; padding-bottom: 20px; }
        .review-header { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .review-author { font-weight: 700; color: var(--text-dark); font-size: 14px; }
        .review-date { color: #999; font-size: 12px; font-style: italic; }
        .review-stars { color: var(--shop-gold); font-size: 12px; margin-bottom: 8px; }
        .review-text { color: #666; font-size: 14px; line-height: 1.6; font-style: italic; }

        .review-form-box { background: #f9f9f9; padding: 25px; border: 1px solid var(--border-light); }
        .rf-title { font-family: 'Playfair Display'; font-size: 18px; color: var(--shop-green); margin-bottom: 15px; }
        .rf-input, .rf-area { width: 100%; padding: 12px; border: 1px solid #ddd; background: white; margin-bottom: 15px; outline: none; border-radius: 0; font-family: 'Roboto'; box-sizing: border-box; }
        .rf-btn { background: var(--shop-gold); color: white; border: none; padding: 12px 25px; text-transform: uppercase; font-weight: 700; cursor: pointer; border-radius: 0; transition: 0.3s; width: 100%; }
        .rf-btn:hover { background: var(--shop-green); }


        /* =========================================
           DESIGN SPÉCIAL "VOUS AIMEREZ AUSSI" 
           ========================================= */
        .related-section { 
            margin-bottom: 100px; 
            padding-top: 60px; 
            position: relative;
        }

        /* Titre décoré */
        .rel-title-box { text-align: center; position: relative; margin-bottom: 50px; }
        .rel-title-box::before {
            content: ''; position: absolute; top: 50%; left: 0; width: 100%; height: 1px; background: #e5e5e5; z-index: 1;
        }
        .rel-title { 
            display: inline-block; background: var(--bg-body); padding: 0 30px; position: relative; z-index: 2;
            font-family: 'Playfair Display', serif; font-size: 28px; color: var(--shop-green); 
            text-transform: uppercase; letter-spacing: 3px;
        }

        .rel-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 30px; }

        /* Carte Spéciale "Atmosphère" */
        .special-card {
            background: white;
            transition: all 0.5s cubic-bezier(0.25, 0.8, 0.25, 1);
            border: 1px solid transparent; /* Bordure invisible par défaut */
            position: relative;
            overflow: hidden;
            display: flex; flex-direction: column;
        }

        /* L'image prend de la place */
        .sc-img-wrap {
            height: 350px; 
            width: 100%; 
            position: relative; 
            overflow: hidden; 
            background: #fbfbfb;
            display: flex; align-items: center; justify-content: center;
        }
        
        .sc-img-wrap img {
            max-width: 90%; max-height: 90%; object-fit: contain;
            transition: transform 0.6s ease, opacity 0.4s ease, filter 0.4s ease;
        }

        /* EFFET SURVOL SPÉCIAL */
        .special-card:hover .sc-img-wrap img {
            transform: scale(1.1);
            opacity: 0.5; /* L'image s'assombrit légèrement */
            filter: blur(2px); /* Léger flou artistique */
        }

        /* Bouton "DÉCOUVRIR" qui apparaît au centre */
        .sc-overlay-btn {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, 50%); /* Décalé vers le bas par défaut */
            opacity: 0;
            background-color: var(--shop-green);
            color: var(--shop-gold);
            padding: 15px 30px;
            font-family: 'Playfair Display', serif;
            font-size: 14px; text-transform: uppercase; letter-spacing: 1px;
            text-decoration: none;
            transition: all 0.4s ease;
            white-space: nowrap;
            border: 1px solid var(--shop-green);
        }

        .special-card:hover .sc-overlay-btn {
            opacity: 1;
            transform: translate(-50%, -50%); /* Revient au centre exact */
        }
        
        .sc-overlay-btn:hover {
            background-color: transparent; color: var(--shop-green); border-color: var(--shop-green);
            font-weight: 700;
        }

        /* Infos en bas minimalistes */
        .sc-info { padding: 20px; text-align: center; border-top: 1px solid transparent; transition: 0.3s; }
        .special-card:hover .sc-info { border-top-color: var(--shop-gold); }

        .sc-cat { font-size: 10px; text-transform: uppercase; color: #999; letter-spacing: 2px; display: block; margin-bottom: 5px; }
        .sc-title { 
            font-family: 'Playfair Display', serif; font-size: 18px; color: #222; text-decoration: none; display: block; margin-bottom: 5px; 
        }
        .sc-price { color: var(--shop-green); font-weight: 500; }

        @media (max-width: 900px) {
            .product-wrapper { flex-direction: column; gap: 30px; }
            .content-sections { grid-template-columns: 1fr; gap: 40px; }
            .pi-actions { flex-direction: column; }
            .qty-input { width: 100%; padding: 10px; }
        }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <div class="container">
        
        <div class="breadcrumb">
            <a href="index.php">Accueil</a> <i class="fas fa-chevron-right"></i>
            <a href="produits.php">Boutique</a> <i class="fas fa-chevron-right"></i>
            <a href="produits.php?categorie=<?= $produit['categorie_id'] ?>"><?= htmlspecialchars($produit['categorie_nom']) ?></a> <i class="fas fa-chevron-right"></i>
            <span><?= htmlspecialchars($produit['nom']) ?></span>
        </div>

        <div class="product-wrapper">
            
            <div class="product-gallery">
    <div class="main-image-container">
        <img id="mainImage" src="<?= htmlspecialchars($img_path) ?>" alt="<?= htmlspecialchars($produit['nom']) ?>">
    </div>

    <div class="thumbnails-row">
        <div class="thumb-item active" onclick="changeImage(this, '<?= htmlspecialchars($img_path) ?>')">
            <img src="<?= htmlspecialchars($img_path) ?>">
        </div>

        <?php foreach($galerie_images as $img): ?>
            <?php $galerie_path = 'uploads/produits/' . $img['chemin_image']; ?>
            <?php if(file_exists($galerie_path)): ?>
                <div class="thumb-item" onclick="changeImage(this, '<?= $galerie_path ?>')">
                    <img src="<?= $galerie_path ?>" alt="Vue supplémentaire">
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
<style>
    .main-image-container img {
        width: 100%;
        height: 400px; /* Hauteur fixe pour éviter les sauts */
        object-fit: contain; /* L'image ne sera pas déformée */
        border-radius: 12px;
        border: 1px solid #eee;
        background: #fff;
    }

    .thumbnails-row {
        display: flex;
        gap: 10px;
        margin-top: 15px;
        overflow-x: auto; /* Permet de scroller si trop d'images */
    }

    .thumb-item {
        width: 70px;
        height: 70px;
        border: 2px solid transparent;
        border-radius: 8px;
        cursor: pointer;
        opacity: 0.6;
        transition: 0.3s;
    }

    .thumb-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 6px;
    }

    .thumb-item:hover, .thumb-item.active {
        border-color: #10b981; /* Couleur verte SOFCOS */
        opacity: 1;
    }
</style>

<script>
    function changeImage(element, src) {
        // 1. Changer la grande image
        document.getElementById('mainImage').src = src;
        
        // 2. Gérer la classe "active" (bordure verte)
        // Enlever 'active' de toutes les miniatures
        document.querySelectorAll('.thumb-item').forEach(el => el.classList.remove('active'));
        // Ajouter 'active' sur celle cliquée
        element.classList.add('active');
    }
</script>

            <div class="product-info">
                <span class="pi-brand"><?= htmlspecialchars($marque) ?></span>
                <h1 class="pi-title"><?= htmlspecialchars($produit['nom']) ?></h1>

                <div class="pi-price-box">
                    <?php if ($produit['prix_promo']): ?>
                        <span class="pi-price"><?= number_format($produit['prix_promo'], 2) ?> MAD</span>
                        <span class="pi-old-price"><?= number_format($produit['prix'], 2) ?></span>
                        <span class="pi-badge">-<?= round((($produit['prix'] - $produit['prix_promo']) / $produit['prix']) * 100) ?>%</span>
                    <?php else: ?>
                        <span class="pi-price"><?= number_format($produit['prix'], 2) ?> MAD</span>
                    <?php endif; ?>
                </div>

                <div class="pi-desc-short">
                    <p><?= nl2br(htmlspecialchars(substr($produit['description'], 0, 150))) ?>...</p>
                </div>

                <form action="panier.php" method="POST">
                    <input type="hidden" name="action" value="ajouter">
                    <input type="hidden" name="produit_id" value="<?= $produit['id'] ?>">

                    <div class="pi-actions">
                        <?php if ($produit['stock'] > 0): ?>
                            <input type="number" name="quantite" value="1" min="1" max="<?= $produit['stock'] ?>" class="qty-input">
                            <button type="submit" class="btn-add-luxe">Ajouter au panier</button>
                        <?php else: ?>
                            <input type="number" value="0" disabled class="qty-input" style="background:#eee; border-color:#eee;">
                            <button type="button" class="btn-add-luxe btn-disabled" disabled>Rupture de stock</button>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="reassurance-grid">
                    <div class="rea-item"><i class="fas fa-truck"></i><div class="rea-content"><h4>Livraison Express</h4><p>Partout au Maroc</p></div></div>
                    <div class="rea-item"><i class="fas fa-leaf"></i><div class="rea-content"><h4>100% Naturel</h4><p>Ingrédients certifiés</p></div></div>
                    <div class="rea-item"><i class="fas fa-shield-alt"></i><div class="rea-content"><h4>Authentique</h4><p>Produits origine garantie</p></div></div>
                    <div class="rea-item"><i class="fas fa-phone-alt"></i><div class="rea-content"><h4>Support</h4><p>Disponible 7j/7</p></div></div>
                </div>
            </div>
        </div>

        <div class="content-sections">
            
            <div class="details-col">
                <div class="sec-header"><span class="sec-title-sm">Description</span></div>
                <div class="text-content">
                    <?= nl2br(htmlspecialchars($produit['description'])) ?>
                </div>
            </div>

            <div class="reviews-col">
                <div class="sec-header"><span class="sec-title-sm">Avis Clients</span></div>
                
                <div class="reviews-list">
                    <?php foreach($avis_clients as $avis): ?>
                        <div class="review-item">
                            <div class="review-header">
                                <span class="review-author"><?= htmlspecialchars($avis['user']) ?></span>
                                <span class="review-date"><?= htmlspecialchars($avis['date']) ?></span>
                            </div>
                            <div class="review-stars">
                                <?php for($i=0; $i<$avis['note']; $i++) echo '<i class="fas fa-star"></i>'; ?>
                                <?php for($i=$avis['note']; $i<5; $i++) echo '<i class="far fa-star"></i>'; ?>
                            </div>
                            <div class="review-text">"<?= htmlspecialchars($avis['text']) ?>"</div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="review-form-box">
                    <h4 class="rf-title">Donnez votre avis</h4>
                    <form action="#" method="POST">
                        <select name="note" class="rf-input">
                            <option value="5">★★★★★ (Excellent)</option>
                            <option value="4">★★★★☆ (Très bien)</option>
                            <option value="3">★★★☆☆ (Bien)</option>
                        </select>
                        <input type="text" name="nom" placeholder="Votre nom" class="rf-input" required>
                        <textarea name="commentaire" rows="2" placeholder="Votre commentaire..." class="rf-area" required></textarea>
                        <button type="submit" class="rf-btn">Publier</button>
                    </form>
                </div>
            </div>

        </div>

        <?php if(count($produits_similaires) > 0): ?>
            <div class="related-section">
                
                <div class="rel-title-box">
                    <h3 class="rel-title">Vous aimerez aussi</h3>
                </div>

                <div class="rel-grid">
                    <?php foreach ($produits_similaires as $sim): ?>
                        <?php 
                            $sim_img = !empty($sim['image']) ? 'uploads/produits/' . $sim['image'] : 'images/default.jpg';
                            if (!file_exists($sim_img)) $sim_img = 'images/default.jpg';
                        ?>
                        
                        <div class="special-card">
                            <div class="sc-img-wrap">
                                <img src="<?= htmlspecialchars($sim_img) ?>" alt="<?= htmlspecialchars($sim['nom']) ?>">
                                <a href="produit-detail.php?id=<?= $sim['id'] ?>" class="sc-overlay-btn">
                                    DÉCOUVRIR
                                </a>
                            </div>
                            
                            <div class="sc-info">
                                <span class="sc-cat">SOFCOS</span>
                                <a href="produit-detail.php?id=<?= $sim['id'] ?>" class="sc-title"><?= htmlspecialchars($sim['nom']) ?></a>
                                <div class="sc-price">
                                    <?= number_format($sim['prix_promo'] ?: $sim['prix'], 2) ?> MAD
                                </div>
                            </div>
                        </div>

                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>