<?php
session_start();
require_once 'config.php';

// --- 1. REDIRECTION AUTOMATIQUE SI DÉJÀ CONNECTÉ ---
// Si c'est un Admin connecté -> Dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: admin/dashboard.php');
    exit();
}
// Si c'est un Client connecté -> Mon Compte
if (isset($_SESSION['client_id'])) {
    // Gestion de la redirection personnalisée (ex: retour au paiement)
    if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
        header('Location: ' . $_GET['redirect']);
    } else {
        header('Location: mon-compte.php');
    }
    exit();
}

$error = null;

// --- 2. TRAITEMENT DU FORMULAIRE ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        try {
            if(!isset($pdo)) $pdo = new PDO("mysql:host=localhost;dbname=sofcos_db;charset=utf8", "root", "");

            // ====================================================
            // ÉTAPE A : VÉRIFIER SI C'EST UN ADMINISTRATEUR
            // ====================================================
            $stmtAdmin = $pdo->prepare("SELECT * FROM administrateurs WHERE email = ?");
            $stmtAdmin->execute([$email]);
            $admin = $stmtAdmin->fetch();

            if ($admin) {
                // Vérification du mot de passe Admin
                if (password_verify($password, $admin['mot_de_passe'])) {
                    
                    // Connexion ADMIN réussie
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_nom'] = $admin['nom'];
                    $_SESSION['admin_role'] = $admin['role'];

                    // Mise à jour dernière connexion
                    $pdo->prepare("UPDATE administrateurs SET derniere_connexion = NOW() WHERE id = ?")->execute([$admin['id']]);

                    // Redirection vers l'espace Admin
                    header("Location: admin/dashboard.php");
                    exit();
                }
            }

            // ====================================================
            // ÉTAPE B : SI PAS ADMIN, VÉRIFIER SI C'EST UN CLIENT
            // ====================================================
            $stmtClient = $pdo->prepare("SELECT * FROM clients WHERE email = ?");
            $stmtClient->execute([$email]);
            $client = $stmtClient->fetch();

            $passwordOk = false;
            if ($client) {
                // Vérification mot de passe (Crypté OU Clair pour vos tests)
                if (password_verify($password, $client['mot_de_passe'])) {
                    $passwordOk = true;
                } elseif ($password == $client['mot_de_passe']) {
                    $passwordOk = true; // Mode dév
                }
            }

            if ($passwordOk) {
                // Connexion CLIENT réussie
                $_SESSION['client_id'] = $client['id'];
                $_SESSION['client_nom'] = $client['nom'];
                $_SESSION['client_prenom'] = $client['prenom'];
                $_SESSION['client_email'] = $client['email'];
                $_SESSION['client_telephone'] = $client['telephone'];

                // Redirection Client
                if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
                    header("Location: " . $_GET['redirect']);
                } else {
                    header("Location: mon-compte.php");
                }
                exit();

            } else {
                // Si ni admin ni client (ou mot de passe faux)
                $error = "Email ou mot de passe incorrect.";
            }

        } catch(PDOException $e) {
            $error = "Erreur technique : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - SOFCOS Paris</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Prata&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- VARIABLES --- */
        :root {
            --green-luxe: #1A3C34;
            --grey-google: #4a4a4a;
            --beige-bg: #fdfbf7;
            --text-main: #2c2c2c;
        }

        body {
            margin: 0; padding: 0;
            font-family: 'Montserrat', sans-serif;
            background-color: var(--beige-bg);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* --- SECTION PRINCIPALE (Entre Header et Footer) --- */
        .main-login-section {
            display: flex;
            flex: 1; /* Prend tout l'espace disponible */
            min-height: 700px;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            margin: 20px 0;
            position: relative;
        }

        /* --- ANIMATIONS --- */
        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slowZoom {
            from { transform: scale(1); }
            to { transform: scale(1.1); }
        }

        /* --- COLONNE GAUCHE : PHOTO PRODUITS --- */
        .split-left {
            flex: 1.3;
            position: relative;
            overflow: hidden;
            background-color: #f4f4f4;
        }

        .bg-image {
            width: 100%;
            height: 100%;
            /* PHOTO : Composition Cosmétique Luxe (Flacons, Pinceaux, Marbre, Fleurs) */
            background-image: url('Gemini_Generated_Image_l6xvlsl6xvlsl6xv.png');
            background-size: cover;
            background-position: center;
            /* Animation Zoom Lent */
            animation: slowZoom 25s ease-in-out infinite alternate;
        }

        /* Texte Vertical "SOFCOS PARIS" */
        .brand-vertical {
            position: absolute;
            bottom: 40px; left: 40px;
            z-index: 10;
        }

        .vertical-text {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            font-family: 'Prata', serif;
            font-size: 48px;
            color: var(--green-luxe);
            letter-spacing: 6px;
            opacity: 0;
            animation: fadeSlideUp 1s ease 0.5s forwards;
            text-shadow: 0 2px 10px rgba(255,255,255,0.4); /* Petite ombre blanche pour lisibilité sur photo chargée */
        }

        /* --- COLONNE DROITE : FORMULAIRE --- */
        .split-right {
            flex: 1;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            position: relative;
        }

        .login-wrapper {
            width: 100%;
            max-width: 400px;
            opacity: 0;
            animation: fadeSlideUp 0.8s ease 0.2s forwards;
        }

        .page-title {
            font-family: 'Prata', serif;
            font-size: 42px;
            color: var(--green-luxe);
            margin: 0 0 10px 0;
        }

        .page-subtitle {
            color: #888; font-size: 13px; line-height: 1.6; margin-bottom: 35px;
        }

        /* Inputs */
        .input-group { margin-bottom: 25px; }
        
        .label-text {
            display: block; font-size: 10px; font-weight: 700; text-transform: uppercase; 
            letter-spacing: 1.5px; color: var(--text-main); margin-bottom: 8px;
        }

        .custom-input {
            width: 100%; padding: 15px; background: #fafafa; border: 1px solid #eee;
            font-family: 'Montserrat', sans-serif; font-size: 14px; color: #333;
            transition: 0.3s; box-sizing: border-box;
        }

        .custom-input:focus {
            background: white; border-color: var(--green-luxe); outline: none;
        }

        /* Checkbox */
        .options-row {
            display: flex; justify-content: space-between; align-items: center;
            font-size: 12px; color: #666; margin-bottom: 30px;
        }
        .options-row a { color: #666; text-decoration: none; transition: 0.2s; }
        .options-row a:hover { color: var(--green-luxe); text-decoration: underline; }

        /* BOUTON VERT */
        .btn-luxe {
            width: 100%; padding: 18px; background-color: var(--green-luxe);
            color: white; border: none; font-size: 12px; font-weight: 700; 
            letter-spacing: 2px; text-transform: uppercase; cursor: pointer; transition: 0.3s;
        }
        .btn-luxe:hover {
            background-color: #112923; transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(26, 60, 52, 0.15);
        }

        /* BOUTON GOOGLE (Dark Grey) */
        .btn-google-grey {
            width: 100%; padding: 16px; background-color: var(--grey-google);
            color: white; border: none; font-size: 12px; font-weight: 600;
            display: flex; align-items: center; justify-content: center;
            margin-top: 15px; cursor: pointer; text-decoration: none; transition: 0.3s;
        }
        .btn-google-grey:hover { background-color: #333; }
        .btn-google-grey i { font-size: 16px; margin-right: 10px; }

        /* Footer Inscription */
        .footer-text {
            margin-top: 35px; text-align: center; font-size: 12px; color: #999;
        }
        .footer-text a {
            color: var(--green-luxe); font-weight: 700; text-decoration: none; margin-left: 5px;
        }

        .error-box {
            padding: 15px; background: #fff2f2; border-left: 3px solid #ff4d4d;
            color: #d63031; font-size: 13px; margin-bottom: 25px;
        }

        /* RESPONSIVE */
        @media (max-width: 900px) {
            .main-login-section { flex-direction: column; min-height: auto; }
            .split-left { height: 250px; flex: none; }
            .brand-vertical { display: none; }
            .split-right { padding: 40px 20px; }
        }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <div class="main-login-section">
        <div class="split-left">
            <div class="bg-image"></div>
           
        </div>

        <div class="split-right">
            <div class="login-wrapper">
                
                <h1 class="page-title">Connexion</h1>
                <p class="page-subtitle">Accédez à votre espace beauté personnel.</p>

                <?php if ($error): ?>
                    <div class="error-box">
                        <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    
                    <div class="input-group">
                        <label class="label-text">ADRESSE EMAIL</label>
                        <input type="email" name="email" class="custom-input" placeholder="exemple@email.com" required>
                    </div>

                    <div class="input-group">
                        <label class="label-text">MOT DE PASSE</label>
                        <input type="password" name="password" class="custom-input" placeholder="••••••••" required>
                    </div>

                    <div class="options-row">
                        <label style="display:flex; align-items:center; cursor:pointer;">
                            <input type="checkbox" style="margin-right:8px; accent-color:var(--green-luxe);"> Se souvenir de moi
                        </label>
                        <a href="mot-de-passe-oublie.php">Mot de passe oublié ?</a>
                    </div>

                    <button type="submit" class="btn-luxe">SE CONNECTER</button>

                    <a href="google_login.php" class="btn-google-grey">
                        <i class="fab fa-google"></i> Continuer avec Google
                    </a>

                </form>

                <div class="footer-text">
                    Vous n'avez pas de compte ? <a href="inscription.php">S'INSCRIRE MAINTENANT</a>
                </div>

            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

</body>
</html>