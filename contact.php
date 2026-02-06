<?php
// ============================================================
// LOGIQUE PHP (Sécurisée & Anti-Spam)
// ============================================================
require_once 'config.php';
$msg_success = "";
$msg_error = "";

if(isset($_POST['btn_contact'])) {
    // 1. Anti-Spam "Honeypot" (Si ce champ caché est rempli, c'est un robot)
    if(!empty($_POST['website'])) { die(); }

    $nom = htmlspecialchars(trim($_POST['nom']));
    $email = htmlspecialchars(trim($_POST['email']));
    $sujet = htmlspecialchars(trim($_POST['sujet']));
    $message = htmlspecialchars(trim($_POST['message']));

    if(!empty($nom) && !empty($email) && !empty($message)) {
        try {
            $sql = "INSERT INTO messages (nom, email, sujet, message, date_envoi, statut) VALUES (?, ?, ?, ?, NOW(), 'nouveau')";
            $stmt = $pdo->prepare($sql);
            if($stmt->execute([$nom, $email, $sujet, $message])) {
                $msg_success = "Merci $nom. Votre message a été transmis à notre conciergerie.";
            }
        } catch(PDOException $e) {
            $msg_error = "Une erreur technique est survenue. Veuillez réessayer.";
        }
    } else {
        $msg_error = "Veuillez remplir les champs obligatoires.";
    }
}

include 'includes/header.php';
?>

