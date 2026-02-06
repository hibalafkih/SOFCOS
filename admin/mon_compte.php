<?php
session_start();
require_once '../config.php';

if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$message = '';
$success = false;

// Récupérer les infos de l'admin
$stmt = $pdo->prepare("SELECT * FROM administrateurs WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch();

// Modifier les informations
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['update_info'])) {
        $nom = securiser($_POST['nom']);
        $email = securiser($_POST['email']);
        
        $stmt = $pdo->prepare("UPDATE administrateurs SET nom = ?, email = ? WHERE id = ?");
        $stmt->execute([$nom, $email, $_SESSION['admin_id']]);
        
        $_SESSION['admin_nom'] = $nom;
        $message = "Informations mises à jour avec succès !";
        $success = true;
    }
    
    // Changer le mot de passe
    if(isset($_POST['change_password'])) {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        
        if(password_verify($current, $admin['mot_de_passe'])) {
            if($new === $confirm && strlen($new) >= 6) {
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE administrateurs SET mot_de_passe = ? WHERE id = ?")
                    ->execute([$hash, $_SESSION['admin_id']]);
                
                $message = "Mot de passe modifié avec succès !";
                $success = true;
            } else {
                $message = "Les mots de passe ne correspondent pas ou sont trop courts";
            }
        } else {
            $message = "Mot de passe actuel incorrect";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Compte - SOFCOS Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-spa"></i> SOFCOS Admin</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="produits.php"><i class="fas fa-box"></i> Produits</a>
                <a href="categories.php"><i class="fas fa-tags"></i> Catégories</a>
                <a href="commandes.php"><i class="fas fa-shopping-cart"></i> Commandes</a>
                <a href="clients.php" class="active">
                    <i class="fas fa-users"></i> <span>Clients</span>
                </a>
                <a href="admins.php"><i class="fas fa-user-shield"></i> Administrateurs</a>
                <a href="mon_compte.php" class="active"><i class="fas fa-user-circle"></i> Mon Compte</a>
                 <a href="livraisons.php">
                    <i class="fas fa-user-shield"></i> livraisons
                </a>
                <a href="../index.php" target="_blank"><i class="fas fa-globe"></i> Voir le Site</a>
                <a href="logout.php" style="color: #e74c3c;"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="top-bar">
                <h1>Mon Compte</h1>
            </header>

            <div class="content">
                <?php if($message): ?>
                    <div class="alert <?= $success ? 'alert-success' : 'alert-error' ?>">
                        <i class="fas fa-<?= $success ? 'check-circle' : 'exclamation-circle' ?>"></i>
                        <?= $message ?>
                    </div>
                <?php endif; ?>

                <div class="dashboard-grid">
                    <!-- Informations personnelles -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3><i class="fas fa-user"></i> Informations Personnelles</h3>
                        </div>
                        <form method="POST" style="padding: 25px;">
                            <div class="form-group">
                                <label>Nom complet</label>
                                <input type="text" name="nom" required value="<?= htmlspecialchars($admin['nom']) ?>">
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" required value="<?= htmlspecialchars($admin['email']) ?>">
                            </div>
                            <div class="form-group">
                                <label>Rôle</label>
                                <input type="text" value="<?= htmlspecialchars($admin['role']) ?>" disabled style="background: #f5f5f5;">
                            </div>
                            <button type="submit" name="update_info" class="btn btn-primary">
                                <i class="fas fa-save"></i> Enregistrer
                            </button>
                        </form>
                    </div>

                    <!-- Changer le mot de passe -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3><i class="fas fa-key"></i> Changer le Mot de Passe</h3>
                        </div>
                        <form method="POST" style="padding: 25px;">
                            <div class="form-group">
                                <label>Mot de passe actuel</label>
                                <input type="password" name="current_password" required>
                            </div>
                            <div class="form-group">
                                <label>Nouveau mot de passe</label>
                                <input type="password" name="new_password" required minlength="6">
                                <small style="color: #7f8c8d;">Minimum 6 caractères</small>
                            </div>
                            <div class="form-group">
                                <label>Confirmer le nouveau mot de passe</label>
                                <input type="password" name="confirm_password" required minlength="6">
                            </div>
                            <button type="submit" name="change_password" class="btn btn-primary">
                                <i class="fas fa-lock"></i> Changer le mot de passe
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Dernière connexion -->
                <div class="dashboard-card" style="margin-top: 20px;">
                    <div style="padding: 20px;">
                        <p><i class="fas fa-clock"></i> <strong>Dernière connexion :</strong> 
                            <?= $admin['derniere_connexion'] ? date('d/m/Y à H:i', strtotime($admin['derniere_connexion'])) : 'Jamais' ?>
                        </p>
                        <p><i class="fas fa-calendar"></i> <strong>Compte créé le :</strong> 
                            <?= date('d/m/Y', strtotime($admin['date_creation'])) ?>
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>