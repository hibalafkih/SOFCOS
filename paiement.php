<?php
session_start();
require_once 'config.php';
// On utilise include_once pour éviter l'erreur fatale si le fichier manque
include_once 'includes/EmailManager.php';

// --- INITIALISATION ---
$panier = $_SESSION['panier'] ?? [];

// 1. Si panier vide -> Redirection Panier
if (empty($panier)) {
    header("Location: panier.php");
    exit();
}

// 2. Calcul du Panier
$ids = array_keys($panier);
$panier_complet = [];
$total_panier = 0;

if (!empty($ids)) {
    if(!isset($pdo)) $pdo = new PDO("mysql:host=localhost;dbname=sofcos_db;charset=utf8", "root", "");
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    $stmt = $pdo->prepare("SELECT * FROM produits WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $produits_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($produits_db as $p) {
        $p['qty_panier'] = $panier[$p['id']];
        $panier_complet[] = $p;
        $total_panier += $p['prix'] * $p['qty_panier'];
    }
}

// Frais
$frais_livraison = ($total_panier > 500) ? 0 : 30;
$total_a_payer = $total_panier + $frais_livraison;

// 3. TRAITEMENT DE LA COMMANDE (Uniquement si connecté)
$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'commander') {
    
    if (!isset($_SESSION['client'])) {
        $error = "Vous devez être connecté pour commander.";
    } else {
        $adresse = htmlspecialchars($_POST['adresse']);
        $ville = htmlspecialchars($_POST['ville']);
        $telephone = htmlspecialchars($_POST['telephone']);
        $paiement = htmlspecialchars($_POST['paiement']);

        if (empty($adresse) || empty($ville) || empty($telephone)) {
            $error = "Veuillez remplir tous les champs.";
        } else {
            // Insertion Commande
            $stmt = $pdo->prepare("INSERT INTO commandes (client_id, montant_total, adresse_livraison, ville, telephone, methode_paiement, date_creation) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$_SESSION['client']['id'], $total_a_payer, $adresse, $ville, $telephone, $paiement]);
            $commande_id = $pdo->lastInsertId();

            // Insertion Détails
            $stmt_det = $pdo->prepare("INSERT INTO details_commande (commande_id, produit_id, nom_produit, quantite, prix_unitaire) VALUES (?, ?, ?, ?, ?)");
            foreach ($panier_complet as $item) {
                $stmt_det->execute([$commande_id, $item['id'], $item['nom'], $item['qty_panier'], $item['prix']]);
            }

            // Email
            if (class_exists('EmailManager')) {
                EmailManager::envoyerConfirmationCommande($_SESSION['client']['email'], $_SESSION['client']['prenom'], $commande_id, $total_a_payer);
            }

            // Succès
            $_SESSION['panier'] = [];
            header("Location: confirmation.php?id=" . $commande_id);
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Finaliser la commande - SOFCOS</title>
    <link href="https://fonts.googleapis.com/css2?family=Prata&family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --green-dark: #1A3C34; --gold: #C5A059; --bg: #f8f9fa; }
        body { font-family: 'Montserrat', sans-serif; background: var(--bg); margin: 0; padding-bottom: 50px; }
        
        .checkout-container { max-width: 1100px; margin: 40px auto; display: flex; gap: 30px; padding: 0 20px; }
        .col-left { flex: 2; }
        .col-right { flex: 1; }

        .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); margin-bottom: 20px; }
        
        h2 { font-family: 'Prata', serif; color: var(--green-dark); border-bottom: 2px solid var(--gold); padding-bottom: 10px; margin-bottom: 20px; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: 600; font-size: 13px; margin-bottom: 5px; }
        .input-field { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; font-family: 'Montserrat'; }
        
        /* Boutons Paiement */
        .payment-options { display: flex; gap: 10px; }
        .payment-radio { display: none; }
        .payment-label { flex: 1; border: 1px solid #ddd; padding: 15px; border-radius: 8px; cursor: pointer; text-align: center; transition: 0.3s; }
        .payment-radio:checked + .payment-label { border-color: var(--green-dark); background: #f0fdf4; color: var(--green-dark); font-weight: bold; }
        
        .btn-pay { width: 100%; background: var(--green-dark); color: white; border: none; padding: 15px; font-weight: bold; text-transform: uppercase; cursor: pointer; border-radius: 5px; transition: 0.3s; }
        .btn-pay:hover { background: var(--gold); }

        /* Connexion Alert */
        .login-alert { background: #e0f2f1; padding: 30px; border-radius: 10px; text-align: center; border: 1px solid #b2dfdb; }
        .btn-login-link { display: inline-block; background: var(--green-dark); color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 10px; font-weight: bold; }
        
        @media (max-width: 800px) { .checkout-container { flex-direction: column; } }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="checkout-container">
        
        <div class="col-left">
            <?php if ($error): ?>
                <div style="background:#fee2e2; color:#b91c1c; padding:15px; border-radius:5px; margin-bottom:20px;">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <h2>1. Identification</h2>
                <?php if (isset($_SESSION['client'])): ?>
                    <div style="display:flex; align-items:center; gap:10px; color: #2e7d32;">
                        <i class="fas fa-check-circle" style="font-size:20px;"></i>
                        <div>
                            Connecté en tant que <strong><?= htmlspecialchars($_SESSION['client']['prenom']) ?> <?= htmlspecialchars($_SESSION['client']['nom']) ?></strong><br>
                            <small><?= htmlspecialchars($_SESSION['client']['email']) ?></small>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="login-alert">
                        <i class="fas fa-lock" style="font-size: 30px; color: var(--green-dark); margin-bottom: 10px;"></i>
                        <h3>Vous n'êtes pas connecté</h3>
                        <p>Pour finaliser votre commande et suivre sa livraison, veuillez vous identifier.</p>
                        <a href="connexion.php?redirect=paiement.php" class="btn-login-link">Me connecter</a>
                        <br><br>
                        <a href="inscription.php" style="color: var(--green-dark); font-size: 13px;">Pas encore de compte ? Créer un compte</a>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (isset($_SESSION['client'])): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="commander">
                    
                    <div class="card">
                        <h2>2. Livraison</h2>
                        <div class="form-group">
                            <label>Adresse complète</label>
                            <textarea name="adresse" class="input-field" rows="2" required placeholder="Rue, N°, Quartier..."></textarea>
                        </div>
                        <div style="display:flex; gap:15px;">
                            <div class="form-group" style="flex:1">
                                <label>Ville</label>
                                <input type="text" name="ville" class="input-field" required>
                            </div>
                            <div class="form-group" style="flex:1">
                                <label>Téléphone</label>
                                <input type="tel" name="telephone" class="input-field" required>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <h2>3. Paiement</h2>
                        <div class="payment-options">
                            <input type="radio" name="paiement" value="Espece" id="cod" class="payment-radio" checked>
                            <label for="cod" class="payment-label">
                                <i class="fas fa-money-bill-wave"></i><br>À la livraison
                            </label>

                            <input type="radio" name="paiement" value="Carte" id="card" class="payment-radio">
                            <label for="card" class="payment-label">
                                <i class="far fa-credit-card"></i><br>Carte Bancaire
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn-pay">Confirmer la commande</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="col-right">
            <div class="card">
                <h3>Récapitulatif</h3>
                <?php foreach($panier_complet as $p): ?>
                    <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:10px; color:#555;">
                        <span><?= $p['qty_panier'] ?>x <?= htmlspecialchars($p['nom']) ?></span>
                        <span><?= number_format($p['prix'] * $p['qty_panier'], 2) ?> DH</span>
                    </div>
                <?php endforeach; ?>
                <hr style="border:0; border-top:1px solid #eee; margin:15px 0;">
                <div style="display:flex; justify-content:space-between; font-weight:bold; color:var(--green-dark); font-size:18px;">
                    <span>Total</span>
                    <span><?= number_format($total_a_payer, 2) ?> DH</span>
                </div>
            </div>
        </div>

    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>