<style>
    /* 1. HERO SECTION */
    .hero-contact {
        background: linear-gradient(rgba(26, 60, 52, 0.9), rgba(26, 60, 52, 0.8)), url('https://images.unsplash.com/photo-1616394584738-fc6e612e71b9?q=80&w=1920&auto=format&fit=crop');
        background-size: cover; background-position: center; background-attachment: fixed;
        padding: 140px 20px 200px; text-align: center; color: white;
    }
    .hero-title { font-family: 'Playfair Display', serif; font-size: 50px; margin: 0; letter-spacing: 1px; }
    .hero-subtitle { font-family: 'Montserrat', sans-serif; font-weight: 300; font-size: 16px; margin-top: 15px; opacity: 0.9; }

    /* 2. WRAPPER FLOTTANT (OVERLAP) */
    .wrapper-contact {
        max-width: 1100px; margin: -120px auto 80px; 
        display: flex; background: white; border-radius: 4px; 
        box-shadow: 0 30px 60px rgba(0,0,0,0.15); overflow: hidden; position: relative; z-index: 10;
    }

    /* GAUCHE : INFOS */
    .info-panel {
        flex: 1; background-color: #1A3C34; color: white; padding: 60px 40px;
        background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCIgdmlld0JveD0iMCAwIDQwIDQwIiBmaWxsPSJub25lIiBzdHJva2U9InJnYmEoMTk3LCAxNjAsIDg5LCAwLjA1KSIgc3Ryb2tlLXdpZHRoPSIxIj48cGF0aCBkPSJNMCAyMGw0MCAwbS0yMC0yMGwwIDQwIiAvPjwvc3ZnPg==');
    }
    .info-item { margin-bottom: 40px; display: flex; gap: 20px; }
    .info-icon { width: 40px; height: 40px; border: 1px solid rgba(197, 160, 89, 0.3); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #C5A059; flex-shrink: 0; }
    .info-content h4 { margin: 0 0 5px; color: #C5A059; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; }
    .info-content p { margin: 0; font-size: 15px; line-height: 1.6; opacity: 0.9; font-family: 'Playfair Display', serif; }

    /* DROITE : FORMULAIRE */
    .form-panel { flex: 1.4; padding: 60px 50px; background: white; }
    .form-title { font-family: 'Playfair Display', serif; color: #1A3C34; font-size: 30px; margin-bottom: 30px; }

    /* INPUTS ANIMÉS */
    .input-group { position: relative; margin-bottom: 35px; }
    .input-field {
        width: 100%; padding: 10px 0; border: none; border-bottom: 1px solid #ddd;
        font-family: 'Montserrat', sans-serif; font-size: 15px; background: transparent; transition: 0.3s;
    }
    .input-field:focus { outline: none; border-bottom-color: #C5A059; }
    
    .input-label {
        position: absolute; top: 10px; left: 0; color: #999; font-size: 14px; pointer-events: none; transition: 0.3s;
    }
    .input-field:focus ~ .input-label, .input-field:not(:placeholder-shown) ~ .input-label {
        top: -15px; font-size: 11px; color: #1A3C34; font-weight: 600;
    }

    /* BOUTON CHARGEMENT */
    .btn-luxe {
        background-color: #1A3C34 !important; color: white !important;
        border: none; padding: 16px 40px; font-size: 13px; font-weight: 600;
        text-transform: uppercase; letter-spacing: 2px; cursor: pointer;
        transition: 0.3s; border-radius: 2px; display: flex; align-items: center; gap: 10px;
    }
    .btn-luxe:hover { background-color: #C5A059 !important; transform: translateY(-3px); }
    .btn-luxe:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }

    /* 3. SECTION FAQ ACCORDÉON */
    .faq-section { max-width: 900px; margin: 0 auto 80px; padding: 0 20px; }
    .faq-title { text-align: center; color: #1A3C34; font-family: 'Playfair Display', serif; font-size: 28px; margin-bottom: 40px; }
    
    .accordion-item { border-bottom: 1px solid #eee; margin-bottom: 10px; }
    .accordion-header {
        background: none; border: none; width: 100%; text-align: left; padding: 20px 0;
        font-size: 16px; font-weight: 500; color: #333; cursor: pointer;
        display: flex; justify-content: space-between; align-items: center; font-family: 'Montserrat', sans-serif;
    }
    .accordion-header:hover { color: #C5A059; }
    .accordion-content { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; color: #666; line-height: 1.6; font-size: 14px; }
    .accordion-content p { margin: 0 0 20px; }
    
    /* 4. CARTE */
    .map-container { width: 100%; height: 400px; filter: grayscale(100%); transition: 0.5s; }
    .map-container:hover { filter: grayscale(0%); }

    /* ALERTES */
    .alert { padding: 15px; margin-bottom: 20px; font-size: 14px; display: flex; align-items: center; gap: 10px; }
    .alert-success { background: #f0fdf4; color: #166534; border-left: 3px solid #166534; }
    .alert-error { background: #fef2f2; color: #991b1b; border-left: 3px solid #991b1b; }

    /* RESPONSIVE */
    @media(max-width: 800px) {
        .wrapper-contact { flex-direction: column; margin-top: 0; box-shadow: none; }
        .hero-contact { padding: 100px 20px; }
    }
</style>

<div class="hero-contact">
    <h1 class="hero-title">Contact & Conciergerie</h1>
    <p class="hero-subtitle">Notre équipe est à votre disposition pour vous conseiller.</p>
</div>

<div class="wrapper-contact">
    
    <div class="info-panel">
        <div class="info-item">
            <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
            <div class="info-content">
                <h4>Boutique & Siège</h4>
                <p>123 Avenue Mohamed V,<br>Casablanca, Maroc</p>
            </div>
        </div>
        <div class="info-item">
            <div class="info-icon"><i class="fas fa-phone"></i></div>
            <div class="info-content">
                <h4>Téléphone</h4>
                <p>+212 6 00 11 22 33<br><span style="font-size:12px; opacity:0.7">9h-18h / Lundi au Samedi</span></p>
            </div>
        </div>
        <div class="info-item">
            <div class="info-icon"><i class="fas fa-envelope"></i></div>
            <div class="info-content">
                <h4>Email</h4>
                <p>contact@sofcos.ma</p>
            </div>
        </div>
    </div>

    <div class="form-panel">
        <h2 class="form-title">Envoyez-nous un message</h2>

        <?php if($msg_success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $msg_success ?></div>
        <?php endif; ?>
        <?php if($msg_error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> <?= $msg_error ?></div>
        <?php endif; ?>

        <form method="POST" id="contactForm">
            <input type="text" name="website" style="display:none;">

            <div class="input-group">
                <input type="text" name="nom" class="input-field" placeholder=" " required 
                       value="<?= isset($_SESSION['client_nom']) ? $_SESSION['client_prenom'].' '.$_SESSION['client_nom'] : '' ?>">
                <label class="input-label">Nom Complet</label>
            </div>

            <div class="input-group">
                <input type="email" name="email" class="input-field" placeholder=" " required
                       value="<?= isset($_SESSION['client_email']) ? $_SESSION['client_email'] : '' ?>">
                <label class="input-label">Adresse Email</label>
            </div>

            <div class="input-group">
                <input type="text" name="sujet" class="input-field" placeholder=" ">
                <label class="input-label">Objet de la demande</label>
            </div>

            <div class="input-group">
                <textarea name="message" class="input-field" rows="3" placeholder=" " required style="resize:none;"></textarea>
                <label class="input-label">Votre message</label>
            </div>

            <button type="submit" name="btn_contact" class="btn-luxe" id="submitBtn">
                <span>Envoyer le message</span> <i class="fas fa-arrow-right"></i>
            </button>
        </form>
    </div>
</div>

<div class="faq-section">
    <h3 class="faq-title">Questions Fréquentes</h3>
    
    <div class="accordion-item">
        <button class="accordion-header">Quels sont les délais de livraison ? <i class="fas fa-plus" style="font-size:12px;"></i></button>
        <div class="accordion-content"><p>Nous livrons partout au Maroc en 24h à 48h ouvrables via Amana ou nos livreurs privés.</p></div>
    </div>
    
    <div class="accordion-item">
        <button class="accordion-header">Les produits sont-ils 100% naturels ? <i class="fas fa-plus" style="font-size:12px;"></i></button>
        <div class="accordion-content"><p>Oui, SOFCOS garantit des compositions sans paraben, sans sulfate et issus de l'agriculture biologique locale.</p></div>
    </div>

    <div class="accordion-item">
        <button class="accordion-header">Comment retourner un produit ? <i class="fas fa-plus" style="font-size:12px;"></i></button>
        <div class="accordion-content"><p>Vous disposez de 7 jours après réception. Le produit ne doit pas avoir été ouvert. Contactez-nous pour la procédure.</p></div>
    </div>
</div>

<div class="map-container">
    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3323.846433568285!2d-7.632298684849646!3d33.58309864983058!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xda7d2e0a2f42337%3A0x2802613b52479d23!2sCasablanca!5e0!3m2!1sfr!2sma!4v1677688533081!5m2!1sfr!2sma" 
            width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
</div>

<script>
    // 1. Accordéon FAQ
    const acc = document.getElementsByClassName("accordion-header");
    for (let i = 0; i < acc.length; i++) {
        acc[i].addEventListener("click", function() {
            this.classList.toggle("active");
            const icon = this.querySelector('i');
            const panel = this.nextElementSibling;
            
            if (panel.style.maxHeight) {
                panel.style.maxHeight = null;
                icon.classList.remove('fa-minus');
                icon.classList.add('fa-plus');
            } else {
                panel.style.maxHeight = panel.scrollHeight + "px";
                icon.classList.remove('fa-plus');
                icon.classList.add('fa-minus');
            } 
        });
    }

    // 2. Animation Bouton Envoi
    document.getElementById('contactForm').addEventListener('submit', function() {
        const btn = document.getElementById('submitBtn');
        const span = btn.querySelector('span');
        const icon = btn.querySelector('i');
        
        // Change le texte et ajoute un spinner
        span.innerText = "Envoi en cours...";
        icon.className = "fas fa-spinner fa-spin"; // Icône de chargement
        btn.style.opacity = "0.8";
    });
</script>

<?php include 'includes/footer.php'; ?>