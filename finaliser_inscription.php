<?php
// finaliser_inscription.php
session_start();
require_once 'config.php';
require_once 'includes/EmailManager.php'; // Important pour l'envoi du mail

// 1. Sécurité : Si pas d'infos Google en session, on redirige
if (!isset($_SESSION['inscription_temp'])) {
    header('Location: connexion.php');
    exit();
}

$google_data = $_SESSION['inscription_temp'];
$error = '';

// 2. TRAITEMENT DU FORMULAIRE (C'est la partie qu'il vous manquait)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nom = htmlspecialchars($_POST['nom']);
    $prenom = htmlspecialchars($_POST['prenom']);
    $telephone = htmlspecialchars($_POST['telephone']);
    $email = $google_data['email'];
    $google_id = $google_data['google_id'];

    if (empty($telephone)) {
        $error = "Le numéro de téléphone est obligatoire.";
    } else {
        try {
            // A. Générer un mot de passe aléatoire
            $motDePasseClair = substr(str_shuffle('abcdefghjkmnpqrstuvwxyz23456789'), 0, 10);
            $motDePasseHache = password_hash($motDePasseClair, PASSWORD_DEFAULT);

            // B. Insérer le client en base de données
            // Note : J'adapte selon votre structure (nom + prénom séparés ou non)
            // Si votre table a juste 'nom', on concatène :
            // $nomComplet = $prenom . ' ' . $nom; 
            // Si votre table a 'nom' et 'prenom', on utilise les deux :
            $stmt = $pdo->prepare("INSERT INTO clients (nom, prenom, email, telephone, mot_de_passe, google_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nom, $prenom, $email, $telephone, $motDePasseHache, $google_id]);
            
            $nouvelId = $pdo->lastInsertId();

            // C. Envoyer l'e-mail de bienvenue
            // (Vérifiez que votre méthode envoyerBienvenue accepte bien ces arguments)
            EmailManager::envoyerBienvenue($email, $prenom, $motDePasseClair);

            // D. Connecter l'utilisateur
            $_SESSION['client_id'] = $nouvelId;
            $_SESSION['client_nom'] = $prenom; // ou $nom
            $_SESSION['client_email'] = $email;

            // Nettoyer la session temporaire
            unset($_SESSION['inscription_temp']);

            // Redirection vers le compte
            header('Location: mon-compte.php');
            exit();

        } catch (PDOException $e) {
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
    <title>Finaliser l'inscription - SOFCOS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    
    <style>
        body {
            background-color: #f4f8f9;
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        
        .container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.05);
            width: 100%;
            max-width: 420px;
            text-align: center;
        }

        .icon-top {
            font-size: 40px;
            color: #1abc9c;
            border: 2px solid #1abc9c;
            border-radius: 50%;
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        h2 {
            font-size: 18px;
            color: #333;
            margin-bottom: 25px;
        }

        /* Selecteur M / Mme */
        .gender-selector {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        .gender-option {
            flex: 1;
        }
        .gender-option input {
            display: none;
        }
        .gender-option label {
            display: block;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            cursor: pointer;
            color: #333;
            font-weight: 500;
            transition: all 0.3s;
        }
        /* Quand coché */
        .gender-option input:checked + label {
            background-color: #1abc9c;
            color: white;
            border-color: #1abc9c;
        }

        /* Champs texte */
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        .input-group {
            width: 100%;
            text-align: left;
        }
        .input-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e1e1e1;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box; /* Important pour le padding */
            font-family: 'Poppins', sans-serif;
        }
        .input-group input:focus {
            border-color: #1abc9c;
            outline: none;
        }
        .input-hint {
            font-size: 11px;
            color: #999;
            margin-top: 4px;
            display: block;
        }

        /* Champ Téléphone avec drapeau */
        .phone-group {
            position: relative;
            margin-bottom: 15px;
        }
        .phone-flag {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            align-items: center;
            gap: 5px;
            color: #333;
            font-size: 14px;
            font-weight: 500;
            border-right: 1px solid #ddd;
            padding-right: 10px;
            height: 20px;
        }
        .phone-group input {
            width: 100%;
            padding: 12px 15px 12px 90px; /* Espace pour le +212 */
            border: 1px solid #e1e1e1;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .email-field {
            background-color: #f9f9f9;
            color: #777;
            margin-bottom: 25px;
        }

        .btn-submit {
            background-color: #8edcd0; /* Couleur claire comme l'image */
            color: white;
            border: none;
            width: 100%;
            padding: 12px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-submit:hover {
            background-color: #1abc9c; /* Plus foncé au survol */
        }

    </style>
</head>
<body>

<div class="container">
    <div class="icon-top">
        <i class="far fa-user"></i>
    </div>
    
    <h2>Créer votre compte</h2>

    <form action="traitement_inscription_google.php" method="POST">
        
        <div class="gender-selector">
            <div class="gender-option">
                <input type="radio" name="civilite" id="mr" value="M" checked>
                <label for="mr">M</label>
            </div>
            <div class="gender-option">
                <input type="radio" name="civilite" id="mme" value="Mme">
                <label for="mme">Mme</label>
            </div>
        </div>

        <div class="form-row">
            <div class="input-group">
                <input type="text" name="nom" value="<?= htmlspecialchars($google_data['nom']) ?>" placeholder="Nom" required>
                <span class="input-hint">35 caractères max.</span>
            </div>
            <div class="input-group">
                <input type="text" name="prenom" value="<?= htmlspecialchars($google_data['prenom']) ?>" placeholder="Prénom" required>
                <span class="input-hint">35 caractères max.</span>
            </div>
        </div>

        <div class="phone-group">
            <div class="phone-flag">
                <img src="https://flagcdn.com/w20/ma.png" alt="Maroc"> +212
            </div>
            <input type="tel" name="telephone" placeholder="Numéro de téléphone" required pattern="[0-9]{9,10}">
        </div>

        <div class="input-group">
            <div style="position: relative;">
                <span style="position: absolute; left: 15px; top: 13px; color: #ccc;">
                    <i class="far fa-envelope"></i>
                </span>
                <input type="email" value="<?= htmlspecialchars($google_data['email']) ?>" class="email-field" readonly style="padding-left: 40px;">
            </div>
        </div>

        <button type="submit" class="btn-submit">Valider</button>
    </form>
</div>

</body>
</html>