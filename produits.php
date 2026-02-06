<?php
session_start();
require_once 'config.php';

// --- LOGIQUE PHP ---
if(!isset($pdo)) {
    try { $pdo = new PDO("mysql:host=localhost;dbname=sofcos_db;charset=utf8", "root", ""); } 
    catch(Exception $e) { die("Erreur BDD"); }
}

// 1. Récupération des catégories
$stmt_cat = $pdo->query("SELECT * FROM categories ORDER BY nom");
$categories = $stmt_cat->fetchAll();

// 2. Récupération dynamique des MARQUES (Nouveau)
// On prend seulement les marques utilisées par des produits actifs
$stmt_marques = $pdo->query("SELECT DISTINCT marque FROM produits WHERE actif = 1 AND marque IS NOT NULL AND marque != '' ORDER BY marque ASC");
$toutes_marques = $stmt_marques->fetchAll(PDO::FETCH_COLUMN);

// 3. Construction de la requête Produits
$sql = "SELECT * FROM produits WHERE actif = 1";
$params = [];

// --- FILTRES ---

// Recherche
if (!empty($_GET['search'])) {
    $sql .= " AND (nom LIKE ? OR description LIKE ?)";
    $params[] = "%" . $_GET['search'] . "%";
    $params[] = "%" . $_GET['search'] . "%";
}

// Filtre Catégorie (Multiple)
if (!empty($_GET['categorie'])) {
    if (is_array($_GET['categorie'])) {
        $in  = str_repeat('?,', count($_GET['categorie']) - 1) . '?';
        $sql .= " AND categorie_id IN ($in)";
        $params = array_merge($params, $_GET['categorie']);
    } else {
        $sql .= " AND categorie_id = ?";
        $params[] = $_GET['categorie'];
    }
}

// Filtre Marque (Multiple) - NOUVEAU
if (!empty($_GET['marque'])) {
    if (is_array($_GET['marque'])) {
        // Crée une chaine "?,?,?" selon le nombre de marques cochées
        $in_m  = str_repeat('?,', count($_GET['marque']) - 1) . '?';
        $sql .= " AND marque IN ($in_m)";
        $params = array_merge($params, $_GET['marque']);
    }
}

// Filtre Prix
if (!empty($_GET['min_price'])) {
    $sql .= " AND (CASE WHEN prix_promo IS NOT NULL THEN prix_promo ELSE prix END) >= ?";
    $params[] = $_GET['min_price'];
}
if (!empty($_GET['max_price'])) {
    $sql .= " AND (CASE WHEN prix_promo IS NOT NULL THEN prix_promo ELSE prix END) <= ?";
    $params[] = $_GET['max_price'];
}

