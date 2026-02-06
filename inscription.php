<?php
session_start();
require_once 'config.php';
// On inclut le gestionnaire d'email s'il est présent
if(file_exists('includes/EmailManager.php')) {
    require_once 'includes/EmailManager.php';
}

// Redirection si déjà connecté
if (isset($_SESSION['client_id'])) {
    header('Location: mon-compte.php');
    exit();
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Récupération et nettoyage des champs simples
    $prenom = trim($_POST['prenom']);
    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $adresse = trim($_POST['adresse']);
    $code_postal = trim($_POST['code_postal']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 2. LOGIQUE TÉLÉPHONE MAROC (+212)
    $tel_input = trim($_POST['telephone']);
    // On enlève les espaces et le premier '0' (ex: 06 12... -> 612...)
    $tel_clean = ltrim(str_replace(' ', '', $tel_input), '0');
    // On formate pour la base de données
    $telephone = "+212" . $tel_clean;

    // 3. LOGIQUE VILLE (Liste ou Saisie Manuelle)
    $ville_select = trim($_POST['ville']);
    // Si l'utilisateur a choisi "Autre", on prend le champ manuel, sinon le menu
    $ville = ($ville_select === 'Autre') ? trim($_POST['ville_autre']) : $ville_select;

    // 4. VALIDATIONS
    if(empty($nom) || empty($prenom) || empty($email) || empty($password)) {
        $error = "Veuillez remplir les champs obligatoires (*)";
    } elseif(empty($ville)) {
        $error = "Veuillez sélectionner ou saisir une ville.";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email invalide.";
    } elseif(strlen($password) < 6) {
        $error = "Le mot de passe doit faire au moins 6 caractères.";
    } elseif($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        try {
            if(!isset($pdo)) $pdo = new PDO("mysql:host=localhost;dbname=sofcos_db;charset=utf8", "root", "");
            
            // Vérifier si l'email existe déjà
            $check = $pdo->prepare("SELECT id FROM clients WHERE email = ?");
            $check->execute([$email]);
            
            if($check->fetch()) {
                $error = "Cet email est déjà utilisé par un autre compte.";
            } else {
                // Inscription
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                $sql = "INSERT INTO clients (prenom, nom, email, telephone, mot_de_passe, adresse, ville, code_postal, date_inscription) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $insert = $pdo->prepare($sql);
                
                if($insert->execute([$prenom, $nom, $email, $telephone, $password_hash, $adresse, $ville, $code_postal])) {
                    
                    // 5. CONNEXION AUTOMATIQUE
                    $new_id = $pdo->lastInsertId();
                    $_SESSION['client_id'] = $new_id;
                    $_SESSION['client_prenom'] = $prenom;
                    $_SESSION['client_nom'] = $nom;
                    $_SESSION['client_email'] = $email;
                    
                    // Redirection vers le tableau de bord
                    header('Location: mon-compte.php');
                    exit();
                } else {
                    $error = "Une erreur technique est survenue.";
                }
            }
        } catch (PDOException $e) {
            $error = "Erreur système : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - SOFCOS Paris</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Prata&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- STYLE GENERAL --- */
        body {
            margin: 0; padding: 0;
            font-family: 'Montserrat', sans-serif;
            overflow-x: hidden;
            background-color: #f0f0f0;
        }

        /* FOND ANIMÉ */
        .animated-background {
            position: fixed; top: 0; left: 0;
            width: 100vw; height: 100vh;
            z-index: -1;
            /* Dégradé Vert Luxe */
            background: linear-gradient(-45deg, #0f2b24, #1A3C34, #2a5c50, #132e28);
            background-size: 400% 400%;
            animation: gradientSmooth 25s ease infinite;
        }

        @keyframes gradientSmooth {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* CONTENEUR CENTRÉ */
        .page-wrapper {
            display: flex; justify-content: center; align-items: center;
            min-height: calc(100vh - 150px); padding: 60px 20px;
        }

        /* CARTE FORMULAIRE */
        .form-card {
            background: #ffffff; width: 100%; max-width: 700px;
            padding: 50px; border-radius: 8px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            position: relative;
        }

        /* TYPOGRAPHIE */
        .title-block { text-align: center; margin-bottom: 35px; }
        .title-block h1 {
            font-family: 'Prata', serif; font-size: 34px; color: #1A3C34; margin: 0 0 10px 0;
        }
        .title-block p { color: #666; font-size: 14px; margin: 0; }

        /* GRILLE ET CHAMPS */
        .grid-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .full-row { margin-bottom: 20px; }

        label {
            display: block; font-size: 11px; font-weight: 700; color: #1A3C34;
            text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px;
        }

        .input-field {
            width: 100%; padding: 14px; background: #fdfdfd;
            border: 1px solid #ccc; border-radius: 4px;
            font-family: 'Montserrat', sans-serif; font-size: 14px;
            transition: 0.3s; box-sizing: border-box;
        }
        .input-field:focus {
            border-color: #C5A059; background: #fff;
            box-shadow: 0 0 0 4px rgba(197, 160, 89, 0.1); outline: none;
        }

        /* STYLE SELECT (Flèche) */
        select.input-field {
            appearance: none; -webkit-appearance: none; -moz-appearance: none;
            background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%231A3C34%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E");
            background-repeat: no-repeat;
            background-position: right 15px top 50%;
            background-size: 12px auto; cursor: pointer; color: #333;
        }

        /* STYLE TÉLÉPHONE (+212) */
        .phone-wrapper {
            display: flex; align-items: stretch; width: 100%;
            border: 1px solid #ccc; border-radius: 4px;
            background: #fdfdfd; transition: 0.3s; overflow: hidden;
        }
        .phone-wrapper:focus-within {
            border-color: #C5A059; background: #fff;
            box-shadow: 0 0 0 4px rgba(197, 160, 89, 0.1);
        }
        .country-indicator {
            display: flex; align-items: center; background-color: #eee;
            padding: 0 15px; font-size: 14px; font-weight: 600; color: #333;
            border-right: 1px solid #ddd;
        }
        .input-phone-field {
            width: 100%; border: none !important; outline: none !important;
            background: transparent !important; padding: 14px;
            font-family: 'Montserrat', sans-serif; font-size: 14px;
            box-shadow: none !important;
        }

        /* BOUTON */
        .btn-register {
            width: 100%; padding: 16px; background-color: #1A3C34;
            color: white; border: none; font-size: 13px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 2px; border-radius: 4px; cursor: pointer;
            transition: 0.3s;
        }
        .btn-register:hover { background-color: #C5A059; transform: translateY(-2px); }

        /* ERREUR */
        .alert-danger {
            background: #fee2e2; color: #991b1b; padding: 15px;
            border-radius: 4px; text-align: center; margin-bottom: 25px;
        }

        .footer-links { text-align: center; margin-top: 25px; font-size: 13px; color: #666; }
        .footer-links a { color: #C5A059; text-decoration: none; font-weight: 600; }

        /* Animation d'apparition input ville */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .grid-row { grid-template-columns: 1fr; gap: 0; }
            .grid-row > div { margin-bottom: 20px; }
            .form-card { padding: 30px 20px; }
        }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <div class="animated-background"></div>

    <div class="page-wrapper">
        <div class="form-card">
            
            <div class="title-block">
                <h1>Créer un compte</h1>
                <p>Rejoignez l'univers exclusif SOFCOS</p>
            </div>

            <?php if ($error): ?>
                <div class="alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                
                <div class="grid-row">
                    <div>
                        <label>Prénom *</label>
                        <input type="text" name="prenom" class="input-field" required value="<?= isset($_POST['prenom']) ? htmlspecialchars($_POST['prenom']) : '' ?>">
                    </div>
                    <div>
                        <label>Nom *</label>
                        <input type="text" name="nom" class="input-field" required value="<?= isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : '' ?>">
                    </div>
                </div>

                <div class="grid-row">
                    <div>
                        <label>Email *</label>
                        <input type="email" name="email" class="input-field" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                    </div>
                    <div>
                        <label>Téléphone</label>
                        <div class="phone-wrapper">
                            <div class="country-indicator">
                                🇲🇦 +212
                            </div>
                            <input type="tel" name="telephone" class="input-phone-field" placeholder="6 00 00 00 00" value="<?= isset($_POST['telephone']) ? htmlspecialchars($_POST['telephone']) : '' ?>">
                        </div>
                    </div>
                </div>

                <div class="full-row">
                    <label>Adresse de livraison</label>
                    <input type="text" name="adresse" class="input-field" placeholder="Numéro, rue, quartier..." value="<?= isset($_POST['adresse']) ? htmlspecialchars($_POST['adresse']) : '' ?>">
                </div>

                <div class="grid-row">
                    <div>
                        <label>Ville</label>
                        <select name="ville" id="villeSelect" class="input-field" onchange="toggleVilleAutre()">
                            <option value="">Sélectionnez votre ville</option>
                            </select>
                        
                        <input type="text" 
                               name="ville_autre" 
                               id="villeAutreInput" 
                               class="input-field" 
                               placeholder="Précisez votre ville"
                               style="display: none; margin-top: 10px; animation: fadeIn 0.5s;"
                               value="<?= isset($_POST['ville_autre']) ? htmlspecialchars($_POST['ville_autre']) : '' ?>">
                    </div>
                    <div>
                        <label>Code Postal</label>
                        <input type="text" name="code_postal" class="input-field" value="<?= isset($_POST['code_postal']) ? htmlspecialchars($_POST['code_postal']) : '' ?>">
                    </div>
                </div>

                <div class="grid-row">
                    <div>
                        <label>Mot de passe *</label>
                        <input type="password" name="password" class="input-field" placeholder="Min. 6 caractères" required>
                    </div>
                    <div>
                        <label>Confirmation *</label>
                        <input type="password" name="confirm_password" class="input-field" required>
                    </div>
                </div>

                <button type="submit" class="btn-register">S'inscrire</button>

            </form>

            <div class="footer-links">
                Vous avez déjà un compte ? <a href="connexion.php">Se connecter</a>
            </div>

        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        const villesMaroc = [
            "Agadir", "Al Hoceima", "Assilah", "Azemmour", "Beni Mellal", "Benslimane", 
            "Berrechid", "Bouskoura", "Casablanca", "Chefchaouen", "Dakhla", "Dar Bouazza", 
            "El Jadida", "Errachidia", "Essaouira", "Fès", "Fnideq", "Guelmim", 
            "Ifrane", "Kénitra", "Khemisset", "Khenifra", "Khouribga", "Ksar El Kebir", 
            "Laâyoune", "Larache", "Marrakech", "Martil", "Meknès", "Midelt", 
            "Mohammedia", "Nador", "Ouarzazate", "Oujda", "Rabat", "Safi", "Salé", 
            "Sefrou", "Settat", "Sidi Kacem", "Sidi Slimane", "Skhirat", "Tanger", 
            "Tan-Tan", "Taroudant", "Taza", "Témara", "Tétouan", "Tiznit", "Youssoufia", "Autre"
        ];

        const select = document.getElementById("villeSelect");
        const inputAutre = document.getElementById("villeAutreInput");
        
        // On récupère la valeur précédente en cas d'erreur PHP pour la remettre
        const oldVille = "<?= isset($_POST['ville']) ? htmlspecialchars($_POST['ville']) : '' ?>";
        const oldAutre = "<?= isset($_POST['ville_autre']) ? htmlspecialchars($_POST['ville_autre']) : '' ?>";

        // 1. Remplir le select
        villesMaroc.forEach(ville => {
            const option = document.createElement("option");
            option.value = ville;
            option.textContent = ville;
            
            if (ville === oldVille) {
                option.selected = true;
            }
            
            select.appendChild(option);
        });

        // 2. Fonction afficher/cacher le champ
        function toggleVilleAutre() {
            if (select.value === 'Autre') {
                inputAutre.style.display = 'block';
                inputAutre.required = true;
                // Si on a déjà une valeur "Autre" (retour erreur), on ne l'écrase pas
                if(!inputAutre.value) inputAutre.focus();
            } else {
                inputAutre.style.display = 'none';
                inputAutre.required = false;
            }
        }

        // Lancer la vérification au chargement de la page
        window.addEventListener('load', toggleVilleAutre);
    </script>

</body>
</html>