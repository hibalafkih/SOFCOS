<?php
// ============================================================
// VALIDER_COMMANDE.PHP (Design Email Amélioré + Nom Article)
// ============================================================

// 1. Configuration & Session
if (file_exists('config.php')) { require_once 'config.php'; } 
else { require_once '../config.php'; }

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 2. Chargement PHPMailer Robuste
$root = __DIR__;
$chemins_possibles = [
    $root . '/PHPMailer/src/Exception.php',
    $root . '/vendor/autoload.php'
];

$phpmailer_trouve = false;
foreach ($chemins_possibles as $chemin) {
    if (file_exists($chemin)) {
        if (strpos($chemin, 'Exception.php') !== false) {
            require_once $root . '/PHPMailer/src/Exception.php';
            require_once $root . '/PHPMailer/src/PHPMailer.php';
            require_once $root . '/PHPMailer/src/SMTP.php';
        } else {
            require_once $chemin;
        }
        $phpmailer_trouve = true;
        break;
    }
}

if (!$phpmailer_trouve) {
    die("Erreur : PHPMailer introuvable.");
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 3. Vérifications
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['panier'])) {
    header('Location: produits.php');
    exit();
}

// 4. Connexion BDD
$db = isset($pdo) ? $pdo : (isset($conn) ? $conn : new PDO("mysql:host=localhost;dbname=sofcos_db;charset=utf8", "root", ""));
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Données Formulaire
$nom = htmlspecialchars($_POST['nom'] ?? '');
$telephone = htmlspecialchars($_POST['telephone'] ?? '');
$ville = htmlspecialchars($_POST['ville'] ?? '');
$adresse = htmlspecialchars($_POST['adresse'] ?? '');
$email_client = htmlspecialchars($_POST['email'] ?? '');

// Calculs Panier
$ids = array_keys($_SESSION['panier']);
if(empty($ids)) die("Panier vide");

$in = str_repeat('?,', count($ids) - 1) . '?';
$stmt = $db->prepare("SELECT * FROM produits WHERE id IN ($in)");
$stmt->execute($ids);
$produits_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_produits = 0;
$details_commande = []; 

foreach ($produits_db as $p) {
    $qty = $_SESSION['panier'][$p['id']];
    $prix_reel = ($p['prix_promo'] > 0) ? $p['prix_promo'] : $p['prix'];
    $total_produits += ($prix_reel * $qty);
    
    $details_commande[] = [
        'id' => $p['id'],
        'nom' => $p['nom'], // Le nom est bien ici
        'quantite' => $qty,
        'prix' => $prix_reel
    ];
}

$frais_livraison = ($total_produits >= 500) ? 0 : 30;
$total_final = $total_produits + $frais_livraison;

