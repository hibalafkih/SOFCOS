<?php
// changer-mot-de-passe.php
session_start();
require_once 'config.php';

if(!isset($_SESSION['client_id'])) { header('Location: connexion.php'); exit(); }
$client_id = $_SESSION['client_id'];

try {
    if(!isset($pdo)) {
        $pdo = new PDO("mysql:host=localhost;dbname=sofcos_db;charset=utf8", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    $message = ''; $msg_type = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        if (empty($current) || empty($new) || empty($confirm)) {
            $message = "Tous les champs sont obligatoires."; $msg_type = "error";
        } elseif ($new !== $confirm) {
            $message = "Les nouveaux mots de passe ne correspondent pas."; $msg_type = "error";
        } elseif (strlen($new) < 6) {
            $message = "Le mot de passe doit faire 6 caractères minimum."; $msg_type = "error";
        } else {
            $stmtUser = $pdo->prepare("SELECT mot_de_passe FROM clients WHERE id = ?");
            $stmtUser->execute([$client_id]);
            $user = $stmtUser->fetch();

            if ($user && password_verify($current, $user['mot_de_passe'])) {
                $new_hash = password_hash($new, PASSWORD_DEFAULT);
                $stmtUpdate = $pdo->prepare("UPDATE clients SET mot_de_passe = ? WHERE id = ?");
                if ($stmtUpdate->execute([$new_hash, $client_id])) {
                    $message = "Mot de passe modifié avec succès."; $msg_type = "success";
                }
            } else {
                $message = "Le mot de passe actuel est incorrect."; $msg_type = "error";
            }
        }
    }

    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch();

} catch (PDOException $e) { die("Erreur : " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sécurité - SOFCOS</title>
    <link href="https://fonts.googleapis.com/css2?family=Prata&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* DESIGN SYSTEM LUXE */
        :root { --primary: #1A3C34; --gold: #C5A059; --bg-light: #F9F7F2; --white: #ffffff; --text: #2c2c2c; --shadow: 0 10px 30px rgba(26, 60, 52, 0.08); }
        body { font-family: 'Montserrat', sans-serif; background-color: var(--bg-light); color: var(--text); margin: 0; }
        .dashboard-container { max-width: 1200px; margin: 40px auto; padding: 0 20px; display: grid; grid-template-columns: 280px 1fr; gap: 40px; }
        
        /* SIDEBAR IDENTIQUE */
        .sidebar { background: var(--white); border-radius: 8px; box-shadow: var(--shadow); padding: 30px 0; height: fit-content; }
        .user-profile-summary { text-align: center; padding-bottom: 20px; border-bottom: 1px solid #e5e5e5; margin-bottom: 10px; }
        .avatar-circle { width: 80px; height: 80px; border-radius: 50%; border: 2px solid var(--gold); color: var(--gold); display: flex; align-items: center; justify-content: center; font-size: 30px; margin: 0 auto 15px; }
        .user-name { font-family: 'Prata', serif; font-size: 18px; color: var(--primary); }
        .menu-list { list-style: none; padding: 0; margin: 0; }
        .menu-link { display: flex; align-items: center; gap: 15px; padding: 16px 30px; text-decoration: none; color: #666; font-weight: 500; font-size: 14px; transition: all 0.3s; border-left: 4px solid transparent; }
        .menu-link:hover, .menu-link.active { background: linear-gradient(90deg, rgba(197,160,89,0.1) 0%, rgba(255,255,255,0) 100%); color: var(--primary); border-left-color: var(--gold); }
        .menu-link.logout { color: #d63031; margin-top: 20px; border-top: 1px solid #e5e5e5; }

        /* FORM */
        .section-box { background: var(--white); padding: 40px; border-radius: 8px; box-shadow: var(--shadow); }
        .section-title { font-family: 'Prata', serif; font-size: 24px; color: var(--primary); border-bottom: 1px solid #e5e5e5; padding-bottom: 15px; margin-bottom: 30px; }
        .form-group { margin-bottom: 25px; max-width: 500px; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: #666; font-weight: 600; }
        .form-control { width: 100%; padding: 12px 15px; border: 1px solid #e0e0e0; border-radius: 4px; font-family: 'Montserrat', sans-serif; transition: 0.3s; box-sizing: border-box; }
        .form-control:focus { border-color: var(--gold); outline: none; box-shadow: 0 0 0 3px rgba(197, 160, 89, 0.1); }
        .btn-submit { background: var(--primary); color: white; border: none; padding: 15px 40px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; font-size: 13px; cursor: pointer; transition: 0.3s; border-radius: 4px; }
        .btn-submit:hover { background: var(--gold); }
        .alert { padding: 15px; margin-bottom: 25px; border-radius: 4px; font-size: 14px; }
        .alert-success { background: #E8F5E9; color: #2E7D32; border: 1px solid #A5D6A7; }
        .alert-error { background: #FFEBEE; color: #C62828; border: 1px solid #EF9A9A; }
        
        @media (max-width: 900px) { .dashboard-container { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="dashboard-container">
        <div class="sidebar">
            <div class="user-profile-summary">
                <div class="avatar-circle"><?= strtoupper(substr($client['prenom'], 0, 1)) ?></div>
                <div class="user-name"><?= htmlspecialchars($client['prenom']) ?></div>
            </div>
            <ul class="menu-list">
                <li><a href="mon-compte.php" class="menu-link"><i class="fas fa-th-large"></i> Tableau de bord</a></li>
                <li><a href="mes-commandes.php" class="menu-link"><i class="fas fa-box"></i> Mes commandes</a></li>
                <li><a href="mes-informations.php" class="menu-link"><i class="fas fa-user-edit"></i> Profil</a></li>
                <li><a href="mes-adresses.php" class="menu-link"><i class="fas fa-map-marker-alt"></i> Adresses</a></li>
                <li><a href="changer-mot-de-passe.php" class="menu-link active"><i class="fas fa-lock"></i> Sécurité</a></li>
                <li><a href="deconnexion.php" class="menu-link logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="section-box">
                <h2 class="section-title">Changer mon mot de passe</h2>
                
                <?php if($message): ?>
                    <div class="alert alert-<?= $msg_type ?>"><?= $message ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label>Mot de passe actuel</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>

                    <div style="border-top: 1px solid #eee; margin: 30px 0; max-width: 500px;"></div>

                    <div class="form-group">
                        <label>Nouveau mot de passe</label>
                        <input type="password" name="new_password" class="form-control" required minlength="6">
                        <small style="color:#999; font-size:11px;">Minimum 6 caractères</small>
                    </div>

                    <div class="form-group">
                        <label>Confirmer le nouveau mot de passe</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>

                    <div style="margin-top: 30px;">
                        <button type="submit" name="update_password" class="btn-submit">
                            Mettre à jour
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>