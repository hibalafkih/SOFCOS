<?php
session_start();
require_once '../config.php';

// --- 1. INCLUSION DE PHPMAILER ---
// Vérifiez que le dossier s'appelle bien "PHPMailer" (ou "PHPMailer-master")
// Si vous avez une erreur "No such file", vérifiez le nom du dossier dans C:\xamppp\htdocs\SOFCOS\
require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- SÉCURITÉ ---
if(!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit(); }
if(!isset($_GET['id'])) { header('Location: contact.php'); exit(); }

$id = (int)$_GET['id'];
$conn = $pdo;
$alert = "";

// 2. RÉCUPÉRER LE MESSAGE
$stmt = $conn->prepare("SELECT * FROM messages WHERE id = ?");
$stmt->execute([$id]);
$m = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$m) die("Message introuvable");

// 3. MARQUER COMME LU (si pas déjà répondu)
if($m['statut'] == 'nouveau') {
    $conn->prepare("UPDATE messages SET statut = 'lu' WHERE id = ?")->execute([$id]);
    $m['statut'] = 'lu';
}

// 4. TRAITEMENT DE L'ENVOI DE L'EMAIL
if(isset($_POST['envoyer_reponse'])) {
    $sujet_reponse = "Réponse à votre message : " . $m['sujet'];
    $message_reponse = nl2br(htmlspecialchars($_POST['contenu_reponse']));
    $email_client = $m['email'];
    $nom_client = $m['nom'];

    // Corps du mail en HTML
    $corps_mail = "
    <html>
    <head><title>Réponse SOFCOS</title></head>
    <body style='font-family: Arial, sans-serif; color: #333;'>
        <h3 style='color: #1A3C34;'>Bonjour $nom_client,</h3>
        <p>Merci de nous avoir contactés. Voici notre réponse :</p>
        <div style='padding: 15px; border-left: 4px solid #10b981; background: #f9f9f9; margin: 15px 0;'>
            $message_reponse
        </div>
        <p>Cordialement,<br><strong>L'équipe SOFCOS</strong></p>
        <hr>
        <small style='color: #888;'>Rappel de votre message :<br> " . nl2br(htmlspecialchars($m['message'])) . "</small>
    </body>
    </html>
    ";

    $mail = new PHPMailer(true);

    try {
        // --- CONFIGURATION SMTP (Depuis config.php / .env) ---
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        // Destinataire
        $mail->setFrom(SMTP_USER, EMAIL_FROM_NAME);
        $mail->addAddress($email_client, $nom_client);

        // Contenu
        $mail->isHTML(true);
        $mail->Subject = $sujet_reponse;
        $mail->Body    = $corps_mail;
        $mail->AltBody = strip_tags($message_reponse);

        $mail->send();

        // Mise à jour BDD
        $conn->prepare("UPDATE messages SET statut = 'repondu' WHERE id = ?")->execute([$id]);
        $alert = "<div class='alert success'><i class='fas fa-check-circle'></i> Réponse envoyée avec succès !</div>";
        $m['statut'] = 'repondu';

    } catch (Exception $e) {
        $alert = "<div class='alert error'><i class='fas fa-exclamation-triangle'></i> Erreur d'envoi : {$mail->ErrorInfo}</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Lire le message - SOFCOS Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --sidebar-bg: #1A3C34; --bg-body: #f6fcf8; --text-dark: #1f2937; --accent: #10b981; }
        body { font-family: 'Poppins', sans-serif; background: var(--bg-body); color: var(--text-dark); display: flex; margin:0; }
        
        .sidebar { width: 260px; background: var(--sidebar-bg); color: white; position: fixed; height: 100vh; }
        .sidebar .brand { padding: 20px; font-size: 22px; font-weight: bold; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .nav-links { list-style: none; padding: 20px 0; }
        .nav-links a { display: block; padding: 15px 20px; color: #d1fae5; text-decoration: none; }
        .nav-links a:hover { background: rgba(255,255,255,0.1); }

        .main { margin-left: 260px; padding: 40px; width: 100%; max-width: 900px; box-sizing: border-box; }
        
        .btn-back { display: inline-flex; align-items: center; gap: 8px; color: #555; text-decoration: none; font-weight: 500; margin-bottom: 20px; }
        .btn-back:hover { color: var(--sidebar-bg); }

        .msg-card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        
        .msg-header { border-bottom: 1px solid #eee; padding-bottom: 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: start; }
        .sender-info h2 { margin: 0 0 5px 0; font-size: 20px; }
        .sender-info span { color: #888; font-size: 14px; }
        .msg-date { color: #999; font-size: 13px; text-align: right; }

        /* Badges de statut */
        .badge { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; margin-left: 10px; }
        .bg-nouveau { background: #dbeafe; color: #1e40af; }
        .bg-lu { background: #f3f4f6; color: #374151; }
        .bg-repondu { background: #dcfce7; color: #166534; }

        .msg-body { line-height: 1.6; color: #333; font-size: 15px; white-space: pre-wrap; min-height: 100px; margin-bottom: 30px;}
        
        /* Zone de réponse */
        .reply-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-top: 20px; }
        .reply-box h3 { margin-top: 0; font-size: 16px; color: var(--sidebar-bg); }
        
        textarea { width: 100%; height: 150px; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit; font-size: 14px; box-sizing: border-box; resize: vertical; margin-bottom: 15px; }
        textarea:focus { outline: none; border-color: var(--accent); ring: 2px solid #a7f3d0; }

        .btn-send { background: var(--accent); color: white; padding: 10px 25px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 14px; }
        .btn-send:hover { background: #059669; }

        /* Alertes */
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 6px; }
        .success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="brand">SOFCOS Admin</div>
        <ul class="nav-links">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="contact.php" style="background: rgba(255,255,255,0.1); color: #10b981;"><i class="fas fa-envelope"></i> Messages</a></li>
        </ul>
    </aside>

    <div class="main">
        <a href="contact.php" class="btn-back"><i class="fas fa-arrow-left"></i> Retour à la boîte de réception</a>

        <?= $alert ?>

        <div class="msg-card">
            <div class="msg-header">
                <div class="sender-info">
                    <h2><?= htmlspecialchars($m['sujet'] ?? 'Sans sujet') ?></h2>
                    <span>De : <strong><?= htmlspecialchars($m['nom']) ?></strong> &lt;<?= htmlspecialchars($m['email']) ?>&gt;</span>
                    
                    <?php 
                        $badgeClass = 'bg-lu';
                        if($m['statut'] == 'nouveau') $badgeClass = 'bg-nouveau';
                        if($m['statut'] == 'repondu') $badgeClass = 'bg-repondu';
                    ?>
                    <span class="badge <?= $badgeClass ?>"><?= ucfirst($m['statut']) ?></span>
                </div>
                <div class="msg-date">
                    Reçu le<br><strong><?= date('d/m/Y à H:i', strtotime($m['date_envoi'])) ?></strong>
                </div>
            </div>

            <div class="msg-body">
                <?= nl2br(htmlspecialchars($m['message'])) ?>
            </div>

            <div class="reply-box">
                <h3><i class="fas fa-reply"></i> Répondre au client</h3>
                <form method="POST">
                    <textarea name="contenu_reponse" placeholder="Écrivez votre réponse ici. Le client la recevra directement par email..." required></textarea>
                    <button type="submit" name="envoyer_reponse" class="btn-send">
                        <i class="fas fa-paper-plane"></i> Envoyer la réponse
                    </button>
                </form>
            </div>

        </div>
    </div>

</body>
</html>