<?php
// suivi.php

// 1. Session et Config
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'config.php';

// 2. SÉCURITÉ
if(!isset($_SESSION['client_id'])) {
    header('Location: connexion.php');
    exit();
}

// 3. VÉRIFICATION ID COMMANDE
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: mon-compte.php');
    exit();
}

$commande_id = (int)$_GET['id'];
$client_id = $_SESSION['client_id'];

try {
    if(!isset($pdo)) {
        $pdo = new PDO("mysql:host=localhost;dbname=sofcos_db;charset=utf8", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    // 4. RÉCUPÉRER LA COMMANDE
    $stmt = $pdo->prepare("SELECT * FROM commandes WHERE id = ? AND client_id = ?");
    $stmt->execute([$commande_id, $client_id]);
    $commande = $stmt->fetch();

    if (!$commande) {
        header('Location: mon-compte.php');
        exit();
    }

    // 5. RÉCUPÉRER LES DÉTAILS
    // Note : On utilise bien 'commandes_details'
    $sqlDetails = "SELECT d.*, p.nom, p.image 
                   FROM commandes_details d 
                   LEFT JOIN produits p ON d.produit_id = p.id 
                   WHERE d.commande_id = ?";
    $stmtDetails = $pdo->prepare($sqlDetails);
    $stmtDetails->execute([$commande_id]);
    $details = $stmtDetails->fetchAll();

} catch (PDOException $e) {
    die("Erreur technique : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi de Commande #<?= $commande_id ?> - SOFCOS</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Prata&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- DESIGN SYSTEM LUXE --- */
        :root {
            --green-luxe: #1A3C34;
            --gold-accent: #C5A059;
            --beige-bg: #fdfbf7;
            --text-main: #2c2c2c;
            --white: #ffffff;
            --border-light: #e0e0e0;
        }

        body {
            margin: 0; padding: 0;
            font-family: 'Montserrat', sans-serif;
            background-color: var(--beige-bg);
            color: var(--text-main);
        }

        .details-container { max-width: 1000px; margin: 40px auto; padding: 0 20px; }

        /* Header */
        .page-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid var(--gold-accent);
        }
        .page-header h1 { font-family: 'Prata', serif; font-size: 28px; margin: 0; color: var(--green-luxe); }
        .btn-back {
            text-decoration: none; color: var(--text-main); font-size: 14px;
            display: flex; align-items: center; gap: 8px; transition: 0.3s;
        }
        .btn-back:hover { color: var(--gold-accent); }

        /* --- BARRE DE PROGRESSION (TRACKING) --- */
        .track-container { margin-bottom: 50px; padding: 20px 0; }
        
        .progression-bar {
            display: flex; justify-content: space-between; position: relative; margin-top: 20px;
        }
        .progression-bar::before {
            content: ''; position: absolute; top: 15px; left: 0; width: 100%; height: 2px;
            background: #e0e0e0; z-index: 0;
        }
        
        .step {
            position: relative; z-index: 1; text-align: center; width: 25%;
        }
        
        /* Cercle de l'étape */
        .step-circle {
            width: 30px; height: 30px; background: #fff; border: 2px solid #e0e0e0;
            border-radius: 50%; margin: 0 auto 10px auto; display: flex;
            align-items: center; justify-content: center; transition: 0.4s;
        }
        .step-circle i { color: #ccc; font-size: 14px; }
        
        /* Texte de l'étape */
        .step-label {
            font-size: 12px; color: #999; text-transform: uppercase; letter-spacing: 1px; font-weight: 500;
        }

        /* ÉTAPE ACTIVE (Verte/Or) */
        .step.active .step-circle {
            background: var(--green-luxe); border-color: var(--green-luxe);
            box-shadow: 0 0 0 4px rgba(26, 60, 52, 0.1);
        }
        .step.active .step-circle i { color: #fff; }
        .step.active .step-label { color: var(--green-luxe); font-weight: 700; }
        
        /* Ligne de progression colorée */
        .progress-line-active {
            position: absolute; top: 15px; left: 0; height: 2px; background: var(--green-luxe);
            z-index: 0; transition: width 0.4s ease;
        }

        /* --- RESTE DU DESIGN --- */
        .details-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }
        .info-box { background: var(--white); padding: 30px; border: 1px solid rgba(0,0,0,0.05); margin-bottom: 20px; }
        .box-title {
            font-family: 'Prata', serif; font-size: 18px; margin-bottom: 20px;
            color: var(--green-luxe); border-bottom: 1px solid #eee; padding-bottom: 10px;
        }
        .products-table { width: 100%; border-collapse: collapse; }
        .products-table td { padding: 15px 0; border-bottom: 1px solid #eee; font-size: 14px; }
        .product-info { display: flex; align-items: center; gap: 15px; }
        .product-img { width: 60px; height: 60px; object-fit: cover; background: #f9f9f9; }
        .totals-row { display: flex; justify-content: space-between; margin-top: 10px; font-size: 14px; }
        .totals-row.final {
            margin-top: 20px; padding-top: 15px; border-top: 2px solid var(--gold-accent);
            font-size: 18px; font-weight: 600; color: var(--green-luxe);
        }
        
        /* Alerte Annulée */
        .alert-annule {
            background: #f8d7da; color: #721c24; padding: 15px; text-align: center;
            border-radius: 4px; border: 1px solid #f5c6cb; margin-bottom: 20px;
        }

        @media (max-width: 800px) {
            .details-grid { grid-template-columns: 1fr; }
            .step-label { font-size: 9px; }
        }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>
    
    <div class="details-container">
        
        <div class="page-header">
            <h1>Suivi Commande #<?= $commande_id ?></h1>
            <a href="mon-compte.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>

        <?php 
            // LOGIQUE DE SUIVI (Stepper)
            $st = strtolower(trim($commande['statut'])); // Nettoyage statut
            
            // Calcul du % de la barre verte
            $progressWidth = '0%';
            $step1 = $step2 = $step3 = $step4 = '';

            // Si Annulé
            if ($st == 'annule') {
                echo '<div class="alert-annule"><i class="fas fa-times-circle"></i> Cette commande a été annulée.</div>';
            } else {
                // Logique des étapes (Cumulative)
                // Étape 1 : Validée (Dès que la commande existe et n'est pas annulée)
                $step1 = 'active';
                $progressWidth = '15%'; 

                // Étape 2 : En préparation (Si confirmé, expedié ou livré)
                if (strpos($st, 'confirm') !== false || strpos($st, 'expedi') !== false || strpos($st, 'livre') !== false) {
                    $step2 = 'active';
                    $progressWidth = '50%';
                }

                // Étape 3 : Expédiée / En route (Si expedié ou livré)
                if (strpos($st, 'expedi') !== false || strpos($st, 'livre') !== false) {
                    $step3 = 'active';
                    $progressWidth = '85%';
                }

                // Étape 4 : Livrée
                if (strpos($st, 'livre') !== false) {
                    $step4 = 'active';
                    $progressWidth = '100%';
                }
        ?>

        <div class="track-container">
            <div class="progression-bar">
                <div class="progress-line-active" style="width: <?= $progressWidth ?>;"></div>

                <div class="step <?= $step1 ?>">
                    <div class="step-circle"><i class="fas fa-check"></i></div>
                    <div class="step-label">Validée</div>
                </div>

                <div class="step <?= $step2 ?>">
                    <div class="step-circle"><i class="fas fa-box-open"></i></div>
                    <div class="step-label">Préparation</div>
                </div>

                <div class="step <?= $step3 ?>">
                    <div class="step-circle"><i class="fas fa-truck"></i></div>
                    <div class="step-label">En route</div>
                </div>

                <div class="step <?= $step4 ?>">
                    <div class="step-circle"><i class="fas fa-home"></i></div>
                    <div class="step-label">Livrée</div>
                </div>
            </div>
        </div>
        <?php } // Fin du else (si pas annulé) ?>

        <div class="details-grid">
            
            <div class="col-products">
                <div class="info-box">
                    <h3 class="box-title">Articles</h3>
                    <table class="products-table">
                        <?php foreach($details as $item): ?>
                            <tr>
                                <td>
                                    <div class="product-info">
                                        <?php  $img = !empty($p['image']) ? 'uploads/produits/' . $p['image'] : 'images/default.jpg';?>
                                         
                                        <img src="<?= htmlspecialchars($img) ?>" class="product-img">
                                        <div>
                                            <strong><?= htmlspecialchars($item['nom']) ?></strong>
                                        </div>
                                    </div>
                                </td>
                                <td align="right">x<?= $item['quantite'] ?></td>
                                <td align="right"><?= number_format($item['prix_unitaire'] * $item['quantite'], 2) ?> DH</td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>

            <div class="col-infos">
                <div class="info-box">
                    <h3 class="box-title">Livraison</h3>
                    <div style="font-size:14px; line-height:1.6;">
                        <strong><?= htmlspecialchars($commande['nom_client']) ?></strong><br>
                        <?= htmlspecialchars($commande['adresse']) ?><br>
                        <?= htmlspecialchars($commande['ville']) ?><br>
                        Tél : <?= htmlspecialchars($commande['telephone']) ?>
                    </div>
                </div>

                <div class="info-box">
                    <h3 class="box-title">Total</h3>
                    <?php 
                         $subTotal = 0;
                         foreach($details as $d) { $subTotal += $d['prix_unitaire'] * $d['quantite']; }
                         $livraison = ($commande['total'] - $subTotal);
                         if($livraison < 0) $livraison = 0;
                    ?>
                    <div class="totals-row">
                        <span>Sous-total</span> <span><?= number_format($subTotal, 2) ?> DH</span>
                    </div>
                    <div class="totals-row">
                        <span>Livraison</span> <span><?= ($livraison==0)?'Offerte':number_format($livraison,2).' DH' ?></span>
                    </div>
                    <div class="totals-row final">
                        <span>Total</span> <span><?= number_format($commande['total'], 2) ?> DH</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>