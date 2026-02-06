 <?php
// includes/header.php

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Connexion BDD
try {
    if(file_exists('config.php')) { require_once 'config.php'; } 
    elseif(file_exists('../config.php')) { require_once '../config.php'; } 
    else { $pdo = new PDO("mysql:host=localhost;dbname=sofcos_db;charset=utf8", "root", ""); }
    
    $categories = [];
    if(isset($pdo)) {
        $stmt = $pdo->query("SELECT * FROM categories ORDER BY id ASC");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch(Exception $e) { $categories = []; }

// Panier
$nb_articles_panier = 0;
if(isset($_SESSION['panier'])) {
    foreach($_SESSION['panier'] as $qty) { $nb_articles_panier += $qty; }
}

// Fonction active page
function isActive($page) {
    return basename($_SERVER['PHP_SELF']) == $page ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOFCOS</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --so-green: #3A6342;
            --so-dark: #2F4F38;
            --so-text: #222222;
            --so-gray: #e5e5e5;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Roboto', sans-serif; color: #333; }
        a { text-decoration: none; color: inherit; }
        ul { list-style: none; }
        .container { max-width: 1250px; margin: 0 auto; padding: 0 15px; }

        /* 1. TOP BAR */
        .top-bar {
            background-color: var(--so-dark);
            color: white;
            font-size: 13px;
            padding: 8px 0;
        }
        .tb-content { display: flex; justify-content: space-between; align-items: center; }
        .tb-right { display: flex; gap: 20px; }

        /* 2. MIDDLE HEADER */
        .middle-header {
            padding: 25px 0;
            background: white;
        }
        .mh-row { display: flex; align-items: center; justify-content: space-between; gap: 20px; }

        .logo { font-size: 34px; font-weight: 700; color: var(--so-green); display: flex; align-items: center; gap: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .logo i { font-size: 28px; transform: rotate(-15deg); }

        .search-box { flex-grow: 1; max-width: 550px; display: flex; }
        .search-inp {
            width: 100%; padding: 12px 20px;
            border: 1px solid #ddd; border-right: none;
            border-radius: 50px 0 0 50px; outline: none; font-size: 14px;
        }
        .search-btn {
            background: var(--so-green); color: white; border: none;
            padding: 0 25px; border-radius: 0 50px 50px 0; cursor: pointer; font-size: 16px;
        }
        .search-btn:hover { background: var(--so-dark); }

        .actions { display: flex; gap: 30px; align-items: center; }
        .act-item { display: flex; flex-direction: column; align-items: center; font-size: 12px; color: #333; }
        .act-item i { font-size: 24px; margin-bottom: 5px; }
        .badge {
            position: absolute; top: -5px; right: 0;
            background: #d63031; color: white;
            font-size: 10px; font-weight: bold; width: 18px; height: 18px;
            border-radius: 50%; display: flex; justify-content: center; align-items: center;
        }

        /* 3. NAVIGATION */
        .nav-bar {
            background: white;
            border-top: 1px solid var(--so-gray); 
        }
        .nav-list {
            display: flex; justify-content: center; gap: 35px;
            padding: 0; 
        }
        .nav-link {
            display: block;
            padding: 18px 0;
            font-size: 14px;
            
            /* --- MODIFICATION ICI : TEXTE EN GRAS --- */
            font-weight: 700; 
            
            text-transform: uppercase;
            color: var(--so-text);
            position: relative;
        }

        /* Barre verte au survol */
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0;
            width: 0%; height: 4px;
            background-color: var(--so-green);
            transition: width 0.3s ease;
        }
        .nav-link:hover::after, .nav-link.active::after { width: 100%; }
        .nav-link:hover { color: var(--so-green); }

        /* Responsive */
        @media (max-width: 992px) {
            .mh-row { flex-direction: column; gap: 15px; }
            .search-box { width: 100%; }
            .nav-list { flex-wrap: wrap; gap: 15px; font-size: 12px; padding: 10px 0; }
            .tb-content { flex-direction: column; gap: 5px; text-align: center; }
        }
    </style>
</head>
<body>

    <div class="top-bar">
        <div class="container tb-content">
            <div><i class="fas fa-truck"></i> Livraison offerte dès 500 MAD • Paiement à la livraison</div>
            <div class="tb-right">
                <span><i class="fas fa-phone-alt"></i> 06 00 00 00 00</span>
                <span>Service Client</span>
            </div>
        </div>
    </div>

    <header class="middle-header">
        <div class="container mh-row">
            <a href="index.php" class="logo"><i class="fas fa-leaf"></i> SOFCOS</a>

            <form action="produits.php" method="GET" class="search-box">
                <input type="text" name="q" class="search-inp" placeholder="Rechercher (ex: Huile d'argan)...">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
            </form>

            <div class="actions">
                <a href="mon-compte.php" class="act-item">
                    <i class="far fa-user"></i> Compte
                </a>
                <a href="panier.php" class="act-item" style="position:relative;">
                    <i class="fas fa-shopping-bag"></i>
                    <?php if($nb_articles_panier > 0): ?>
                        <span class="badge"><?= $nb_articles_panier ?></span>
                    <?php else: ?>
                        <span class="badge">0</span>
                    <?php endif; ?>
                    Panier
                </a>
        <a href="deconnexion.php" class="act-item" style="position:relative;">
            <i class="fas fa-sign-out-alt"></i>logout
        </a>
    
            </div>
        </div>
    </header>

    <nav class="nav-bar">
        <div class="container">
            <ul class="nav-list">
                <li><a href="index.php" class="nav-link <?= isActive('index.php') ?>">ACCUEIL</a></li>
                <li><a href="produits.php" class="nav-link <?= isActive('produits.php') ?>">BOUTIQUE</a></li>
                
                <?php if(!empty($categories)): ?>
                    <?php foreach($categories as $cat): ?>
                        <li>
                            <a href="produits.php?categorie=<?= $cat['id'] ?>" class="nav-link">
                                <?= htmlspecialchars(strtoupper($cat['nom'])) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li><a href="produits.php?categorie=1" class="nav-link">SOINS DU VISAGE</a></li>
                    <li><a href="produits.php?categorie=2" class="nav-link">MAQUILLAGE</a></li>
                    <li><a href="produits.php?categorie=3" class="nav-link">SOINS DU CORPS</a></li>
                    <li><a href="produits.php?categorie=4" class="nav-link">PARFUMS</a></li>
                    <li><a href="produits.php?categorie=5" class="nav-link">CHEVEUX</a></li>
                <?php endif; ?>

                <li><a href="contact.php" class="nav-link <?= isActive('contact.php') ?>">CONTACT</a></li>
            </ul>
        </div>
    </nav>
</body>
</html>