// Tri
$sort = $_GET['sort'] ?? 'newest';
switch ($sort) {
    case 'price_asc':  $sql .= " ORDER BY (CASE WHEN prix_promo IS NOT NULL THEN prix_promo ELSE prix END) ASC"; break;
    case 'price_desc': $sql .= " ORDER BY (CASE WHEN prix_promo IS NOT NULL THEN prix_promo ELSE prix END) DESC"; break;
    case 'name':       $sql .= " ORDER BY nom ASC"; break;
    default:           $sql .= " ORDER BY id DESC"; break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produits = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Collection Boutique - SOFCOS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        
        /* En-tête */
        .shop-header-banner {
            background-color: var(--shop-green);
            color: white;
            text-align: center;
            padding: 60px 20px;
            margin-bottom: 50px;
        }
        .sh-title { font-family: 'Playfair Display', serif; font-size: 38px; font-weight: 400; letter-spacing: 2px; margin: 0; text-transform: uppercase; }
        .sh-subtitle { font-family: 'Playfair Display', serif; color: var(--shop-gold); font-style: italic; margin-top: 10px; font-size: 16px; }

        .shop-container {
            display: flex;
            max-width: 1350px;
            margin: 0 auto;
            padding: 0 20px;
            gap: 50px;
            margin-bottom: 80px;
        }

        /* --- SIDEBAR --- */
        .sidebar {
            width: 260px;
            flex-shrink: 0;
            padding-right: 20px;
            border-right: 1px solid var(--border-light);
        }

        .filter-group { margin-bottom: 35px; }
        .filter-title {
            font-family: 'Playfair Display', serif;
            font-size: 16px; font-weight: 700; color: var(--shop-green);
            margin-bottom: 18px; text-transform: uppercase; letter-spacing: 1px;
            position: relative;
        }
        .filter-title::after { content: ''; display: block; width: 30px; height: 1px; background: var(--shop-gold); margin-top: 5px; }

        .checkbox-label { display: flex; align-items: center; margin-bottom: 12px; cursor: pointer; font-size: 14px; color: #555; transition: 0.2s; }
        .checkbox-label:hover { color: var(--shop-green); }
        .checkbox-label input { display: none; }
        .checkmark { width: 16px; height: 16px; border: 1px solid #ccc; margin-right: 12px; display: flex; align-items: center; justify-content: center; background: white; }
        .checkbox-label input:checked + .checkmark { background-color: var(--shop-green); border-color: var(--shop-green); }
        .checkbox-label input:checked + .checkmark::after { content: ''; width: 4px; height: 8px; border: solid white; border-width: 0 2px 2px 0; transform: rotate(45deg); margin-top: -2px; }

        .price-row { display: flex; gap: 10px; align-items: center; }
        .price-input { width: 100%; padding: 10px; border: 1px solid #ccc; font-size: 13px; text-align: center; outline: none; border-radius: 0; }
        .price-input:focus { border-color: var(--shop-green); }

        .color-grid { display: flex; flex-wrap: wrap; gap: 10px; }
        .color-item { width: 28px; height: 28px; border-radius: 50%; cursor: pointer; position: relative; border: 1px solid #eee; }
        .color-item input { display: none; }
        .color-item input:checked + span { box-shadow: 0 0 0 2px white, 0 0 0 3px var(--shop-green); width: 100%; height: 100%; border-radius: 50%; display: block; }

        .btn-filter { width: 100%; padding: 14px; background: var(--shop-green); color: white; border: none; text-transform: uppercase; font-weight: 700; font-size: 12px; letter-spacing: 1px; cursor: pointer; transition: 0.3s; }
        .btn-filter:hover { background: #122b25; }

        /* --- CONTENU --- */
        .products-content { flex-grow: 1; }

        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 15px; border-bottom: 1px solid var(--border-light); }
        .res-count { font-size: 13px; color: #888; text-transform: uppercase; letter-spacing: 1px; }
        .sort-select { border: none; background: transparent; font-family: 'Roboto', sans-serif; color: #444; cursor: pointer; font-size: 14px; outline: none; font-weight: 500; }

        .prod-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 40px; }

        .prod-card { background: white; transition: all 0.4s ease; position: relative; }
        
        .pc-img-wrap {
            height: 340px; width: 100%; position: relative; overflow: hidden;
            background-color: #f9f9f9; display: flex; align-items: center; justify-content: center;
        }
        .pc-img-wrap img {
            max-width: 100%; max-height: 100%; object-fit: cover; transition: transform 0.8s ease;
        }
        .prod-card:hover .pc-img-wrap img { transform: scale(1.08); }

        .pc-badge {
            position: absolute; top: 0; left: 0; background: var(--shop-gold); color: white;
            font-size: 11px; font-weight: 700; padding: 6px 12px; z-index: 2; text-transform: uppercase; letter-spacing: 1px;
        }

        .pc-info { padding: 20px 0; text-align: center; }
        
        .pc-cat { color: #999; font-size: 11px; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 8px; display: block; }
        
        .pc-title {
            font-family: 'Playfair Display', serif; font-size: 18px; color: #222;
            text-decoration: none; font-weight: 600; display: block; margin-bottom: 8px; transition: 0.3s;
        }
        .pc-title:hover { color: var(--shop-gold); }

        .pc-price { font-size: 16px; font-weight: 500; color: var(--shop-green); }
        .pc-old-price { text-decoration: line-through; color: #ccc; font-size: 14px; margin-left: 8px; font-weight: 300; }

        .btn-add-text {
            display: block; width: 100%; margin-top: 15px;
            border: 1px solid var(--border-light); background: transparent; color: var(--text-dark);
            padding: 12px 0; text-transform: uppercase; font-size: 11px; font-weight: 700; letter-spacing: 2px;
            cursor: pointer; transition: 0.3s;
        }
        .btn-add-text:hover { border-color: var(--shop-green); background: var(--shop-green); color: white; }

        @media (max-width: 992px) {
            .shop-container { flex-direction: column; }
            .sidebar { width: 100%; border-right: none; margin-bottom: 40px; }
            .prod-grid { grid-template-columns: repeat(2, 1fr); gap: 20px; }
        }
        @media (max-width: 600px) { .prod-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <div class="shop-header-banner">
        <h1 class="sh-title">Boutique</h1>
        <div class="sh-subtitle">L'essence de la nature marocaine</div>
    </div>

    <div class="shop-container">
        
        <aside class="sidebar">
            <form action="produits.php" method="GET" id="filterForm">
                <?php if(isset($_GET['search'])): ?><input type="hidden" name="search" value="<?= htmlspecialchars($_GET['search']) ?>"><?php endif; ?>

                <div class="filter-group">
                    <div class="filter-title">Prix</div>
                    <div class="price-row">
                        <input type="number" name="min_price" class="price-input" placeholder="Min" value="<?= isset($_GET['min_price']) ? htmlspecialchars($_GET['min_price']) : '' ?>">
                        <span style="color:#ccc;">-</span>
                        <input type="number" name="max_price" class="price-input" placeholder="Max" value="<?= isset($_GET['max_price']) ? htmlspecialchars($_GET['max_price']) : '' ?>">
                    </div>
                </div>

                <div class="filter-group">
                    <div class="filter-title">Catégories</div>
                    <?php foreach($categories as $cat): ?>
                        <label class="checkbox-label">
                            <?php 
                                $checked = '';
                                if(isset($_GET['categorie'])) {
                                    if(is_array($_GET['categorie']) && in_array($cat['id'], $_GET['categorie'])) $checked = 'checked';
                                    elseif($_GET['categorie'] == $cat['id']) $checked = 'checked';
                                }
                            ?>
                            <input type="checkbox" name="categorie[]" value="<?= $cat['id'] ?>" <?= $checked ?>>
                            <span class="checkmark"></span>
                            <?= htmlspecialchars($cat['nom']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <?php if(!empty($toutes_marques)): ?>
                    <div class="filter-group">
                        <div class="filter-title">Marque</div>
                        <?php foreach($toutes_marques as $marque): ?>
                            <label class="checkbox-label">
                                <?php 
                                    $m_checked = '';
                                    if(isset($_GET['marque']) && is_array($_GET['marque']) && in_array($marque, $_GET['marque'])) {
                                        $m_checked = 'checked';
                                    }
                                ?>
                                <input type="checkbox" name="marque[]" value="<?= htmlspecialchars($marque) ?>" <?= $m_checked ?>>
                                <span class="checkmark"></span>
                                <?= htmlspecialchars($marque) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="filter-group">
                    <div class="filter-title">Couleur</div>
                    <div class="color-grid">
                        <label class="color-item" style="background:#b33939;" title="Rouge"><input type="checkbox" name="couleur[]" value="Rouge"><span></span></label>
                        <label class="color-item" style="background:#e84393;" title="Rose"><input type="checkbox" name="couleur[]" value="Rose"><span></span></label>
                        <label class="color-item" style="background:#fdcb6e;" title="Or"><input type="checkbox" name="couleur[]" value="Or"><span></span></label>
                        <label class="color-item" style="background:#2d3436;" title="Noir"><input type="checkbox" name="couleur[]" value="Noir"><span></span></label>
                    </div>
                </div>

                <button type="submit" class="btn-filter">Appliquer</button>
            </form>
        </aside>

        <main class="products-content">
            <div class="toolbar">
                <div class="res-count"><?= count($produits) ?> produits</div>
                <form id="sortForm" method="GET" style="margin:0;">
                    <?php foreach ($_GET as $key => $value) { if ($key != 'sort' && !is_array($value)) { echo '<input type="hidden" name="'.htmlspecialchars($key).'" value="'.htmlspecialchars($value).'">'; } } ?>
                    <select name="sort" class="sort-select" onchange="this.form.submit()">
                        <option value="newest" <?= $sort == 'newest' ? 'selected' : '' ?>>Trier par : Nouveautés</option>
                        <option value="price_asc" <?= $sort == 'price_asc' ? 'selected' : '' ?>>Prix croissant</option>
                        <option value="price_desc" <?= $sort == 'price_desc' ? 'selected' : '' ?>>Prix décroissant</option>
                    </select>
                </form>
            </div>

            <?php if (count($produits) > 0): ?>
                <div class="prod-grid">
                    <?php foreach ($produits as $p): ?>
                        <div class="prod-card">
                            
                            <?php if ($p['prix_promo']): ?>
                                <span class="pc-badge">-<?= round((($p['prix'] - $p['prix_promo']) / $p['prix']) * 100); ?>%</span>
                            <?php endif; ?>

                            <div class="pc-img-wrap">
                                <a href="produit-detail.php?id=<?= $p['id'] ?>">
                                    <?php 
                                        $img = !empty($p['image']) ? 'uploads/produits/' . $p['image'] : 'images/default.jpg';
                                        if (!file_exists($img)) $img = 'images/default.jpg';
                                    ?>
                                    <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($p['nom']) ?>">
                                </a>
                            </div>

                            <div class="pc-info">
                                <span class="pc-cat">SOFCOS</span>
                                <a href="produit-detail.php?id=<?= $p['id'] ?>" class="pc-title"><?= htmlspecialchars($p['nom']) ?></a>
                                
                                <div class="pc-price-box">
                                    <?php if ($p['prix_promo']): ?>
                                        <span class="pc-price"><?= number_format($p['prix_promo'], 2) ?> MAD</span>
                                        <span class="pc-old-price"><?= number_format($p['prix'], 2) ?></span>
                                    <?php else: ?>
                                        <span class="pc-price"><?= number_format($p['prix'], 2) ?> MAD</span>
                                    <?php endif; ?>
                                </div>

                                <form action="panier.php" method="POST">
                                    <input type="hidden" name="action" value="ajouter">
                                    <input type="hidden" name="produit_id" value="<?= $p['id'] ?>">
                                    <input type="hidden" name="quantite" value="1">
                                    
                                    <?php if($p['stock'] > 0): ?>
                                        <button type="submit" class="btn-add-text">Ajouter au panier</button>
                                    <?php else: ?>
                                        <button type="button" class="btn-add-text" style="color:#ccc; border-color:#eee; cursor:default;" disabled>Rupture</button>
                                    <?php endif; ?>
                                </form>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 100px 0; color: #999;">
                    <i class="fas fa-search" style="font-size: 40px; margin-bottom: 20px; color: var(--shop-gold);"></i>
                    <p>Aucun produit ne correspond à vos critères.</p>
                    <a href="produits.php" style="color: var(--shop-green); text-decoration: underline;">Voir toute la collection</a>
                </div>
            <?php endif; ?>

        </main>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>