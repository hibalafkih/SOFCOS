<?php
session_start();
require_once '../config.php';

// SÉCURITÉ : Vérifier si connecté
if(!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit(); }

$db = isset($pdo) ? $pdo : (isset($conn) ? $conn : new PDO("mysql:host=localhost;dbname=sofcos_db", "root", ""));
$msg_alert = "";

// --- 0. RECUPERER LE ROLE DE L'UTILISATEUR CONNECTÉ ---
$stmt = $db->prepare("SELECT * FROM administrateurs WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$me = $stmt->fetch(PDO::FETCH_ASSOC);

// Est-ce que je suis un Super Admin ?
$i_am_admin = ($me['role'] === 'admin');

// --- 1. AJOUTER UN ADMIN (Réservé aux Admins) ---
if(isset($_POST['add_admin'])) {
    if($i_am_admin) {
        $nom = htmlspecialchars($_POST['nom']);
        $email = htmlspecialchars($_POST['email']);
        $pass = $_POST['password'];
        $role = $_POST['role'] ?? 'gestionnaire';

        $check = $db->prepare("SELECT id FROM administrateurs WHERE email = ?");
        $check->execute([$email]);

        if($check->rowCount() > 0) {
            $msg_alert = "<div class='alert error'><i class='fas fa-exclamation-circle'></i> Email déjà utilisé.</div>";
        } else {
            $pass_hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO administrateurs (nom, email, mot_de_passe, role) VALUES (?, ?, ?, ?)");
            if($stmt->execute([$nom, $email, $pass_hash, $role])) {
                $msg_alert = "<div class='alert success'><i class='fas fa-check-circle'></i> Compte créé avec succès.</div>";
            }
        }
    } else {
        $msg_alert = "<div class='alert error'><i class='fas fa-ban'></i> Action refusée : Vous n'êtes pas Administrateur.</div>";
    }
}

// --- 2. MODIFIER LE MOT DE PASSE (Réservé aux Admins) ---
if(isset($_POST['update_password'])) {
    if($i_am_admin) {
        $target_id = (int)$_POST['edit_id'];
        $new_pass = $_POST['new_password'];
        $pass_hash = password_hash($new_pass, PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("UPDATE administrateurs SET mot_de_passe = ? WHERE id = ?");
        if($stmt->execute([$pass_hash, $target_id])) {
            $msg_alert = "<div class='alert success'><i class='fas fa-key'></i> Mot de passe mis à jour.</div>";
        } else {
            $msg_alert = "<div class='alert error'>Erreur lors de la mise à jour.</div>";
        }
    } else {
        $msg_alert = "<div class='alert error'><i class='fas fa-ban'></i> Seul l'administrateur peut modifier les mots de passe.</div>";
    }
}

// --- 3. SUPPRIMER UN ADMIN (Réservé aux Admins) ---
if(isset($_GET['delete'])) {
    if($i_am_admin) {
        $id_to_del = (int)$_GET['delete'];
        if($id_to_del != $_SESSION['admin_id']) {
            $db->prepare("DELETE FROM administrateurs WHERE id = ?")->execute([$id_to_del]);
            $msg_alert = "<div class='alert success'><i class='fas fa-trash'></i> Compte supprimé.</div>";
        }
    } else {
        $msg_alert = "<div class='alert error'><i class='fas fa-ban'></i> Action non autorisée.</div>";
    }
}

// Récupérer la liste
$admins = $db->query("SELECT * FROM administrateurs ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Gestion de l'admin à modifier (Chargement des infos)
$edit_admin = null;
if(isset($_GET['edit']) && $i_am_admin) { // On ne charge que si on est Admin
    $edit_id = (int)$_GET['edit'];
    foreach($admins as $a) { if($a['id'] == $edit_id) $edit_admin = $a; }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Comptes - SOFCOS</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { 
            --primary: #1A3C34; 
            --primary-light: #26544a;
            --bg-body: #f3f4f6; 
            --sidebar-width: 270px; 
        }
        body { font-family: 'Outfit', sans-serif; background: var(--bg-body); margin:0; display: flex; }
        .main { margin-left: var(--sidebar-width); padding: 40px; width: 100%; box-sizing: border-box; }
        h1 { color: var(--primary); margin-bottom: 30px; font-size: 24px; }
        
        .grid-container { display: grid; grid-template-columns: 1fr 350px; gap: 30px; }
        .card { background: white; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.04); overflow: hidden; }
        
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 20px; background: #fcfcfc; color: #888; font-size: 12px; text-transform: uppercase; border-bottom: 1px solid #eee; }
        td { padding: 18px 20px; border-bottom: 1px solid #f0f0f0; }

        .form-box { padding: 25px; background: white; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.04); margin-bottom: 20px; border: 1px solid #eee; }
        .form-edit { border: 2px solid var(--primary); background: #f0fdf4; }
        .form-title { font-size: 17px; font-weight: 600; color: var(--primary); margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }
        
        .input-group { margin-bottom: 15px; }
        .input-group label { display: block; font-size: 13px; margin-bottom: 5px; color: #666; }
        .input-group input, .input-group select { width: 100%; padding: 11px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; font-family: inherit; }
        .input-group input:focus { border-color: var(--primary); outline: none; }
        
        .btn-submit { width: 100%; background: var(--primary); color: white; border: none; padding: 12px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .btn-submit:hover { background: var(--primary-light); }
        
        .badge-role { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .role-admin { background: #1A3C34; color: white; }
        .role-gestionnaire { background: #e2e8f0; color: #475569; }

        .actions { display: flex; gap: 8px; justify-content: flex-end; }
        .btn-icon { width: 34px; height: 34px; border-radius: 8px; display: flex; align-items: center; justify-content: center; text-decoration: none; font-size: 14px; transition: 0.2s; }
        .btn-edit { background: #f0fdf4; color: var(--primary); border: 1px solid #dcfce7; }
        .btn-del { background: #fee2e2; color: #991b1b; }
        .btn-edit:hover { background: var(--primary); color: white; }

        .alert { padding: 15px; margin-bottom: 20px; border-radius: 8px; display: flex; align-items: center; gap: 10px; font-size: 14px; }
        .alert.success { background: #dcfce7; color: #166534; border-left: 5px solid #22c55e; }
        .alert.error { background: #fee2e2; color: #991b1b; border-left: 5px solid #ef4444; }
        
        /* Message d'info pour gestionnaire */
        .info-card { padding: 25px; background: #fff7ed; border-radius: 12px; text-align: center; color: #9a3412; font-size: 14px; border: 1px solid #fed7aa; }

        .avatar-circle {
            width: 38px; height: 38px; background: #f0fdf4; color: var(--primary);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-weight: bold; margin-right: 12px; float: left; border: 1px solid #dcfce7;
        }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main">
        <h1>Gestion des Comptes</h1>
        <?= $msg_alert ?>

        <div class="grid-container">
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>Identité</th>
                            <th>Rôle</th>
                            <th style="text-align:right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($admins as $admin): 
                            $is_me = ($admin['id'] == $_SESSION['admin_id']);
                            $initial = strtoupper(substr($admin['nom'], 0, 1));
                        ?>
                        <tr>
                            <td>
                                <div class="avatar-circle"><?= $initial ?></div>
                                <div style="display:inline-block; padding-top:2px;">
                                    <strong><?= htmlspecialchars($admin['nom']) ?></strong>
                                    <?php if($is_me): ?><small style="color:var(--primary); font-weight:bold;"> (Moi)</small><?php endif; ?><br>
                                    <small style="color:#888;"><?= htmlspecialchars($admin['email']) ?></small>
                                </div>
                            </td>
                            <td>
                                <span class="badge-role <?= $admin['role'] == 'admin' ? 'role-admin' : 'role-gestionnaire' ?>">
                                    <?= ucfirst($admin['role']) ?>
                                </span>
                            </td>
                            <td class="actions">
                                <?php if($i_am_admin): ?>
                                    
                                    <a href="?edit=<?= $admin['id'] ?>" class="btn-icon btn-edit" title="Modifier le mot de passe">
                                        <i class="fas fa-lock"></i>
                                    </a>
                                    
                                    <?php if(!$is_me): // L'admin ne peut pas se supprimer lui-même ?>
                                        <a href="?delete=<?= $admin['id'] ?>" class="btn-icon btn-del" onclick="return confirm('Supprimer ce compte définitivement ?')" title="Supprimer">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    <?php endif; ?>

                                <?php else: ?>
                                    <span style="color:#cbd5e1; font-size:12px;"><i class="fas fa-lock"></i> Lecture seule</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="sidebar-forms">
                
                <?php if($i_am_admin): ?>
                    
                    <?php if($edit_admin): ?>
                        <div class="form-box form-edit">
                            <div class="form-title">
                                <i class="fas fa-shield-alt"></i> Sécuriser le compte
                            </div>
                            <p style="font-size: 13px; color:#444; margin-bottom:15px;">
                                Nouveau MDP pour : <strong><?= htmlspecialchars($edit_admin['nom']) ?></strong>
                            </p>
                            <form method="POST">
                                <input type="hidden" name="edit_id" value="<?= $edit_admin['id'] ?>">
                                <div class="input-group">
                                    <label>Nouveau mot de passe</label>
                                    <input type="password" name="new_password" required autofocus>
                                </div>
                                <button type="submit" name="update_password" class="btn-submit">
                                    Enregistrer
                                </button>
                                <div style="text-align:center; margin-top:12px;">
                                    <a href="admins.php" style="font-size:12px; color:#666; text-decoration:none;">
                                        <i class="fas fa-times"></i> Annuler
                                    </a>
                                </div>
                            </form>
                        </div>

                    <?php else: ?>
                        <div class="form-box">
                            <div class="form-title"><i class="fas fa-plus-circle"></i> Nouveau Compte</div>
                            <form method="POST">
                                <div class="input-group">
                                    <label>Nom Complet</label>
                                    <input type="text" name="nom" required placeholder="Prénom Nom">
                                </div>
                                <div class="input-group">
                                    <label>Adresse Email</label>
                                    <input type="email" name="email" required placeholder="email@sofcos.com">
                                </div>
                                <div class="input-group">
                                    <label>Type de profil</label>
                                    <select name="role">
                                        <option value="gestionnaire">Gestionnaire (Accès standard)</option>
                                        <option value="admin">Administrateur (Accès total)</option>
                                    </select>
                                </div>
                                <div class="input-group">
                                    <label>Mot de passe initial</label>
                                    <input type="password" name="password" required>
                                </div>
                                <button type="submit" name="add_admin" class="btn-submit">
                                    Créer l'administrateur
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    
                    <div class="info-card">
                        <i class="fas fa-user-lock" style="font-size:24px; margin-bottom:15px;"></i><br>
                        <strong>Accès Restreint</strong><br><br>
                        Vous êtes connecté en tant que <strong>Gestionnaire</strong>.<br><br>
                        Vous ne pouvez pas modifier les comptes ou les mots de passe.<br><br>
                        Veuillez contacter un Administrateur pour toute modification.
                    </div>

                <?php endif; ?>

            </div>
        </div>
    </div>
</body>
</html>