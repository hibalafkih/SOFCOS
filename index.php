<?php
// index.php
session_start();
require_once 'config.php';

// --- LOGIQUE PHP ---

// 1. Produits en promo (On en prend 4)
$stmt = $pdo->query("SELECT * FROM produits WHERE prix_promo IS NOT NULL AND actif = 1 LIMIT 4");
$produits_promo = $stmt->fetchAll();

// 2. Les Catégories (Pour afficher les univers ensuite)
$stmt = $pdo->query("SELECT * FROM categories ORDER BY id ASC");
$categories = $stmt->fetchAll();

// 3. Calcul du nombre d'articles panier (Nécessaire pour le badge du header)
$nb_articles_panier = 0;
if(isset($_SESSION['panier'])) {
    foreach($_SESSION['panier'] as $qty) {
        $nb_articles_panier += $qty;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOFCOS - Beauté Naturelle & Élégance</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500&family=Playfair+Display:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* --- 1. VARIABLES & RESET --- */
        :root {
            --primary: #446A46;       /* Vert Nature */
            --primary-dark: #2F4F32;  
            --accent: #C7B299;        /* Beige Doré */
            --promo-bg: #F9F7F2;      /* Fond clair promo */
            --text-dark: #1a1a1a;
            --white: #ffffff;
            --container-width: 1200px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: var(--white);
            color: var(--text-dark);
            line-height: 1.6;
        }

        a { text-decoration: none; color: inherit; transition: 0.3s; }
        ul { list-style: none; }

        .container {
            max-width: var(--container-width);
            margin: 0 auto;
            padding: 0 20px;
        }

        /* --- 2. NOUVELLE STRUCTURE NAVIGATION (3 NIVEAUX) --- */
        
        /* Niveau 1: Top Bar */
        .top-bar {
            background-color: var(--primary-dark);
            color: var(--white);
            font-size: 0.75rem;
            padding: 8px 0;
        }
        .top-bar-content {
            display: flex; justify-content: space-between; align-items: center;
        }
        .top-links a { margin-left: 15px; color: #ccc; }
        .top-links a:hover { color: white; }

        /* Niveau 2: Middle Header (Logo, Search, Icons) */
        .middle-header {
            padding: 20px 0;
            background: white;
            border-bottom: 1px solid #eee;
        }
        .header-row {
            display: flex; justify-content: space-between; align-items: center; gap: 30px;
        }
        .logo {
            font-size: 2rem; font-weight: 700; color: var(--primary); letter-spacing: -1px; white-space: nowrap;
        }
        /* Search Bar */
        .search-bar {
            flex-grow: 1; max-width: 600px; position: relative;
        }
        .search-form { display: flex; }
        .search-input {
            width: 100%; padding: 12px 15px; border: 2px solid #eee; border-radius: 50px 0 0 50px; outline: none; font-size: 0.9rem; transition: 0.3s;
        }
        .search-input:focus { border-color: var(--primary); }
        .search-btn {
            background: var(--primary); color: white; border: none; padding: 0 25px; border-radius: 0 50px 50px 0; cursor: pointer; font-size: 1rem; transition: 0.3s;
        }
        .search-btn:hover { background: var(--primary-dark); }
        
        /* Icons Actions */
        .header-actions { display: flex; align-items: center; gap: 25px; }
        .action-item { text-align: center; font-size: 0.8rem; color: #555; position: relative; }
        .action-item i { font-size: 1.4rem; display: block; margin-bottom: 3px; color: var(--text-dark); }
        .action-item:hover i { color: var(--primary); }
        .badge {
            position: absolute; top: -5px; right: 0; background: #e74c3c; color: white;
            font-size: 0.7rem; width: 18px; height: 18px; border-radius: 50%;
            display: flex; justify-content: center; align-items: center; font-weight: bold;
        }

        /* Niveau 3: Menu Sticky */
        .main-nav {
            background: white; border-bottom: 1px solid #eee; position: sticky; top: 0; z-index: 1000; box-shadow: 0 4px 10px rgba(0,0,0,0.03);
        }
        .nav-list { display: flex; justify-content: center; gap: 40px; }
        .nav-link {
            display: block; padding: 15px 0; font-size: 0.95rem; font-weight: 600; text-transform: uppercase; color: var(--text-dark); position: relative;
        }
        .nav-link::after {
            content: ''; position: absolute; bottom: 0; left: 0; width: 0%; height: 3px; background: var(--primary); transition: 0.3s;
        }
        .nav-link:hover { color: var(--primary); }
        .nav-link:hover::after { width: 100%; }
        .nav-link.special { color: #e74c3c; }

        /* --- 3. HERO --- */
        .hero {
            position: relative; height: 600px; width: 100%;
            background: url('beverly-kimberly--KAj3MBsH7U-unsplash.jpg') no-repeat center center;
            background-size: cover; display: flex; align-items: center;
        }
        .hero::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(90deg, rgba(0,0,0,0.5) 0%, rgba(0,0,0,0.1) 100%); }
        .hero-content { position: relative; z-index: 2; color: var(--white); max-width: 550px; padding-left: 20px; }
        .hero h1 { font-size: 3.8rem; line-height: 1.1; margin-bottom: 20px; font-weight: 700; }
        .btn-hero { display: inline-block; background: var(--white); color: var(--text-dark); padding: 15px 35px; border-radius: 0; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .btn-hero:hover { background: var(--primary); color: white; }

        /* --- 4. SERVICES --- */
        .services-section { padding: 50px 0; border-bottom: 1px solid #eee; }
        .services-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 30px; text-align: center; }
        .service-icon { font-size: 2rem; color: var(--primary); margin-bottom: 15px; }
        .service-title { font-size: 1rem; font-weight: 700; margin-bottom: 5px; text-transform: uppercase; }
        .service-desc { font-size: 0.85rem; color: #777; }

        /* --- 5. DESIGN "OFFRES SPÉCIALES" ÉLÉGANT --- */
        .promo-section {
            background-color: var(--promo-bg);
            padding: 80px 0;
            position: relative;
        }
        .section-header-center { text-align: center; margin-bottom: 50px; }
        .section-header-center h2 { font-size: 2.5rem; color: var(--primary-dark); font-weight: 400; letter-spacing: 2px; text-transform: uppercase; }
        .section-header-center span { display: block; width: 60px; height: 3px; background: var(--accent); margin: 15px auto 0; }

        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 30px; }
        
        .product-card {
            background: var(--white); 
            transition: transform 0.3s ease, box-shadow 0.3s ease; 
            border: 1px solid #eee;
            position: relative;
        }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.08); border-color: var(--accent); }

        .badge-promo {
            position: absolute; top: 15px; left: 15px; z-index: 10;
            background-color: #e74c3c; color: white;
            padding: 5px 12px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; letter-spacing: 1px;
        }

        .card-img { height: 280px; width: 100%; overflow: hidden; position: relative; background: #fdfdfd; }
        .card-img img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
        .product-card:hover .card-img img { transform: scale(1.05); }

        .card-body { padding: 25px 20px; text-align: center; }
        .card-cat { font-size: 0.7rem; color: #999; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
        .card-title { font-size: 1.05rem; color: var(--text-dark); margin-bottom: 12px; font-weight: 600; letter-spacing: 0.5px; }
        
        .card-price { margin-bottom: 15px; }
        .price-new { color: var(--primary); font-weight: 700; font-size: 1.1rem; margin-left: 8px; }
        .price-old { text-decoration: line-through; color: #aaa; font-size: 0.9rem; }

        .btn-link {
            display: inline-block; padding-bottom: 2px; border-bottom: 1px solid var(--text-dark);
            font-size: 0.85rem; font-weight: 600; text-transform: uppercase; color: var(--text-dark);
        }
        .btn-link:hover { color: var(--primary); border-color: var(--primary); }

        /* --- 6. SECTION PAR UNIVERS --- */
        .univers-wrapper { padding: 60px 0; border-top: 1px solid #eee; }
        .univers-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; }
        .univers-title { font-size: 1.8rem; color: var(--text-dark); font-weight: 700; }
        .univers-link { color: var(--primary); font-weight: 600; font-size: 0.9rem; text-transform: uppercase; }
        .univers-link:hover { text-decoration: underline; }

        /* --- 7. FOOTER --- */
        /* --- FOOTER LUXE & MODERNE --- */
.luxe-footer {
    background-color: #1e3d2f; /* Le même vert sombre que votre Promo/TopBar */
    color: #fff;
    padding-top: 70px;
    font-size: 0.9rem;
    position: relative;
    border-top: 4px solid #C7B299; /* Petite ligne dorée de rappel */
}

.footer-top-newsletter {
    text-align: center;
    padding-bottom: 50px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    margin-bottom: 50px;
}

.newsletter-title {
    font-family: 'Playfair Display', serif;
    font-size: 2rem;
    margin-bottom: 10px;
    color: #fff;
}

.newsletter-form {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 20px;
    flex-wrap: wrap;
}

.newsletter-input {
    padding: 12px 20px;
    border: 1px solid rgba(255,255,255,0.3);
    background: transparent;
    color: white;
    width: 300px;
    outline: none;
    transition: 0.3s;
}
.newsletter-input:focus { border-color: #C7B299; }

.newsletter-btn {
    background-color: #C7B299;
    color: #1a1a1a;
    border: none;
    padding: 12px 25px;
    font-weight: 700;
    text-transform: uppercase;
    cursor: pointer;
    transition: 0.3s;
}
.newsletter-btn:hover { background-color: #fff; }

/* Grille principale */
.footer-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 40px;
    padding-bottom: 50px;
}

.footer-col h3 {
    font-family: 'Playfair Display', serif;
    font-size: 1.2rem;
    color: #C7B299; /* Couleur Accent Doré */
    margin-bottom: 25px;
    letter-spacing: 1px;
}

.footer-col p {
    color: #ccc;
    line-height: 1.8;
}

.footer-links li {
    margin-bottom: 12px;
}

.footer-links a {
    color: #ccc;
    text-decoration: none;
    transition: 0.3s;
    display: flex;
    align-items: center;
}

.footer-links a:hover {
    color: #C7B299;
    transform: translateX(5px); /* Petit mouvement vers la droite */
}

/* Icônes Contact */
.contact-list li {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    margin-bottom: 15px;
    color: #ccc;
}
.contact-list i {
    color: #C7B299;
    margin-top: 5px;
}

/* Réseaux Sociaux */
.social-links {
    display: flex;
    gap: 15px;
    margin-top: 20px;
}
.social-btn {
    width: 40px; height: 40px;
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: white;
    transition: 0.3s;
}
.social-btn:hover {
    background-color: #C7B299;
    border-color: #C7B299;
    color: #1a1a1a;
}

/* Bas de page */
.footer-bottom {
    background-color: #162e23; /* Un vert encore plus foncé */
    padding: 20px 0;
    text-align: center;
    color: #777;
    font-size: 0.8rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
}

.payment-icons {
    font-size: 1.5rem;
    display: flex;
    gap: 15px;
}

@media (max-width: 768px) {
    .newsletter-input { width: 100%; }
    .footer-bottom { justify-content: center; gap: 15px; }
}
    /* --- NOUVEAU DESIGN OFFRES SPECIALES --- */
.promo-section {
    background: linear-gradient(to bottom, #F9F7F2, #ffffff); /* Dégradé subtil */
    padding: 80px 0;
}

/* Le Header avec le compte à rebours */
.promo-header {
    text-align: center; margin-bottom: 50px;
}
.promo-header h2 {
    font-size: 2.8rem; color: var(--primary-dark); font-weight: 300; text-transform: uppercase; letter-spacing: 3px; margin-bottom: 10px;
}
.promo-subtitle {
    color: #e74c3c; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; font-size: 0.9rem; display: flex; align-items: center; justify-content: center; gap: 10px;
}

/* Timer Box */
.timer-box {
    display: inline-flex; gap: 10px; margin-top: 15px;
}
.time-unit {
    background: var(--primary); color: white; padding: 5px 10px; border-radius: 4px; font-weight: bold; min-width: 40px;
}

/* Carte Promo Premium */
.promo-card {
    background: white;
    border: 1px solid #f0f0f0;
    transition: 0.4s ease;
    position: relative;
    overflow: hidden;
}
.promo-card:hover {
    box-shadow: 0 15px 30px rgba(0,0,0,0.1);
    transform: translateY(-5px);
    border-color: var(--accent);
}

/* Badge Pourcentage Rond */
.badge-percent {
    position: absolute; top: 15px; left: 15px; z-index: 10;
    background-color: #d63031; color: white;
    width: 50px; height: 50px; border-radius: 50%;
    display: flex; justify-content: center; align-items: center;
    font-weight: 700; font-size: 0.9rem;
    box-shadow: 0 4px 10px rgba(214, 48, 49, 0.3);
}

.promo-img-container {
    height: 300px; overflow: hidden; position: relative;
}
.promo-img-container img {
    width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s ease;
}
.promo-card:hover .promo-img-container img {
    transform: scale(1.08);
}

.promo-body { padding: 25px; text-align: center; }
.promo-title {
    font-size: 1.1rem; font-weight: 600; margin-bottom: 10px; color: var(--text-dark);
}

.promo-prices {
    margin-bottom: 20px; font-family: 'Helvetica', sans-serif;
}
.old-price { color: #aaa; text-decoration: line-through; font-size: 0.95rem; margin-right: 10px; }
.new-price { color: #d63031; font-weight: 700; font-size: 1.3rem; }

/* Bouton Large */
.btn-add-cart {
    display: block; width: 100%;
    background-color: var(--text-dark); color: white;
    padding: 12px 0; text-transform: uppercase; font-weight: 600; font-size: 0.8rem; letter-spacing: 1px;
    transition: 0.3s; border: none; cursor: pointer;
}
.btn-add-cart:hover {
    background-color: var(--primary);
}
/* --- NOUVEAU DESIGN "LUXE DARK" --- */
.promo-section-dark {
    background-color: #1e3d2f; /* Vert très sombre luxueux */
    color: white;
    padding: 60px 0;
    overflow: hidden; /* Pour gérer les éléments qui dépassent */
    position: relative;
}

/* Texture de fond subtile (optionnel) */
.promo-section-dark::before {
    content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
    background: radial-gradient(circle at 20% 50%, rgba(255,255,255,0.05) 0%, transparent 50%);
    pointer-events: none;
}

.promo-container {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 40px;
    max-width: 1200px; margin: 0 auto; padding: 0 20px;
}

/* COLONNE GAUCHE : INFO & TIMER */
.promo-info-col {
    flex: 1;
    min-width: 300px;
    z-index: 2;
}

.promo-tag {
    display: inline-block; background: #C7B299; color: #1a1a1a;
    padding: 5px 15px; font-weight: bold; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px;
    margin-bottom: 15px;
}

.promo-info-col h2 {
    font-size: 3rem; margin-bottom: 15px; line-height: 1.1; font-family: 'Times New Roman', serif;
}
.promo-info-col p {
    color: #ccc; margin-bottom: 30px; font-size: 1rem;
}

/* Design du Timer Luxe */
.timer-lux {
    display: flex; gap: 15px;
}
.timer-box {
    text-align: center;
}
.timer-num {
    background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);
    font-size: 1.8rem; font-weight: 700; width: 70px; height: 70px;
    display: flex; align-items: center; justify-content: center;
    border-radius: 5px; margin-bottom: 5px; backdrop-filter: blur(5px);
}
.timer-label {
    font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; color: #888;
}

/* COLONNE DROITE : SCROLL PRODUITS */
.promo-products-col {
    flex: 2;
    min-width: 0; /* Important pour le flexbox scroll */
}

/* Scroll horizontal masqué mais fonctionnel */
.horizontal-scroll {
    display: flex;
    gap: 20px;
    overflow-x: auto;
    padding-bottom: 20px;
    padding-left: 5px; /* Espace pour l'ombre */
    scroll-behavior: smooth;
}
/* Scrollbar custom fine */
.horizontal-scroll::-webkit-scrollbar { height: 6px; }
.horizontal-scroll::-webkit-scrollbar-track { background: rgba(255,255,255,0.1); border-radius: 10px; }
.horizontal-scroll::-webkit-scrollbar-thumb { background: #C7B299; border-radius: 10px; }

/* Carte Produit Dark Mode */
.card-dark {
    background: white;
    min-width: 260px; /* Largeur fixe */
    max-width: 260px;
    border-radius: 8px;
    overflow: hidden;
    position: relative;
    transition: transform 0.3s ease;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}
.card-dark:hover {
    transform: translateY(-10px);
}

.card-dark-badge {
    position: absolute; top: 10px; left: 10px;
    background: #d63031; color: white; padding: 4px 10px; font-weight: bold; font-size: 0.8rem; border-radius: 4px; z-index: 5;
}

.card-dark-img {
    height: 220px; width: 100%; position: relative; overflow: hidden;
}
.card-dark-img img {
    width: 100%; height: 100%; object-fit: cover;
}

.card-dark-body {
    padding: 15px; color: #333; text-align: left;
}
.card-dark h3 {
    font-size: 1rem; margin-bottom: 5px; font-weight: 600;
}
.card-dark-price {
    display: flex; justify-content: space-between; align-items: center; margin-top: 10px;
}
.cd-old { text-decoration: line-through; color: #999; font-size: 0.9rem; }
.cd-new { color: #d63031; font-weight: bold; font-size: 1.1rem; }

.btn-dark-add {
    width: 100%; border: none; background: #1a1a1a; color: white; padding: 10px;
    margin-top: 15px; cursor: pointer; text-transform: uppercase; font-size: 0.75rem; font-weight: bold; transition: 0.3s;
}
.btn-dark-add:hover { background: var(--primary); }

/* Responsive Mobile */
@media (max-width: 768px) {
    .promo-container { flex-direction: column; align-items: flex-start; }
    .promo-info-col h2 { font-size: 2.2rem; }
    .promo-products-col { width: 100%; }
}
/* --- DESIGN SPÉCIAL : UNIVERS VISAGE (GRAND FORMAT) --- */
.univers-full-width {
    display: flex;
    flex-wrap: wrap;
    width: 100%;       /* Prend toute la largeur de l'écran */
    min-height: 650px; /* Force une grande hauteur */
    margin: 80px 0;
    background-color: #F8F5F2;
}

/* Colonne Image (Gauche) - Prend 45% de la largeur */
.special-banner-large {
    flex: 0 0 45%; 
    position: relative;
    overflow: hidden;
    min-height: 400px; /* Sécurité mobile */
}

.special-banner-large img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 1.5s ease; /* Animation plus lente et douce */
}

.univers-full-width:hover .special-banner-large img {
    transform: scale(1.08);
}

.special-overlay-large {
    position: absolute;
    bottom: 0; left: 0; width: 100%;
    padding: 60px; /* Plus d'espace */
    background: linear-gradient(to top, rgba(26, 26, 26, 0.6), transparent);
    color: white;
}

.special-title-large {
    font-size: 3.5rem; /* Titre beaucoup plus gros */
    font-family: 'Times New Roman', serif;
    margin-bottom: 15px;
    line-height: 1;
}

.btn-discover-large {
    display: inline-block;
    background: white;
    color: var(--text-dark);
    padding: 15px 40px; /* Bouton plus gros */
    text-transform: uppercase;
    font-size: 0.9rem;
    font-weight: bold;
    letter-spacing: 2px;
    margin-top: 20px;
}
.btn-discover-large:hover {
    background: var(--primary);
    color: white;
}

/* Colonne Produits (Droite) - Prend 55% de la largeur */
.special-products-container {
    flex: 1;
    padding: 60px 80px; /* Grandes marges internes */
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.special-grid-large {
    display: grid;
    grid-template-columns: repeat(2, 1fr); /* Force 2 colonnes larges */
    gap: 40px; /* Grand espacement entre les produits */
}

/* Responsive */
@media (max-width: 1024px) {
    .univers-full-width { flex-direction: column; height: auto; }
    .special-banner-large { width: 100%; height: 400px; flex: none; }
    .special-products-container { padding: 40px 20px; }
    .special-grid-large { grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); }
}
/* --- VARIANTE MAQUILLAGE (Inversée) --- */
.univers-full-width.reverse {
    flex-direction: row-reverse; /* Met l'image à droite */
    background-color: #ffffff;   /* Fond blanc pur pour faire ressortir les couleurs */
}

.univers-full-width.reverse .special-overlay-large {
    background: linear-gradient(to top, rgba(169, 74, 74, 0.7), transparent); /* Dégradé plus chaud/rouge pour le makeup */
}

/* On ajuste les bordures pour le mode inversé si nécessaire */
@media (max-width: 1024px) {
    .univers-full-width.reverse { flex-direction: column; }
}
/* --- DESIGN LUXE & PROFESSIONNEL --- */

/* Conteneur Principal - Style Magazine */
.univers-pro-section {
    display: flex;
    width: 100%;
    min-height: 700px; /* Très haut pour l'impact visuel */
    margin: 0;         /* Colle aux bords */
    position: relative;
}

/* Colonne Média (Image) */
.pro-media-col {
    flex: 1;
    position: relative;
    overflow: hidden;
}

.pro-media-col img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 2s cubic-bezier(0.25, 0.46, 0.45, 0.94); /* Zoom très lent et fluide */
}

/* Effet de survol sur l'image */
.univers-pro-section:hover .pro-media-col img {
    transform: scale(1.06);
}

/* Overlay sombre élégant pour lisibilité texte */
.pro-overlay {
    position: absolute;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.25); /* Voile noir léger */
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    padding: 40px;
    z-index: 2;
}

/* Typographie Luxe */
.pro-title-main {
    font-family: 'Playfair Display', serif;
    font-size: 4rem;
    color: #fff;
    margin-bottom: 20px;
    font-weight: 400;
    font-style: italic;
    letter-spacing: 1px;
    text-shadow: 0 2px 10px rgba(0,0,0,0.3);
}

.pro-desc {
    font-family: 'Montserrat', sans-serif;
    color: #f0f0f0;
    font-size: 1rem;
    max-width: 400px;
    line-height: 1.8;
    margin-bottom: 40px;
    font-weight: 300;
}

/* Bouton Transparent "Ghost" */
.btn-pro-outline {
    border: 1px solid rgba(255,255,255,0.8);
    background: transparent;
    color: #fff;
    padding: 15px 40px;
    font-family: 'Montserrat', sans-serif;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 3px;
    transition: 0.4s ease;
}
.btn-pro-outline:hover {
    background: #fff;
    color: #000;
    border-color: #fff;
}

/* Colonne Produits (Clean) */
.pro-products-col {
    flex: 1;
    background: #fff;
    padding: 60px 80px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.pro-products-header {
    margin-bottom: 50px;
}
.pro-sub-title {
    font-family: 'Montserrat', sans-serif;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: #888;
    margin-bottom: 10px;
    display: block;
}
.pro-headline {
    font-family: 'Playfair Display', serif;
    font-size: 2.5rem;
    color: #1a1a1a;
}

/* Grille Minimaliste */
.pro-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 40px;
}

/* Carte Produit Minimaliste */
.card-minimal {
    text-align: center;
    transition: opacity 0.3s;
}
.card-minimal:hover { opacity: 0.8; }

.card-minimal-img {
    height: 220px;
    margin-bottom: 20px;
    position: relative;
}
.card-minimal-img img {
    width: 100%; height: 100%; object-fit: contain; /* Contain pour ne pas couper les flacons */
}

.card-minimal-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.1rem;
    color: #000;
    margin-bottom: 5px;
}
.card-minimal-price {
    font-family: 'Montserrat', sans-serif;
    font-size: 0.9rem;
    color: #555;
    font-weight: 500;
}

/* VARIANTE INVERSÉE (MAQUILLAGE) */
.univers-pro-section.reverse {
    flex-direction: row-reverse;
}
.univers-pro-section.reverse .pro-products-col {
    background: #fdfbfb; /* Blanc très légèrement cassé */
}

/* Responsive */
@media (max-width: 992px) {
    .univers-pro-section, .univers-pro-section.reverse { flex-direction: column; }
    .univers-pro-section { min-height: auto; }
    .pro-media-col { height: 400px; }
    .pro-products-col { padding: 40px 20px; }
    .pro-title-main { font-size: 3rem; }
}
/* --- Modification : Section Visage plus large --- */

/* 1. On réduit la largeur de la bannière image (de 45% à 30%) */
.special-banner-large {
    flex: 0 0 30%; /* L'image devient plus étroite */
    min-height: 400px;
}

/* 2. La partie produits prendra automatiquement tout l'espace restant (70%) */
.special-products-container {
    flex: 1; 
    padding: 60px 40px; /* J'ai réduit un peu les marges latérales pour gagner de la place */
}

/* 3. (Optionnel) Passer à 3 produits par ligne au lieu de 2 */
.special-grid-large {
    display: grid;
    /* Si l'écran est assez grand, on met 3 colonnes, sinon on laisse le navigateur gérer */
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
    gap: 30px;
}
/* --- MODIFICATION SECTION VISAGE --- */

/* 1. On réduit la largeur de la colonne Image (passe de 45% à 30%) */
.special-banner-large {
    flex: 0 0 30%; /* <-- Changement ici : 30% au lieu de 45% */
    position: relative;
    overflow: hidden;
    min-height: 400px;
}

/* 2. La colonne produits prend automatiquement tout le reste (70%) */
.special-products-container {
    flex: 1; 
    padding: 60px 60px; /* J'ai légèrement réduit les marges latérales pour profiter de l'espace */
    display: flex;
    flex-direction: column;
    justify-content: center;
}

/* 3. (Optionnel) Comme c'est plus large, on autorise 3 produits par ligne si l'écran le permet */
.special-grid-large {
    display: grid;
    /* ANCIEN : grid-template-columns: repeat(2, 1fr); */
    /* NOUVEAU : Grille adaptative (met 3 produits si assez de place) */
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
    gap: 40px; 
}
/* --- MODIFICATION SECTION LUXE/MAQUILLAGE --- */

/* Colonne Média (Image) */
.pro-media-col {
    /* ANCIEN : flex: 1; (C'était 50%) */
    /* NOUVEAU : On fixe une largeur plus petite (35%) */
    flex: 0 0 35%; 
    position: relative;
    overflow: hidden;
}
/* --- NOUVEAU STYLE : CADRES ÉLÉGANTS & BOUTONS --- */

/* 1. Le Cadre de l'Univers (Section Flottante) */
.univers-pro-section {
    max-width: 1250px; /* Largeur contenue, plus chic */
    margin: 80px auto; /* Espacement vertical important */
    background: #fff;
    box-shadow: 0 15px 40px rgba(0,0,0,0.05); /* Ombre très douce */
    border: 1px solid #f0f0f0; /* Bordure subtile */
    border-radius: 6px; /* Coins légèrement adoucis */
    overflow: hidden; /* Pour que l'image ne dépasse pas */
    display: flex;
    min-height: 600px;
}

/* Sur mobile, on garde le format colonne */
@media (max-width: 992px) {
    .univers-pro-section {
        flex-direction: column;
        margin: 40px 15px; /* Marges sur les côtés mobile */
        max-width: 100%;
        min-height: auto;
    }
}

/* 2. Zone des Boutons Produits */
.card-actions-group {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #f9f9f9; /* Petite ligne de séparation */
}

/* Bouton Voir (Oeil) - Style Outline */
.btn-action-view {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border: 1px solid #ddd;
    border-radius: 50%; /* Rond */
    color: #555;
    transition: all 0.3s ease;
    background: white;
}
.btn-action-view:hover {
    border-color: var(--primary);
    color: var(--primary);
    transform: translateY(-2px);
}

/* Bouton Ajouter (Panier) - Style Plein */
.btn-action-add {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 20px;
    height: 40px;
    background-color: #1a1a1a;
    color: white;
    font-size: 0.75rem;
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 1px;
    border-radius: 20px; /* Ovale */
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none; /* Force suppression soulignement */
}
.btn-action-add:hover {
    background-color: var(--primary); /* Devient vert au survol */
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    color: white;
}

/* Ajustement spécifique pour les couleurs Maquillage */
.univers-pro-section.reverse .btn-action-add {
    background-color: #a6635e; /* Couleur brique pour maquillage */
}
.univers-pro-section.reverse .btn-action-add:hover {
    background-color: #8a4f4b;
}

/* La colonne .pro-products-col a déjà "flex: 1", 
   donc elle prendra automatiquement les 65% restants. Pas besoin de la modifier. */
</style>
</head>
<body>

    <nav class="top-bar">
        <div class="container top-bar-content">
            <div>
                <i class="fas fa-truck"></i> Livraison offerte dès 500 MAD &bull; Paiement à la livraison
            </div>
            <div class="top-links">
                <a href="#"><i class="fas fa-phone-alt"></i> 06 00 00 00 00</a>
                <a href="contact.php">Service Client</a>
            </div>
        </div>
    </nav>

    <header class="middle-header">
        <div class="container header-row">
            
            <a href="index.php" class="logo">
                <i class="fas fa-leaf"></i> SOFCOS
            </a>

            <div class="search-bar">
                <form action="produits.php" method="GET" class="search-form">
                    <input type="text" name="q" class="search-input" placeholder="Rechercher (ex: Huile d'argan)...">
                    <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
                </form>
            </div>

            <div class="header-actions">
                <a href="mon-compte.php" class="action-item">
                    <i class="far fa-user"></i>
                    <span>Compte</span>
                </a>
                <a href="panier.php" class="action-item">
                    <i class="fas fa-shopping-bag"></i>
                    <span>Panier</span>
                    <span class="badge"><?= $nb_articles_panier ?></span>
                </a>
                <a href="deconnexion.php" class="action-item">
            <i class="fas fa-sign-out-alt"></i>logout
        </a>
            </div>

        </div>
    </header>

    <nav class="main-nav">
        <div class="container">
            <ul class="nav-list">
                <li><a href="index.php" class="nav-link">Accueil</a></li>
                <li><a href="produits.php" class="nav-link">Boutique</a></li>
                
                <?php foreach($categories as $cat): ?>
                    <li>
                        <a href="produits.php?cat=<?= $cat['id'] ?>" class="nav-link">
                            <?= htmlspecialchars($cat['nom']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>

                <li><a href="contact.php" class="nav-link">Contact</a></li>
            </ul>
        </div>
    </nav>

    <div class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Révélez votre <br>Beauté Naturelle</h1>
                <p>Soins d'exception aux ingrédients purs.</p>
                <a href="produits.php" class="btn-hero">Voir la collection</a>
            </div>
        </div>
    </div>

    <div class="services-section">
        <div class="container">
            <div class="services-grid">
                <div>
                    <div class="service-icon"><i class="fas fa-truck"></i></div>
                    <div class="service-title">Livraison Offerte</div>
                    <div class="service-desc">Dès 500 MAD d'achat</div>
                </div>
                <div>
                    <div class="service-icon"><i class="fas fa-shipping-fast"></i></div>
                    <div class="service-title">Expédition Rapide</div>
                    <div class="service-desc">Sous 24/48h partout au Maroc</div>
                </div>
                <div>
                    <div class="service-icon"><i class="fas fa-hand-holding-heart"></i></div>
                    <div class="service-title">100% Authentique</div>
                    <div class="service-desc">Produits certifiés bio</div>
                </div>
                <div>
                    <div class="service-icon"><i class="fas fa-headset"></i></div>
                    <div class="service-title">Service Client</div>
                    <div class="service-desc">Disponible 7j/7</div>
                </div>
            </div>
        </div>
    </div>

    <?php if(count($produits_promo) > 0): ?>
    <section class="promo-section-dark">
        <div class="promo-container">
            
            <div class="promo-info-col">
                <span class="promo-tag">Offre Limitée</span>
                <h2>Ventes <br>Privées</h2>
                <p>Profitez de réductions exceptionnelles sur une sélection de nos meilleurs soins. L'élégance à prix doux, pour un temps limité.</p>
                
                <div class="timer-lux" id="countdown">
                    <div class="timer-box">
                        <div class="timer-num" id="hours">04</div>
                        <div class="timer-label">Heures</div>
                    </div>
                    <div class="timer-box">
                        <div class="timer-num" id="minutes">00</div>
                        <div class="timer-label">Min</div>
                    </div>
                    <div class="timer-box">
                        <div class="timer-num" id="seconds">00</div>
                        <div class="timer-label">Sec</div>
                    </div>
                </div>
            </div>

            <div class="promo-products-col">
                <div class="horizontal-scroll">
                    <?php foreach($produits_promo as $p): 
                        $reduction = 0;
                        if($p['prix'] > 0 && $p['prix_promo'] > 0) {
                            $reduction = round((($p['prix'] - $p['prix_promo']) / $p['prix']) * 100);
                        }
                    ?>
                    <div class="card-dark">
                        <?php if($reduction > 0): ?>
                            <div class="card-dark-badge">-<?= $reduction ?>%</div>
                        <?php endif; ?>
                        
                        <div class="card-dark-img">
                            <a href="produit_detail.php?id=<?= $p['id'] ?>">
                                <?php $img = !empty($p['image']) ? 'uploads/produits/'.$p['image'] : 'https://via.placeholder.com/400x500?text=SOFCOS'; ?>
                                <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($p['nom']) ?>">
                            </a>
                        </div>
                        
                        <div class="card-dark-body">
                            <h3><?= htmlspecialchars($p['nom']) ?></h3>
                            <div class="card-dark-price">
                                <span class="cd-old"><?= number_format($p['prix'], 2) ?></span>
                                <span class="cd-new"><?= number_format($p['prix_promo'], 2) ?> MAD</span>
                            </div>
                            <a href="panier-action.php?action=ajout&id=<?= $p['id'] ?>">
                                <button class="btn-dark-add">Ajouter</button>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </section>

    <script>
        function startTimer(duration, displayHours, displayMinutes, displaySeconds) {
            var timer = duration, hours, minutes, seconds;
            setInterval(function () {
                hours = parseInt(timer / 3600, 10);
                minutes = parseInt((timer % 3600) / 60, 10);
                seconds = parseInt(timer % 60, 10);

                hours = hours < 10 ? "0" + hours : hours;
                minutes = minutes < 10 ? "0" + minutes : minutes;
                seconds = seconds < 10 ? "0" + seconds : seconds;

                displayHours.textContent = hours;
                displayMinutes.textContent = minutes;
                displaySeconds.textContent = seconds;

                if (--timer < 0) {
                    timer = duration; 
                }
            }, 1000);
        }

        window.onload = function () {
            var timeInSeconds = 14400; // 4 heures
            var displayHours = document.querySelector('#hours');
            var displayMinutes = document.querySelector('#minutes');
            var displaySeconds = document.querySelector('#seconds');
            startTimer(timeInSeconds, displayHours, displayMinutes, displaySeconds);
        };
    </script>
    <?php endif; ?>
            </div>
        </div>
    </section>

   <?php foreach($categories as $cat): 
    
    // 1. Récupération des produits
    $stmt_p = $pdo->prepare("SELECT * FROM produits WHERE categorie_id = ? AND actif = 1 LIMIT 4");
    $stmt_p->execute([$cat['id']]);
    $produits_cat = $stmt_p->fetchAll();

    if(count($produits_cat) == 0) continue;

    // 2. Détection Univers
    $is_visage = (stripos($cat['nom'], 'Visage') !== false);
    $is_corps = (stripos($cat['nom'], 'Corps') !== false);
    $is_cheveux = (stripos($cat['nom'], 'Cheveux') !== false);
    
    $is_maquillage = (stripos($cat['nom'], 'Maquillage') !== false);
    $is_parfum = (stripos($cat['nom'], 'Parfum') !== false);
?>

    <?php if ($is_visage || $is_corps || $is_cheveux): ?>
        
        <?php 
            if ($is_visage) {
                $banner_img = 'https://images.unsplash.com/photo-1616394584738-fc6e612e71b9?q=80&w=2070';
                $main_title = 'Pureté & Éclat';
                $desc_text = 'Une routine de soins inspirée par la nature.';
            } elseif ($is_corps) {
                $banner_img = 'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?q=80&w=2070';
                $main_title = 'Rituel Corps';
                $desc_text = 'Douceur et hydratation intense pour votre peau.';
            } elseif ($is_cheveux) {
                $banner_img = 'https://images.unsplash.com/photo-1522337660859-02fbefca4702?q=80&w=2070';
                $main_title = 'Expertise Capillaire';
                $desc_text = 'Force et brillance naturelle.';
            }
        ?>

        <section class="univers-pro-section">
            <div class="pro-media-col">
                <img src="<?= $banner_img ?>" alt="<?= htmlspecialchars($cat['nom']) ?>">
                <div class="pro-overlay">
                    <h2 class="pro-title-main"><?= $main_title ?></h2>
                    <p class="pro-desc"><?= $desc_text ?></p>
                    <a href="produits.php?cat=<?= $cat['id'] ?>" class="btn-pro-outline">Tout voir</a>
                </div>
            </div>

            <div class="pro-products-col">
                <div class="pro-products-header">
                    <span class="pro-sub-title">Collection <?= htmlspecialchars($cat['nom']) ?></span>
                    <h3 class="pro-headline">Les Incontournables</h3>
                </div>
                
                <div class="pro-grid">
                    <?php foreach($produits_cat as $p): ?>
                        <div class="card-minimal">
                            <div class="card-minimal-img">
                                <a href="produit-detail.php?id=<?= $p['id'] ?>">
                                    <?php $img = !empty($p['image']) ? 'uploads/produits/'.$p['image'] : 'https://via.placeholder.com/400x500?text=Produit'; ?>
                                    <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($p['nom']) ?>">
                                </a>
                            </div>
                            <h4 class="card-minimal-title"><?= htmlspecialchars($p['nom']) ?></h4>
                            <div class="card-minimal-price"><?= number_format($p['prix'], 2) ?> MAD</div>
                            
                            <div class="card-actions-group">
                                <a href="produit-detail.php?id=<?= $p['id'] ?>" class="btn-action-view" title="Voir le produit">
                                    <i class="far fa-eye"></i>
                                </a>
                                <a href="panier-action.php?action=ajout&id=<?= $p['id'] ?>" class="btn-action-add">
                                    <i class="fas fa-shopping-bag" style="margin-right:8px;"></i> Ajouter
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

    <?php elseif ($is_maquillage || $is_parfum): ?>
        
        <?php 
            if ($is_maquillage) {
                $banner_img = 'https://images.unsplash.com/photo-1512496015851-a90fb38ba796?q=80&w=2070';
                $main_title = 'L\'Art de la Couleur';
                $desc_text = 'Textures soyeuses et pigments intenses.';
                $accent_color = '#a6635e';
            } elseif ($is_parfum) {
                $banner_img = 'https://images.unsplash.com/photo-1595425970377-c9703cf48b6d?q=80&w=2070';
                $main_title = 'Haute Parfumerie';
                $desc_text = 'Des essences rares et inoubliables.';
                $accent_color = '#333';
            }
        ?>

        <section class="univers-pro-section reverse">
            <div class="pro-media-col">
                <img src="<?= $banner_img ?>" alt="<?= htmlspecialchars($cat['nom']) ?>">
                <div class="pro-overlay">
                    <h2 class="pro-title-main"><?= $main_title ?></h2>
                    <p class="pro-desc"><?= $desc_text ?></p>
                    <a href="produits.php?cat=<?= $cat['id'] ?>" class="btn-pro-outline">Explorer</a>
                </div>
            </div>

            <div class="pro-products-col" style="background-color: #fdfbfb;">
                <div class="pro-products-header">
                    <span class="pro-sub-title">Collection <?= htmlspecialchars($cat['nom']) ?></span>
                    <h3 class="pro-headline">Sélection Exclusive</h3>
                </div>
                
                <div class="pro-grid">
                    <?php foreach($produits_cat as $p): ?>
                        <div class="card-minimal">
                            <div class="card-minimal-img">
                                <a href="produit-detail.php?id=<?= $p['id'] ?>">
                                    <?php $img = !empty($p['image']) ? 'uploads/produits/'.$p['image'] : 'https://via.placeholder.com/400x500?text=Produit'; ?>
                                    <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($p['nom']) ?>">
                                </a>
                            </div>
                            <h4 class="card-minimal-title"><?= htmlspecialchars($p['nom']) ?></h4>
                            <div class="card-minimal-price" style="color:<?= $accent_color ?>; font-weight:600;"><?= number_format($p['prix'], 2) ?> MAD</div>
                            
                            <div class="card-actions-group">
                                <a href="produit-detail.php?id=<?= $p['id'] ?>" class="btn-action-view" title="Voir le produit">
                                    <i class="far fa-eye"></i>
                                </a>
                                <a href="panier-action.php?action=ajout&id=<?= $p['id'] ?>" class="btn-action-add">
                                    <i class="fas fa-shopping-bag" style="margin-right:8px;"></i> Ajouter
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

    <?php else: ?>
        <section class="univers-wrapper">
            <div class="container">
                <div class="univers-header">
                    <h2 class="univers-title">Univers <?= htmlspecialchars($cat['nom']) ?></h2>
                    <a href="produits.php?cat=<?= $cat['id'] ?>" class="univers-link">Voir tout <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="product-grid">
                    <?php foreach($produits_cat as $p): ?>
                        <div class="product-card">
                            <div class="card-img">
                                <a href="produit-detail.php?id=<?= $p['id'] ?>">
                                    <?php $img = !empty($p['image']) ? 'uploads/produits/'.$p['image'] : 'https://via.placeholder.com/400x500?text=SOFCOS'; ?>
                                    <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($p['nom']) ?>">
                                </a>
                            </div>
                            <div class="card-body">
                                <h3 class="card-title"><?= htmlspecialchars($p['nom']) ?></h3>
                                <div class="card-price">
                                    <span class="price-new" style="margin-left:0;"><?= number_format($p['prix'], 2) ?> MAD</span>
                                </div>
                                <div class="card-actions-group">
                                    <a href="produit-detail.php?id=<?= $p['id'] ?>" class="btn-action-view"><i class="far fa-eye"></i></a>
                                    <a href="panier-action.php?action=ajout&id=<?= $p['id'] ?>" class="btn-action-add"><i class="fas fa-shopping-bag"></i> Ajouter</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

<?php endforeach; ?>


    <footer class="luxe-footer">
    <div class="container">
        
        <div class="footer-top-newsletter">
            <h2 class="newsletter-title">Rejoignez l'Univers SOFCOS</h2>
            <p style="color: #ccc;">Recevez nos conseils beauté et offres exclusives.</p>
            <form class="newsletter-form" action="#" method="POST">
                <input type="email" placeholder="Votre adresse email" class="newsletter-input" required>
                <button type="submit" class="newsletter-btn">S'inscrire</button>
            </form>
        </div>

        <div class="footer-grid">
            
            <div class="footer-col">
                <div class="logo" style="color:white; margin-bottom:20px; font-size:1.8rem;">
                    <i class="fas fa-leaf" style="color:#C7B299;"></i> SOFCOS
                </div>
                <p>L'alliance parfaite entre nature et science. Des soins d'exception formulés au Maroc pour révéler votre éclat naturel.</p>
                <div class="social-links">
                    <a href="#" class="social-btn"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-btn"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-btn"><i class="fab fa-tiktok"></i></a>
                </div>
            </div>

            <div class="footer-col">
                <h3>Boutique</h3>
                <ul class="footer-links">
                    <li><a href="produits.php">Tous les produits</a></li>
                    <li><a href="produits.php?cat=1">Soins Visage</a></li>
                    <li><a href="produits.php?cat=3">Soins Corps</a></li>
                    <li><a href="produits.php?cat=2">Maquillage</a></li>
                    <li><a href="#">Coffrets Cadeaux</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h3>Informations</h3>
                <ul class="footer-links">
                    <li><a href="#">Notre Histoire</a></li>
                    <li><a href="contact.php">Nous Contacter</a></li>
                    <li><a href="#">Livraisons & Retours</a></li>
                    <li><a href="#">Mentions Légales</a></li>
                    <li><a href="#">CGV</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h3>Service Client</h3>
                <ul class="contact-list">
                    <li>
                        <i class="fas fa-map-marker-alt"></i>
                        <span>123 Bd Anfa,<br>Casablanca, Maroc</span>
                    </li>
                    <li>
                        <i class="fas fa-phone-alt"></i>
                        <span>+212 5 22 00 00 00</span>
                    </li>
                    <li>
                        <i class="fas fa-envelope"></i>
                        <span>contact@sofcos.ma</span>
                    </li>
                    <li>
                        <i class="fas fa-clock"></i>
                        <span>Lun - Sam : 9h - 19h</span>
                    </li>
                </ul>
            </div>

        </div>
    </div>

    <div class="footer-bottom">
        <div class="container" style="display:flex; justify-content:space-between; width:100%; flex-wrap:wrap;">
            <div class="copyright">
                &copy; <?= date('Y') ?> SOFCOS Maroc. Tous droits réservés.
            </div>
            <div class="payment-icons">
                <i class="fab fa-cc-visa" style="color:white;"></i>
                <i class="fab fa-cc-mastercard" style="color:white;"></i>
                <i class="fas fa-money-bill-wave" style="color:white;" title="Paiement à la livraison"></i>
            </div>
        </div>
    </div>
</footer>

</body>
</html>