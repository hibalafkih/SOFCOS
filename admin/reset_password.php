<?php
require_once '../config.php';

$message = '';
$success = false;

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = securiser($_POST['email']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Vérifier si l'email existe
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    
    if(!$admin) {
        $message = "Aucun administrateur trouvé avec cet email";
    } elseif($new_password !== $confirm_password) {
        $message = "Les mots de passe ne correspondent pas";
    } elseif(strlen($new_password) < 6) {
        $message = "Le mot de passe doit contenir au moins 6 caractères";
    } else {
        // Réinitialiser le mot de passe
        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE admins SET mot_de_passe = ? WHERE email = ?");
        $stmt->execute([$hash, $email]);
        
        $message = "Mot de passe réinitialisé avec succès ! Vous pouvez maintenant vous connecter.";
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialiser le mot de passe - SOFCOS Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <i class="fas fa-key"></i>
                <h1>Réinitialiser le mot de passe</h1>
                <p>Créez un nouveau mot de passe</p>
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
                        <i class="fas fa-sign-in-alt"></i> Se connecter
                    </a>
                </div>
            <?php else: ?>
                <form method="POST" class="login-form">
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Votre email administrateur</label>
                        <input type="email" name="email" required 
                               placeholder="admin@sofcos.ma"
                               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Nouveau mot de passe</label>
                        <input type="password" name="new_password" required 
                               minlength="6"
                               placeholder="Minimum 6 caractères">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-check-circle"></i> Confirmer le mot de passe</label>
                        <input type="password" name="confirm_password" required 
                               minlength="6"
                               placeholder="Retapez le mot de passe">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-key"></i> Réinitialiser
                    </button>
                </form>
            <?php endif; ?>
            
            <div class="login-footer">
                <a href="login.php"><i class="fas fa-arrow-left"></i> Retour à la connexion</a>
            </div>
            
            <div style="margin-top: 20px; padding: 12px; background: #fff3cd; border-radius: 8px; font-size: 13px; color: #856404;">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Important :</strong> Cette page permet de réinitialiser n'importe quel mot de passe admin. 
                Supprimez ce fichier après usage pour des raisons de sécurité.
            </div>
        </div>
    </div>
</body>
</html>