<?php
require_once '../config.php';

$message = '';
$success = false;

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = securiser($_POST['username']);
    $email = securiser($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    
    // Validations
    if(empty($username) || empty($email) || empty($password)) {
        $message = "Tous les champs sont obligatoires";
    } elseif($password !== $password_confirm) {
        $message = "Les mots de passe ne correspondent pas";
    } elseif(strlen($password) < 6) {
        $message = "Le mot de passe doit contenir au moins 6 caractères";
    } else {
        // Vérifier si l'email existe déjà
        $check = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
        $check->execute([$email]);
        
        if($check->fetch()) {
            $message = "Cet email est déjà utilisé";
        } else {
            // Créer le hash du mot de passe
            $hash = password_hash($password, PASSWORD_DEFAULT);
            
            try {
                $stmt = $pdo->prepare("INSERT INTO administrateurs (username, email, mot_de_passe) VALUES (?, ?, ?)");
                $stmt->execute([$username, $email, $hash]);
                $message = "Admin créé avec succès ! Vous pouvez maintenant vous connecter.";
                $success = true;
            } catch(PDOException $e) {
                $message = "Erreur lors de la création : " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription Admin - SOFCOS</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <i class="fas fa-user-plus"></i>
                <h1>Nouvel Administrateur</h1>
                <p>Créer un compte administrateur</p>
            </div>
            
            <?php if($message): ?>
                <div class="alert <?= $success ? 'alert-success' : 'alert-error' ?>">
                    <i class="fas fa-<?= $success ? 'check-circle' : 'exclamation-circle' ?>"></i>
                    <?= $message ?>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="login.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Aller à la connexion
                    </a>
                </div>
            <?php else: ?>
                <form method="POST" class="login-form">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Nom d'utilisateur</label>
                        <input type="text" name="username" required 
                               placeholder="Ex: Mohamed"
                               value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" name="email" required 
                               placeholder="admin@sofcos.ma"
                               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Mot de passe</label>
                        <input type="password" name="password" required 
                               minlength="6" 
                               placeholder="Minimum 6 caractères">
                        <small style="color: #7f8c8d; font-size: 12px;">Minimum 6 caractères</small>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Confirmer le mot de passe</label>
                        <input type="password" name="password_confirm" required 
                               minlength="6"
                               placeholder="Retapez le mot de passe">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Créer le compte
                    </button>
                </form>
            <?php endif; ?>
            
            <div class="login-footer">
                <a href="login.php"><i class="fas fa-arrow-left"></i> Retour à la connexion</a>
            </div>
        </div>
    </div>
</body>
</html>