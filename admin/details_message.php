<?php
// admin/details_message.php
require_once '../config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Configuration Email
$root = dirname(__DIR__);
$chemins = [$root.'/vendor/autoload.php', $root.'/includes/EmailManager.php'];
foreach ($chemins as $c) { if (file_exists($c)) require_once $c; }

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if(!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit(); }
if(!isset($_GET['id'])) { header('Location: contact.php'); exit(); }

$id = (int)$_GET['id'];
$alert = "";

// Connexion BDD
$db = isset($pdo) ? $pdo : (isset($conn) ? $conn : new PDO("mysql:host=localhost;dbname=sofcos_db", "root", ""));

// Récupérer le message
$stmt = $db->prepare("SELECT * FROM messages WHERE id = ?");
$stmt->execute([$id]);
$m = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$m) die("Message introuvable");

// Marquer comme LU si c'est "nouveau" (mais pas si déjà répondu)
if($m['statut'] == 'nouveau') {
    $db->prepare("UPDATE messages SET statut = 'lu' WHERE id = ?")->execute([$id]);
    $m['statut'] = 'lu';
}
// --- TRAITEMENT DE LA RÉPONSE (AVEC MOUCHARD DE DÉBOGAGE) ---
if(isset($_POST['envoyer_reponse'])) {
    $sujet_reponse = "RE: " . $m['sujet'];
    $message_reponse = nl2br(htmlspecialchars($_POST['contenu_reponse']));

    $mail = new PHPMailer(true);
    try {
        // Configuration SMTP (Depuis config.php / .env)
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER; 
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(SMTP_USER, EMAIL_FROM_NAME);
        $mail->addAddress($m['email'], $m['nom']);

        $mail->isHTML(true);
        $mail->Subject = $sujet_reponse;
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif;'>
                <h2>Bonjour " . htmlspecialchars($m['nom']) . ",</h2>
                <p>Voici notre réponse :</p>
                <div style='background:#f0f0f0; padding:15px; border-left:4px solid #1A3C34; margin:15px 0;'>
                    $message_reponse
                </div>
                <p>Cordialement,<br>L'équipe SOFCOS</p>
            </div>
        ";
        $mail->AltBody = strip_tags($message_reponse);

        // 1. On envoie le mail
        $mail->send();

        // --- 2. DÉBUT DU TEST SQL (LE MOUCHARD) ---
        // On force la base de données à nous dire s'il y a un problème
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        try {
            // On essaie de changer le statut
            $sql = "UPDATE messages SET statut = 'repondu' WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$id]);

            // Si on arrive ici, c'est que ça a marché !
            $m['statut'] = 'repondu'; 
            $alert = "success";

        } catch (PDOException $e) {
            // SI ERREUR : On arrête tout et on affiche le message en ROUGE
            die("<div style='background:#ef4444; color:white; padding:30px; font-size:18px; font-family:sans-serif; margin:20px;'>
                    <strong>🚨 ERREUR BASE DE DONNÉES DÉTECTÉE :</strong><br><br>" 
                    . $e->getMessage() . 
                    "<br><br>👉 Cela confirme probablement que votre colonne 'statut' refuse le mot 'repondu'.<br>
                    Allez dans phpMyAdmin et exécutez la commande SQL donnée précédemment.
                 </div>");
        }
        // --- FIN DU TEST ---

    } catch (Exception $e) {
        $alert = "error";
        $errorMsg = $mail->ErrorInfo;
    }
}
// --- TRAITEMENT DE LA RÉPONSE ---

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Lecture Message - Admin SOFCOS</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #1A3C34;
            --gold: #C5A059;
            --bg-body: #f3f4f6;
            --sidebar-width: 270px;
        }

        body { margin: 0; font-family: 'Outfit', sans-serif; background: var(--bg-body); display: flex; min-height: 100vh; }
        
        /* Sidebar incluse */
        .sidebar { width: var(--sidebar-width); background: var(--primary); color: white; position: fixed; height: 100vh; z-index: 100; }

        .main-content { margin-left: var(--sidebar-width); flex: 1; padding: 40px; box-sizing: border-box; }
        
        .header-bar { margin-bottom: 30px; }
        .btn-back { color: #555; text-decoration: none; font-weight: 500; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s; }
        .btn-back:hover { color: var(--primary); transform: translateX(-5px); }

        .message-card {
            background: white; border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.04);
            overflow: hidden;
        }
        .card-header {
            padding: 30px; border-bottom: 1px solid #eee;
            display: flex; justify-content: space-between; align-items: flex-start;
        }
        .meta h1 { margin: 0 0 10px 0; font-size: 22px; color: var(--primary); }
        
        /* Badges */
        .status-badge { padding: 6px 14px; border-radius: 30px; font-size: 12px; font-weight: 700; text-transform: uppercase; }
        .bg-nouveau { background: #e0f2fe; color: #0284c7; }
        .bg-lu { background: #f3f4f6; color: #64748b; }
        .bg-repondu { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }

        .card-body { padding: 40px 30px; font-size: 16px; line-height: 1.8; color: #444; white-space: pre-wrap; }

        .reply-area { background: #fafaf9; padding: 30px; border-top: 1px solid #eee; }
        .reply-title { font-size: 16px; font-weight: 600; color: var(--primary); margin-bottom: 15px; }
        
        textarea {
            width: 100%; height: 160px; padding: 15px;
            border: 2px solid #e5e5e5; border-radius: 10px;
            font-family: inherit; font-size: 14px; resize: vertical; outline: none; box-sizing: border-box;
        }
        textarea:focus { border-color: var(--gold); }

        .btn-send {
            margin-top: 15px; background: var(--primary); color: white;
            border: none; padding: 14px 35px; border-radius: 8px;
            font-size: 15px; font-weight: 600; cursor: pointer;
            transition: 0.3s;
        }
        .btn-send:hover { background: #14302a; }

        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 10px; align-items: center; font-weight: 500; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="header-bar">
            <a href="contact.php" class="btn-back"><i class="fas fa-arrow-left"></i> Retour aux messages</a>
        </div>

        <?php if($alert == "success"): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Réponse envoyée ! Le statut est passé à "Répondu".
            </div>
        <?php elseif($alert == "error"): ?>
            <div class="alert alert-error">
                <i class="fas fa-times-circle"></i> Erreur : <?= $errorMsg ?>
            </div>
        <?php endif; ?>

        <div class="message-card">
            <div class="card-header">
                <div class="meta">
                    <h1><?= htmlspecialchars($m['sujet']) ?></h1>
                    <p>De : <strong><?= htmlspecialchars($m['nom']) ?></strong></p>
                    <p style="font-size:12px; color:#999;"><?= date('d/m/Y à H:i', strtotime($m['date_envoi'])) ?></p>
                </div>
                
                <?php if($m['statut'] == 'repondu'): ?>
                    <span class="status-badge bg-repondu"><i class="fas fa-check"></i> Répondu</span>
                <?php elseif($m['statut'] == 'nouveau'): ?>
                    <span class="status-badge bg-nouveau">Nouveau</span>
                <?php else: ?>
                    <span class="status-badge bg-lu">Lu</span>
                <?php endif; ?>
            </div>

            <div class="card-body">
                <?= nl2br(htmlspecialchars($m['message'])) ?>
            </div>

            <div class="reply-area">
                <div class="reply-title"><i class="fas fa-reply"></i> Répondre au client</div>
                <form method="POST">
                    <textarea name="contenu_reponse" placeholder="Rédigez votre réponse ici..." required></textarea>
                    <button type="submit" name="envoyer_reponse" class="btn-send">
                        <i class="fas fa-paper-plane"></i> Envoyer la réponse
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>