<?php
// changer-mot-de-passe.php

// 1. Démarrage de session
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once 'config.php';

// 2. SÉCURITÉ
if(!isset($_SESSION['client_id'])) {
    header('Location: connexion.php');
    exit();
}

$client_id = $_SESSION['client_id'];
$message = '';
$msg_type = '';

try {
    // Connexion DB de secours
    if(!isset($pdo)) {
        $db_host = defined('DB_HOST') ? DB_HOST : 'localhost';
        $db_name = defined('DB_NAME') ? DB_NAME : 'sofcos_db';
        $db_user = defined('DB_USER') ? DB_USER : 'root';
        $db_pass = defined('DB_PASS') ? DB_PASS : '';
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    // --- TRAITEMENT DU CHANGEMENT DE MOT DE PASSE ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $message = "Tous les champs sont obligatoires.";
            $msg_type = "error";
        } elseif ($new_password !== $confirm_password) {
            $message = "Les nouveaux mots de passe ne correspondent pas.";
            $msg_type = "error";
        } elseif (strlen($new_password) < 6) {
            $message = "Le nouveau mot de passe doit contenir au moins 6 caractères.";
            $msg_type = "error";
        } else {
            // 1. Récupérer le hash actuel du mot de passe en BDD
            $stmtUser = $pdo->prepare("SELECT mot_de_passe FROM clients WHERE id = ?");
            $stmtUser->execute([$client_id]);
            $user = $stmtUser->fetch();

            if ($user && password_verify($current_password, $user['mot_de_passe'])) {
                // 2. Ancien mot de passe correct -> On hache le nouveau
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);

                // 3. Mise à jour en BDD
                $stmtUpdate = $pdo->prepare("UPDATE clients SET mot_de_passe = ? WHERE id = ?");
                if ($stmtUpdate->execute([$new_hash, $client_id])) {
                    $message = "Votre mot de passe a été modifié avec succès.";
                    $msg_type = "success";
                } else {
                    $message = "Erreur lors de la mise à jour.";
                    $msg_type = "error";
                }
            } else {
                $message = "Le mot de passe actuel est incorrect.";
                $msg_type = "error";
            }
        }
    }

    // 3. RECUPERATION CLIENT (Juste pour le nom dans le header si besoin)
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch();

    if (!$client) {
        session_destroy();
        header('Location: connexion.php');
        exit();
    }

    // 4. STATISTIQUES (CALCULS TOUJOURS PRÉCIS SUR TOUT L'HISTORIQUE)
    $countAllStmt = $pdo->prepare("SELECT COUNT(*) FROM commandes WHERE client_id = ?");
    $countAllStmt->execute([$client_id]);
    $nombreTotalCommandes = $countAllStmt->fetchColumn();

    $sumStmt = $pdo->prepare("SELECT SUM(total) FROM commandes WHERE client_id = ? AND statut != 'Annulé'");
    $sumStmt->execute([$client_id]);
    $totalDepense = $sumStmt->fetchColumn() ?: 0;

    $countEnCoursStmt = $pdo->prepare("SELECT COUNT(*) FROM commandes WHERE client_id = ? AND statut NOT IN ('Livré', 'Annulé')");
    $countEnCoursStmt->execute([$client_id]);
    $nombreEnCours = $countEnCoursStmt->fetchColumn();

    $stats = [
        'total_commandes' => $nombreTotalCommandes,
        'total_depense'   => $totalDepense,
        'en_cours'        => $nombreEnCours
    ];

} catch (PDOException $e) {
    die("Erreur technique : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sécurité - SOFCOS Paris</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Prata&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- DESIGN SYSTEM LUXE --- */
        :root {
            --green-luxe: #1A3C34;
            --gold-accent: #C5A059;
            --beige-bg: #fdfbf7;
            --text-main: #2c2c2c;
            --white: #ffffff;
            --border-light: #e0e0e0;
        }

        body {
            margin: 0; padding: 0;
            font-family: 'Montserrat', sans-serif;
            background-color: var(--beige-bg);
            color: var(--text-main);
        }

        .compte-container {
            max-width: 1200px; margin: 40px auto; padding: 0 20px;
        }

        /* Header */
        .compte-header {
            background-color: var(--green-luxe); color: var(--white);
            padding: 50px; border-radius: 4px; margin-bottom: 40px;
            text-align: center; position: relative; overflow: hidden;
        }
        .compte-header::after {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px;
            background: linear-gradient(90deg, #d4af37, #f3e5ab);
        }
        .compte-header h1 { font-family: 'Prata', serif; font-size: 38px; margin: 0 0 10px 0; }
        .compte-header p { font-family: 'Montserrat', sans-serif; font-weight: 300; opacity: 0.9; }

        /* Stats */
        .stats-row {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px; margin-bottom: 40px;
        }
        .stat-box {
            background: var(--white); padding: 30px;
            border: 1px solid rgba(0,0,0,0.05); text-align: center;
            transition: transform 0.3s ease;
        }
        .stat-box:hover {
            transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border-bottom: 3px solid var(--gold-accent);
        }
        .stat-box i { font-size: 32px; color: var(--gold-accent); margin-bottom: 15px; }
        .stat-box h3 { font-family: 'Prata', serif; font-size: 32px; margin: 5px 0; color: var(--green-luxe); }
        .stat-box p { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: #888; }

        /* Layout */
        .compte-grid { display: grid; grid-template-columns: 280px 1fr; gap: 40px; }
        
        /* Sidebar */
        .compte-sidebar { background: var(--white); padding: 30px; height: fit-content; border: 1px solid rgba(0,0,0,0.05); }
        .sidebar-title {
            font-family: 'Prata', serif; font-size: 18px; margin-bottom: 25px; padding-bottom: 15px;
            border-bottom: 1px solid var(--border-light); color: var(--green-luxe);
        }
        .compte-menu { list-style: none; padding: 0; margin: 0; }
        .compte-menu li { margin-bottom: 8px; }
        .compte-menu a {
            display: flex; align-items: center; gap: 15px;
            padding: 14px 20px; color: var(--text-main);
            text-decoration: none; font-size: 13px; font-weight: 500;
            transition: all 0.3s; border-left: 3px solid transparent;
        }
        .compte-menu a:hover, .compte-menu a.active {
            background: #fcfcfc; color: var(--green-luxe); border-left: 3px solid var(--gold-accent);
        }
        .compte-menu a.logout { color: #d63031; }
        .compte-menu a.logout:hover { background: #fff5f5; border-color: #d63031; }

        /* Content & Form */
        .compte-content { background: var(--white); padding: 40px; border: 1px solid rgba(0,0,0,0.05); }
        .section-title {
            font-family: 'Prata', serif; font-size: 26px; margin-bottom: 30px; padding-bottom: 15px;
            border-bottom: 1px solid var(--border-light); color: var(--green-luxe);
        }

        /* Form Styles */
        .form-group { margin-bottom: 25px; max-width: 500px; }
        .form-group label {
            display: block; margin-bottom: 8px; font-size: 12px; text-transform: uppercase;
            letter-spacing: 1px; color: #666; font-weight: 600;
        }
        .form-control {
            width: 100%; padding: 12px 15px; border: 1px solid var(--border-light);
            font-family: 'Montserrat', sans-serif; font-size: 14px; box-sizing: border-box;
            transition: 0.3s; background: #fdfdfd;
        }
        .form-control:focus {
            border-color: var(--gold-accent); outline: none; background: #fff;
            box-shadow: 0 0 0 3px rgba(197, 160, 89, 0.1);
        }
        
        .btn-submit {
            background-color: var(--green-luxe); color: white; border: none;
            padding: 15px 40px; font-family: 'Montserrat', sans-serif;
            text-transform: uppercase; letter-spacing: 1px; font-weight: 600; font-size: 12px;
            cursor: pointer; transition: 0.3s; margin-top: 10px;
        }
        .btn-submit:hover { background-color: var(--gold-accent); }

        /* Alerts */
        .alert { padding: 15px; margin-bottom: 25px; font-size: 14px; border-left: 4px solid; }
        .alert-success { background: #e8f5e9; color: #1b5e20; border-color: #2ecc71; }
        .alert-error { background: #ffebee; color: #b71c1c; border-color: #e74c3c; }

        @media (max-width: 900px) {
            .compte-grid { grid-template-columns: 1fr; }
            .form-control { width: 100%; }
        }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>
    
    <div class="compte-container">
        
        <div class="compte-header">
            <h1>Sécurité du Compte</h1>
            <p>Mettez à jour votre mot de passe pour protéger vos accès</p>
        </div>
        
        <div class="stats-row">
            <div class="stat-box">
                <i class="fas fa-shopping-bag"></i>
                <h3><?= $stats['total_commandes'] ?></h3>
                <p>Commandes passées</p>
            </div>
            
            <div class="stat-box">
                <i class="fas fa-euro-sign"></i> 
                <h3><?= number_format($stats['total_depense'], 2, ',', ' ') ?> DH</h3>
                <p>Total Achats</p>
            </div>
            
            <div class="stat-box">
                <i class="fas fa-truck-loading"></i>
                <h3><?= $stats['en_cours'] ?></h3>
                <p>En cours de livraison</p>
            </div>
        </div>
        
        <div class="compte-grid">
            
            <div class="compte-sidebar">
                <div class="sidebar-title">Mon Menu</div>
                <ul class="compte-menu">
                    <li><a href="mon-compte.php"><i class="fas fa-home"></i> Tableau de bord</a></li>
                    <li><a href="mes-commandes.php"><i class="fas fa-box-open"></i> Mes commandes</a></li>
                    <li><a href="mes-informations.php"><i class="far fa-user"></i> Mes informations</a></li>
                    <li><a href="mes-adresses.php"><i class="fas fa-map-marker-alt"></i> Carnet d'adresses</a></li>
                    <li><a href="changer-mot-de-passe.php" class="active"><i class="fas fa-lock"></i> Sécurité</a></li>
                    <li style="margin-top: 15px; border-top: 1px solid #eee; padding-top: 15px;">
                        <a href="deconnexion.php" class="logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                    </li>
                </ul>
            </div>
            
            <div class="compte-content">
                <h2 class="section-title">Changer mon mot de passe</h2>
                
                <?php if($message): ?>
                    <div class="alert alert-<?= $msg_type ?>">
                        <?= $message ?>
                    </div>
                <?php endif; ?>

                <form action="changer-mot-de-passe.php" method="POST">
                    
                    <div class="form-group">
                        <label for="current_password">Mot de passe actuel</label>
                        <input type="password" id="current_password" name="current_password" class="form-control" required>
                    </div>

                    <div style="border-top: 1px solid #eee; margin: 20px 0; max-width: 500px;"></div>

                    <div class="form-group">
                        <label for="new_password">Nouveau mot de passe</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" required minlength="6">
                        <small style="color:#999; font-size:11px;">Minimum 6 caractères</small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    </div>
                    
                    <div class="form-group" style="margin-top:30px;">
                        <button type="submit" name="update_password" class="btn-submit">
                            <i class="fas fa-check-circle"></i> Modifier le mot de passe
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>