try {
    $db->beginTransaction();

    // Insertion Commande
    $sql = "INSERT INTO commandes (nom_client, telephone, ville, adresse, email_client, total, date_commande, statut, mode_paiement) 
            VALUES (?, ?, ?, ?, ?, ?, NOW(), 'En attente', 'Paiement à la livraison')";
    $stmt = $db->prepare($sql);
    $stmt->execute([$nom, $telephone, $ville, $adresse, $email_client, $total_final]);
    $commande_id = $db->lastInsertId();

    // Insertion Détails
    $sql_detail = "INSERT INTO commandes_details (commande_id, produit_id, quantite, prix_unitaire) VALUES (?, ?, ?, ?)";
    $stmt_detail = $db->prepare($sql_detail);
    $sql_stock = "UPDATE produits SET stock = stock - ? WHERE id = ?";
    $stmt_stock = $db->prepare($sql_stock);

    foreach ($details_commande as $item) {
        $stmt_detail->execute([$commande_id, $item['id'], $item['quantite'], $item['prix']]);
        $stmt_stock->execute([$item['quantite'], $item['id']]);
    }

    $db->commit();

    // =========================================================
    // ENVOI EMAIL (DESIGN AMÉLIORÉ SOFCOS)
    // =========================================================
    if (!empty($email_client)) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER; 
            $mail->Password   = SMTP_PASS; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom(SMTP_USER, EMAIL_FROM_NAME);
            $mail->addAddress($email_client, $nom);

            // --- CONSTRUCTION DU TABLEAU DES PRODUITS ---
            $html_produits = "";
            foreach ($details_commande as $d) {
                $total_ligne = number_format($d['prix'] * $d['quantite'], 2);
                $html_produits .= "
                <tr>
                    <td style='padding: 12px 5px; border-bottom: 1px solid #eeeeee;'>
                        <strong style='color:#1A3C34; font-size:14px;'>{$d['nom']}</strong>
                    </td>
                    <td style='padding: 12px 5px; border-bottom: 1px solid #eeeeee; text-align: center;'>
                        {$d['quantite']}
                    </td>
                    <td style='padding: 12px 5px; border-bottom: 1px solid #eeeeee; text-align: right; white-space: nowrap;'>
                        $total_ligne DH
                    </td>
                </tr>";
            }
            
            $txt_livraison = ($frais_livraison == 0) ? "<span style='color:green'>OFFERTE</span>" : number_format($frais_livraison, 2) . " DH";

            $mail->isHTML(true);
            $mail->Subject = " Confirmation de commande #$commande_id - SOFCOS";
            
            // --- DESIGN HTML DE L'EMAIL ---
            $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
                    .container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
                    .header { background-color: #1A3C34; padding: 25px; text-align: center; }
                    .header h1 { color: #C5A059; margin: 0; font-size: 24px; letter-spacing: 2px; }
                    .content { padding: 30px; color: #444444; line-height: 1.6; }
                    .order-info { background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #C5A059; }
                    .table-container { width: 100%; border-collapse: collapse; margin-top: 10px; }
                    .total-section { margin-top: 20px; text-align: right; padding-top: 15px; border-top: 2px solid #eee; }
                    .footer { background: #eeeeee; text-align: center; padding: 15px; font-size: 12px; color: #888; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>SOFCOS</h1>
                        <p style='color: #ffffff; margin: 5px 0 0 0; font-size: 12px; opacity: 0.8;'>Nature & Authenticité</p>
                    </div>

                    <div class='content'>
                        <h2 style='color: #1A3C34; margin-top: 0;'>Bonjour " . htmlspecialchars($nom) . ",</h2>
                        <p>Nous vous remercions pour votre commande. Elle a bien été enregistrée et est en cours de préparation.</p>

                        <div class='order-info'>
                            <strong>Commande N° :</strong> #$commande_id<br>
                            <strong>Date :</strong> " . date('d/m/Y') . "<br>
                            <strong>Paiement :</strong> À la livraison (Espèces)
                        </div>

                        <h3 style='border-bottom: 2px solid #C5A059; padding-bottom: 5px; display: inline-block; color: #1A3C34;'>Détails de votre commande</h3>
                        
                        <table class='table-container'>
                            <tr style='font-size: 12px; color: #888; text-transform: uppercase;'>
                                <th style='text-align: left; padding-bottom: 10px;'>Article</th>
                                <th style='text-align: center; padding-bottom: 10px;'>Qté</th>
                                <th style='text-align: right; padding-bottom: 10px;'>Prix</th>
                            </tr>
                            $html_produits
                        </table>

                        <div class='total-section'>
                            <p style='margin: 5px 0;'>Sous-total : " . number_format($total_produits, 2) . " DH</p>
                            <p style='margin: 5px 0;'>Livraison : <strong>$txt_livraison</strong></p>
                            <p style='font-size: 20px; color: #1A3C34; margin: 10px 0 0 0;'>
                                <strong>TOTAL : " . number_format($total_final, 2) . " DH</strong>
                            </p>
                        </div>

                        <div style='margin-top: 30px; font-size: 13px; color: #666;'>
                            <strong>Adresse de livraison :</strong><br>
                            $adresse, $ville<br>
                            Tél : $telephone
                        </div>
                    </div>

                    <div class='footer'>
                        Merci de votre confiance.<br>
                        L'équipe SOFCOS
                    </div>
                </div>
            </body>
            </html>
            ";

            $mail->AltBody = "Merci pour votre commande #$commande_id. Total à payer : $total_final DH.";
            $mail->send();

        } catch (Exception $e) { }
    }

    unset($_SESSION['panier']);
    header("Location: confirmation.php?id=" . $commande_id);
    exit();

} catch (Exception $e) {
    if ($db->inTransaction()) { $db->rollBack(); }
    die("<div style='background:#ef4444; color:white; padding:30px;'>Erreur : " . $e->getMessage() . "</div>");
}
?>