<?php
// includes/footer.php
?>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400&display=swap" rel="stylesheet">

<style>
    /* ================= VARIABLES ================= */
    :root {
        --footer-bg: #1A3C34;       /* Le vert profond de l'image */
        --footer-darker: #122b25;   /* Le vert très foncé du bas */
        --footer-gold: #d4c4a8;     /* Le beige/doré du bouton */
        --footer-text: #ffffff;
        --footer-text-muted: #cfd8d4; /* Gris clair pour le texte */
    }

    /* ================= STRUCTURE GÉNÉRALE ================= */
    footer {
        background-color: var(--footer-bg);
        color: var(--footer-text);
        font-family: 'Roboto', sans-serif; /* Texte courant */
        padding-top: 70px;
        margin-top: auto;
    }

    .container {
        max-width: 1250px;
        margin: 0 auto;
        padding: 0 15px;
    }

    a { text-decoration: none; color: inherit; transition: 0.3s; }
    ul { list-style: none; padding: 0; margin: 0; }

    /* ================= NEWSLETTER SECTION ================= */
    .newsletter-section {
        text-align: center;
        padding-bottom: 50px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1); /* Ligne de séparation subtile */
        margin-bottom: 50px;
    }

    .newsletter-title {
        font-family: 'Playfair Display', serif; /* Police élégante */
        font-size: 28px;
        margin-bottom: 10px;
        letter-spacing: 0.5px;
    }

    .newsletter-desc {
        color: var(--footer-text-muted);
        font-size: 14px;
        margin-bottom: 25px;
        font-weight: 300;
    }

    .newsletter-form {
        display: flex;
        justify-content: center;
        gap: 10px;
        max-width: 600px;
        margin: 0 auto;
    }

    .nl-input {
        background: transparent;
        border: 1px solid rgba(255, 255, 255, 0.3);
        padding: 12px 20px;
        color: white;
        width: 300px;
        outline: none;
        font-size: 14px;
    }
    .nl-input::placeholder { color: rgba(255, 255, 255, 0.5); }

    .nl-btn {
        background-color: var(--footer-gold);
        color: #222;
        border: none;
        padding: 12px 30px;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 13px;
        cursor: pointer;
        letter-spacing: 1px;
        transition: 0.3s;
    }
    .nl-btn:hover {
        background-color: white;
    }

    /* ================= COLONNES (GRID) ================= */
    .footer-grid {
        display: grid;
        grid-template-columns: 1.2fr 1fr 1fr 1.2fr; /* 4 colonnes ajustées */
        gap: 40px;
        padding-bottom: 60px;
    }

    .footer-col h3 {
        font-family: 'Playfair Display', serif; /* Police élégante */
        font-size: 18px;
        color: var(--footer-gold); /* Titres en beige/doré ou blanc selon préférence */
        margin-bottom: 25px;
        font-weight: 400;
    }
    
    /* Colonne Logo */
    .footer-logo {
        font-size: 24px;
        font-weight: 700;
        text-transform: uppercase;
        display: flex; align-items: center; gap: 10px;
        margin-bottom: 20px;
        font-family: 'Roboto', sans-serif;
    }
    .footer-desc {
        color: var(--footer-text-muted);
        font-size: 13px;
        line-height: 1.8;
        margin-bottom: 25px;
        max-width: 300px;
    }
    
    /* Social Icons (Ronds) */
    .social-icons { display: flex; gap: 10px; }
    .social-btn {
        width: 35px; height: 35px;
        border: 1px solid rgba(255,255,255,0.2);
        border-radius: 50%; /* Rond parfait */
        display: flex; align-items: center; justify-content: center;
        color: white;
        font-size: 14px;
    }
    .social-btn:hover {
        border-color: var(--footer-gold);
        color: var(--footer-gold);
    }

    /* Liens */
    .footer-links li { margin-bottom: 12px; }
    .footer-links a {
        color: var(--footer-text-muted);
        font-size: 14px;
        font-weight: 300;
    }
    .footer-links a:hover { color: var(--footer-gold); padding-left: 5px; }

    /* Contact List */
    .contact-list li {
        display: flex; gap: 15px; margin-bottom: 18px;
        color: var(--footer-text-muted);
        font-size: 13px;
        align-items: flex-start;
    }
    .contact-list i { 
        color: var(--footer-gold); 
        margin-top: 3px; 
    }

    /* ================= BAS DE PAGE (Copyright) ================= */
    .footer-bottom {
        background-color: var(--footer-darker);
        padding: 20px 0;
        font-size: 12px;
        color: rgba(255,255,255,0.4);
    }
    .fb-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .payment-icons { font-size: 22px; color: white; display: flex; gap: 10px; }

    /* Responsive */
    @media (max-width: 992px) {
        .footer-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 768px) {
        .newsletter-form { flex-direction: column; align-items: center; }
        .nl-input { width: 100%; margin-bottom: 10px; }
        .nl-btn { width: 100%; }
        .footer-grid { grid-template-columns: 1fr; text-align: center; }
        .social-icons { justify-content: center; }
        .contact-list li { justify-content: center; }
        .fb-container { flex-direction: column; gap: 15px; }
    }
    /* --- CSS MODIFIÉ POUR LA BARRE NEWSLETTER --- */
    
    .newsletter-form {
        display: flex;
        justify-content: center;
        align-items: center; /* Aligne verticalement */
        gap: 15px; /* L'espace entre le champ et le bouton comme sur l'image */
        margin-top: 30px;
    }

    /* Le champ "Votre adresse email" */
    .nl-input {
        /* Fond transparent sombre */
        background-color: rgba(255, 255, 255, 0.05); 
        
        /* Bordure fine gris clair */
        border: 1px solid rgba(255, 255, 255, 0.2); 
        
        color: #fff;
        padding: 16px 25px; /* Hauteur confortable */
        width: 380px;       /* Assez large */
        font-size: 14px;
        outline: none;
        
        /* IMPORTANT : Coins carrés */
        border-radius: 0; 
        transition: 0.3s;
    }

    /* Effet quand on clique dans le champ */
    .nl-input:focus {
        border-color: #d4c4a8; /* La bordure devient beige */
        background-color: rgba(255, 255, 255, 0.1);
    }
    
    .nl-input::placeholder {
        color: rgba(255, 255, 255, 0.5); /* Texte placeholder gris */
        font-style: italic; /* Optionnel : rend le placeholder élégant */
    }

    /* Le bouton "S'INSCRIRE" */
    .nl-btn {
        background-color: #d4c4a8; /* Couleur Beige/Doré */
        color: #1A3C34;            /* Texte vert foncé (couleur du fond) pour le contraste */
        border: 1px solid #d4c4a8; /* Bordure idem fond */
        
        padding: 16px 35px;        /* Même hauteur que l'input */
        font-weight: 700;
        font-size: 13px;
        text-transform: uppercase; /* MAJUSCULES */
        letter-spacing: 1px;       /* Espacement des lettres */
        cursor: pointer;
        
        /* IMPORTANT : Coins carrés */
        border-radius: 0; 
        transition: 0.3s ease;
    }

    /* Effet survol bouton */
    .nl-btn:hover {
        background-color: #fff;    /* Devient blanc */
        color: #1A3C34;            /* Texte reste foncé */
        border-color: #fff;
    }

    /* Responsive mobile */
    @media (max-width: 768px) {
        .newsletter-form {
            flex-direction: column;
            gap: 10px;
        }
        .nl-input, .nl-btn {
            width: 100%; /* Prend toute la largeur sur mobile */
        }
    }
</style>

<footer>
    <div class="container">
        
        <div class="newsletter-section">
            <h2 class="newsletter-title">Rejoignez l'Univers SOFCOS</h2>
            <p class="newsletter-desc">Recevez nos conseils beauté et offres exclusives.</p>
            
            <form action="#" method="POST" class="newsletter-form">
                <input type="email" placeholder="Votre adresse email" class="nl-input" required>
                <button type="submit" class="nl-btn">S'INSCRIRE</button>
            </form>
        </div>

        <div class="footer-grid">
            
            <div class="footer-col">
                <div class="footer-logo">
                    <i class="fas fa-leaf"></i> SOFCOS
                </div>
                <p class="footer-desc">
                    L'alliance parfaite entre nature et science. Des soins d'exception formulés au Maroc pour révéler votre éclat naturel.
                </p>
                <div class="social-icons">
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
        <div class="container fb-container">
            <div>
                &copy; <?= date('Y') ?> SOFCOS Maroc. Tous droits réservés.
            </div>
            <div class="payment-icons">
                <i class="fab fa-cc-visa"></i>
                <i class="fab fa-cc-mastercard"></i>
                <i class="fas fa-money-bill-wave"></i>
            </div>
        </div>
    </div>
</footer>