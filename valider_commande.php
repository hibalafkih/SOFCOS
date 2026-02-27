<?php
// ============================================================
// VALIDER_COMMANDE.PHP (Version Corrigée - Erreur Array/Int)
// ============================================================

// 1. Configuration & Session
require_once 'config.php'; // config.php gère déjà la session
require_once 'includes/EmailManager.php'; // On inclut notre gestionnaire d'emails

// 2. Vérifications de base
if (empty($_SESSION['panier'])) {
    header('Location: produits.php');
    exit();
}

// Récupération ID Client
$client_id = isset($_SESSION['client_id']) ? $_SESSION['client_id'] : null;

// 3. Connexion BDD
try {
    $db = new PDO("mysql:host=localhost;dbname=sofcos_db;charset=utf8", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion BDD : " . $e->getMessage());
}

// 4. Récupération des données POST
$nom = htmlspecialchars($_POST['nom'] ?? '');
$telephone = htmlspecialchars($_POST['telephone'] ?? '');
$ville = htmlspecialchars($_POST['ville'] ?? '');
$adresse = htmlspecialchars($_POST['adresse'] ?? '');
$email_client = htmlspecialchars($_POST['email'] ?? '');

// 5. Préparation des données produits
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

// 6. TRANSACTION SQL
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

    // 7. ENVOI DE L'EMAIL DE CONFIRMATION via EmailManager
    // On vérifie que la classe existe (au cas où le fichier serait manquant) et que l'email est fourni
    if (class_exists('EmailManager') && !empty($email_client)) {
        
        // A. Préparation du tableau de produits au format attendu par l'EmailManager
        $produits_pour_email = [];
        foreach ($liste_finale as $item) {
            $produits_pour_email[] = [
                'nom' => $item['nom'],
                'qte' => $item['quantite'],
                'prix' => $item['prix_unitaire']
            ];
        }
        
        // B. Appel de la méthode centralisée pour envoyer l'email
        // EmailManager gère déjà les erreurs en interne, donc pas besoin de try/catch ici
        EmailManager::envoyerConfirmationCommande(
            $email_client,
            $nom,
            $commande_id,
            date('d/m/Y H:i'),
            'Paiement à la livraison',
            $produits_pour_email,
            $total_produits, // sous-total
            $frais_livraison,
            $total_final, // total
            $adresse . "\n" . $ville, // Adresse complète
            $telephone
        );
    }

    // 8. FIN ET REDIRECTION
    unset($_SESSION['panier']);
    header("Location: confirmation.php?id=" . $commande_id);
    exit();

} catch (PDOException $e) {
    if ($db->inTransaction()) { $db->rollBack(); }
    // Affiche l'erreur exacte pour le débogage
    die("Erreur SQL lors de la commande : " . $e->getMessage());
}
?>