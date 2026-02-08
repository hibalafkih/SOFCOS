<?php
// ============================================================
// VALIDER_COMMANDE.PHP (Version Corrigée - Erreur Array/Int)
// ============================================================

// 1. Configuration & Session
if (file_exists('config.php')) { require_once 'config.php'; } 
else { require_once '../config.php'; }

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 2. Chargement PHPMailer (Si disponible, sinon on ignore sans planter)
$root = __DIR__;
$use_mail = false;
if (file_exists($root . '/vendor/autoload.php')) {
    require_once $root . '/vendor/autoload.php';
    $use_mail = true;
} elseif (file_exists($root . '/PHPMailer/src/Exception.php')) {
    require_once $root . '/PHPMailer/src/Exception.php';
    require_once $root . '/PHPMailer/src/PHPMailer.php';
    require_once $root . '/PHPMailer/src/SMTP.php';
    $use_mail = true;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 3. Vérifications de base
if (empty($_SESSION['panier'])) {
    header('Location: produits.php');
    exit();
}

// Récupération ID Client
$client_id = isset($_SESSION['client_id']) ? $_SESSION['client_id'] : null;

// 4. Connexion BDD
try {
    $db = new PDO("mysql:host=localhost;dbname=sofcos_db;charset=utf8", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion BDD : " . $e->getMessage());
}

// 5. Récupération des données POST
$nom = htmlspecialchars($_POST['nom'] ?? '');
$telephone = htmlspecialchars($_POST['telephone'] ?? '');
$ville = htmlspecialchars($_POST['ville'] ?? '');
$adresse = htmlspecialchars($_POST['adresse'] ?? '');
$email_client = htmlspecialchars($_POST['email'] ?? '');

// 6. Préparation des données produits
$ids = array_keys($_SESSION['panier']);
if(empty($ids)) die("Panier vide");

$in = str_repeat('?,', count($ids) - 1) . '?';
$stmt = $db->prepare("SELECT * FROM produits WHERE id IN ($in)");
$stmt->execute($ids);
$produits_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

// On recrée un tableau propre pour éviter les erreurs "offset on int"
$liste_finale = [];
$total_produits = 0;

foreach ($produits_db as $p) {
    $id = $p['id'];
    $qty = $_SESSION['panier'][$id]; // Quantité session
    $prix = ($p['prix_promo'] > 0) ? $p['prix_promo'] : $p['prix'];
    
    $total_produits += ($prix * $qty);
    
    // On stocke tout ce dont on a besoin pour l'insertion
    $liste_finale[] = [
        'id_produit' => $id,
        'nom' => $p['nom'],
        'quantite' => $qty,
        'prix_unitaire' => $prix
    ];
}

$frais_livraison = ($total_produits >= 500) ? 0 : 30;
$total_final = $total_produits + $frais_livraison;

// 7. TRANSACTION SQL
try {
    $db->beginTransaction();

    // A. Insertion Commande
    $sql_cmd = "INSERT INTO commandes (client_id, nom_client, telephone, ville, adresse, email_client, total, date_commande, statut, mode_paiement) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'En attente', 'Paiement à la livraison')";
    
    $stmt = $db->prepare($sql_cmd);
    $stmt->execute([$client_id, $nom, $telephone, $ville, $adresse, $email_client, $total_final]);
    
    $commande_id = $db->lastInsertId();

    // B. Insertion Détails (Table commande_details)
    $sql_detail = "INSERT INTO commandes_details (commande_id, produit_id, quantite, prix_unitaire) VALUES (?, ?, ?, ?)";
    $stmt_detail = $db->prepare($sql_detail);
    
    // C. Mise à jour Stock
    $sql_stock = "UPDATE produits SET stock = stock - ? WHERE id = ?";
    $stmt_stock = $db->prepare($sql_stock);

    // BOUCLE CORRIGÉE : On utilise $liste_finale qui est sûre
    foreach ($liste_finale as $item) {
        // Insertion ligne commande
        // On s'assure que les valeurs ne sont pas nulles
        if ($item['id_produit']) {
            $stmt_detail->execute([
                $commande_id, 
                $item['id_produit'], 
                $item['quantite'], 
                $item['prix_unitaire']
            ]);
            
            // Update stock
            $stmt_stock->execute([$item['quantite'], $item['id_produit']]);
        }
    }

    $db->commit();

    // 8. ENVOI EMAIL (Optionnel, dans un bloc try séparé pour ne pas bloquer)
    if ($use_mail && !empty($email_client)) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; 
            $mail->SMTPAuth = true;
            $mail->Username = 'votre_email@gmail.com'; // À configurer
            $mail->Password = 'votre_mot_de_passe_app'; // À configurer
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';

            $mail->setFrom('noreply@sofcos.com', 'SOFCOS');
            $mail->addAddress($email_client, $nom);
            
            $mail->isHTML(true);
            $mail->Subject = "Confirmation commande #$commande_id";
            $mail->Body = "<h3>Merci $nom !</h3><p>Votre commande #$commande_id d'un total de $total_final DH a bien été reçue.</p>";
            
            $mail->send();
        } catch (Exception $e) {
            // On ignore l'erreur mail pour ne pas bloquer la commande
        }
    }

    // 9. FIN ET REDIRECTION
    unset($_SESSION['panier']);
    header("Location: confirmation.php?id=" . $commande_id);
    exit();

} catch (PDOException $e) {
    if ($db->inTransaction()) { $db->rollBack(); }
    // Affiche l'erreur exacte pour le débogage
    die("Erreur SQL lors de la commande : " . $e->getMessage());
}
?>