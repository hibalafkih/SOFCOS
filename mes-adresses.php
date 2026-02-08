<?php
// mes-adresses.php

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

    // --- TRAITEMENT DU FORMULAIRE (Mise à jour adresse) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_address'])) {
        $adresse = trim($_POST['adresse']);
        $ville = trim($_POST['ville']);
        $code_postal = trim($_POST['code_postal']); // Vérifiez si cette colonne existe dans votre BDD
        $pays = trim($_POST['pays']);               // Vérifiez si cette colonne existe dans votre BDD
        $telephone = trim($_POST['telephone']);
        $infos_sup = trim($_POST['infos_sup']);     // Complément d'adresse

        // Mise à jour
        // NOTE : Si vous n'avez pas les colonnes code_postal ou pays, retirez-les de cette requête
        $sql = "UPDATE clients SET adresse = ?, ville = ?, code_postal = ?, pays = ?, telephone = ?, infos_sup = ? WHERE id = ?";
        
        // Si les colonnes n'existent pas encore, utilisez cette version simplifiée :
        // $sql = "UPDATE clients SET adresse = ?, ville = ?, telephone = ? WHERE id = ?";
        // $params = [$adresse, $ville, $telephone, $client_id];

        $stmtUpdate = $pdo->prepare($sql);
        
        try {
            // Exécution avec tous les champs (Ordre important)
            $stmtUpdate->execute([$adresse, $ville, $code_postal, $pays, $telephone, $infos_sup, $client_id]);
            
            $message = "Votre carnet d'adresses a été mis à jour avec succès.";
            $msg_type = "success";
        } catch (PDOException $e) {
            $message = "Erreur lors de la mise à jour. Vérifiez que les colonnes (code_postal, pays, etc.) existent bien en base de données.";
            $msg_type = "error";
            // Pour le debug : echo $e->getMessage();
        }
    }

    // 3. RECUPERATION DONNEES CLIENT
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch();

    if (!$client) {
        session_destroy();
        header('Location: connexion.php');
        exit();
    }

    // 4. STATISTIQUES (CALCULS TOUJOURS PRÉCIS)
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
    <title>Mes Adresses - SOFCOS Paris</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Prata&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- DESIGN SYSTEM LUXE (Identique) --- */
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
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
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
        textarea.form-control { resize: vertical; min-height: 100px; }
        
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
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>
    
    <div class="compte-container">
        
        <div class="compte-header">
            <h1>Mon Carnet d'Adresses</h1>
            <p>Gérez vos coordonnées de livraison et facturation</p>
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
                    <li><a href="mes-adresses.php" class="active"><i class="fas fa-map-marker-alt"></i> Carnet d'adresses</a></li>
                    <li><a href="changer-mot-de-passe.php"><i class="fas fa-lock"></i> Sécurité</a></li>
                    <li style="margin-top: 15px; border-top: 1px solid #eee; padding-top: 15px;">
                        <a href="deconnexion.php" class="logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                    </li>
                </ul>
            </div>
            
            <div class="compte-content">
                <h2 class="section-title">Adresse de livraison par défaut</h2>
                
                <?php if($message): ?>
                    <div class="alert alert-<?= $msg_type ?>">
                        <?= $message ?>
                    </div>
                <?php endif; ?>

                <form action="mes-adresses.php" method="POST">
                    
                    <div class="form-group">
                        <label for="adresse">Rue / Adresse principale</label>
                        <textarea id="adresse" name="adresse" class="form-control" required placeholder="N° Rue, Bâtiment, Résidence..."><?= htmlspecialchars($client['adresse'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="infos_sup">Complément d'adresse (Optionnel)</label>
                        <input type="text" id="infos_sup" name="infos_sup" class="form-control" 
                               value="<?= htmlspecialchars($client['infos_sup'] ?? '') ?>" placeholder="Étage, Digicode, Instructions...">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="code_postal">Code Postal</label>
                            <input type="text" id="code_postal" name="code_postal" class="form-control" 
                                   value="<?= htmlspecialchars($client['code_postal'] ?? '') ?>" placeholder="ex: 75000">
                        </div>
                        <div class="form-group">
                            <label for="ville">Ville</label>
                            <input type="text" id="ville" name="ville" class="form-control" 
                                   value="<?= htmlspecialchars($client['ville'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="pays">Pays</label>
                            <input type="text" id="pays" name="pays" class="form-control" 
                                   value="<?= htmlspecialchars($client['pays'] ?? 'Maroc') ?>">
                        </div>
                        <div class="form-group">
                            <label for="telephone">Téléphone (pour la livraison)</label>
                            <input type="text" id="telephone" name="telephone" class="form-control" 
                                   value="<?= htmlspecialchars($client['telephone'] ?? '') ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-top:20px;">
                        <button type="submit" name="update_address" class="btn-submit">
                            <i class="far fa-save"></i> Mettre à jour mon adresse
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>