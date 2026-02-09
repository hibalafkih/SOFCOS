<?php
// suivi.php
session_start();
require_once 'config.php';

if(!isset($_SESSION['client_id'])) { header('Location: connexion.php'); exit(); }
if (!isset($_GET['id']) || empty($_GET['id'])) { header('Location: mon-compte.php'); exit(); }

$commande_id = (int)$_GET['id'];
$client_id = $_SESSION['client_id'];

try {
    if(!isset($pdo)) {
        $pdo = new PDO("mysql:host=localhost;dbname=sofcos_db;charset=utf8", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    $stmt = $pdo->prepare("SELECT * FROM commandes WHERE id = ? AND client_id = ?");
    $stmt->execute([$commande_id, $client_id]);
    $commande = $stmt->fetch();

    if (!$commande) { header('Location: mon-compte.php'); exit(); }

    $stmtDetails = $pdo->prepare("SELECT d.*, p.nom, p.image FROM commandes_details d LEFT JOIN produits p ON d.produit_id = p.id WHERE d.commande_id = ?");
    $stmtDetails->execute([$commande_id]);
    $details = $stmtDetails->fetchAll();

} catch (PDOException $e) { die("Erreur : " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi #<?= $commande_id ?> - SOFCOS</title>
    <link href="https://fonts.googleapis.com/css2?family=Prata&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #1A3C34; --gold: #C5A059; --bg-light: #F9F7F2;
            --white: #ffffff; --text: #2c2c2c; --shadow: 0 10px 30px rgba(26, 60, 52, 0.08);
        }
        body { font-family: 'Montserrat', sans-serif; background: var(--bg-light); color: var(--text); margin: 0; }
        
        .track-wrapper { max-width: 1000px; margin: 50px auto; padding: 0 20px; }
        
        /* HEADER */
        .track-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .track-header h1 { font-family: 'Prata', serif; font-size: 32px; color: var(--primary); margin: 0; }
        .btn-back { display: inline-flex; align-items: center; gap: 10px; padding: 10px 20px; background: white; color: var(--primary); text-decoration: none; border-radius: 30px; font-weight: 600; font-size: 13px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); transition: 0.3s; }
        .btn-back:hover { background: var(--primary); color: white; transform: translateX(-5px); }

        /* TRACKER */
        .timeline-card { background: white; padding: 40px 20px; border-radius: 12px; box-shadow: var(--shadow); margin-bottom: 30px; }
        .progress-track { position: relative; display: flex; justify-content: space-between; margin-top: 20px; }
        .progress-track::before { content: ''; position: absolute; top: 15px; left: 0; width: 100%; height: 3px; background: #eee; z-index: 0; border-radius: 10px; }
        .progress-bar-fill { position: absolute; top: 15px; left: 0; height: 3px; background: var(--gold); z-index: 0; transition: width 1s ease; box-shadow: 0 0 10px rgba(197, 160, 89, 0.5); }
        .step { position: relative; z-index: 1; text-align: center; width: 25%; }
        .step-icon { width: 35px; height: 35px; background: white; border: 2px solid #ddd; border-radius: 50%; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center; font-size: 14px; color: #ccc; transition: 0.4s; }
        .step-text { font-size: 11px; text-transform: uppercase; color: #999; font-weight: 600; letter-spacing: 1px; }
        .step.active .step-icon { border-color: var(--gold); background: var(--primary); color: var(--gold); transform: scale(1.2); box-shadow: 0 0 0 5px rgba(197, 160, 89, 0.2); }
        .step.active .step-text { color: var(--primary); }

        /* LAYOUT GRID */
        .content-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }
        .info-card { background: white; padding: 30px; border-radius: 12px; box-shadow: var(--shadow); height: fit-content; }
        .card-title { font-family: 'Prata', serif; font-size: 18px; color: var(--primary); border-bottom: 1px solid #f0f0f0; padding-bottom: 15px; margin-bottom: 20px; }

        /* --- NOUVEAU DESIGN CONTENU DU COLIS --- */
        .products-card { background: var(--white); border-radius: 12px; box-shadow: var(--shadow); overflow: hidden; }
        .products-header { background: #fdfbf7; padding: 20px 30px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 10px; }
        .products-header h3 { margin: 0; font-family: 'Prata', serif; font-size: 18px; color: var(--primary); }
        .products-header i { color: var(--gold); }
        
        .product-list { padding: 0 30px; }
        .product-item { display: flex; align-items: center; justify-content: space-between; padding: 25px 0; border-bottom: 1px dashed #eee; }
        .product-item:last-child { border-bottom: none; }
        
        .prod-main { display: flex; align-items: center; gap: 20px; }
        .prod-img-container { position: relative; width: 70px; height: 70px; border-radius: 8px; overflow: hidden; border: 1px solid #f0f0f0; box-shadow: 0 2px 5px rgba(0,0,0,0.03); }
        .prod-img { width: 100%; height: 100%; object-fit: cover; }
        .qty-badge { position: absolute; top: 0; left: 0; background: var(--primary); color: white; font-size: 10px; font-weight: 700; padding: 2px 6px; border-bottom-right-radius: 6px; }
        
        .prod-details h4 { margin: 0 0 5px 0; font-size: 15px; font-weight: 600; color: var(--text); }
        .prod-meta { font-size: 13px; color: #888; display: flex; align-items: center; gap: 10px; }
        .prod-unit-price { background: #f5f5f5; padding: 2px 8px; border-radius: 4px; font-size: 11px; }
        .prod-total-price { font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 16px; color: var(--primary); white-space: nowrap; }

        /* RÉSUMÉ FINANCIER INTÉGRÉ */
        .summary-section { background: #fafafa; padding: 25px 30px; border-top: 1px solid #eee; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14px; color: #666; }
        .summary-row.total { margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd; color: var(--primary); font-size: 18px; font-weight: 700; font-family: 'Prata', serif; }

        @media (max-width: 800px) { .content-grid { grid-template-columns: 1fr; } .step-text { display: none; } }
        @media (max-width: 600px) { .product-item { flex-direction: column; align-items: flex-start; gap: 15px; } .prod-total-price { align-self: flex-end; } .product-list { padding: 0 20px; } }
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<div class="track-wrapper">
    
    <div class="track-header">
        <h1>Commande #<?= $commande_id ?></h1>
        <a href="mon-compte.php" class="btn-back"><i class="fas fa-arrow-left"></i> Retour</a>
    </div>

    <?php 
        $st = strtolower(trim($commande['statut']));
        $width = '10%'; $s1=$s2=$s3=$s4='';
        
        if(strpos($st, 'annul') !== false) {
            // Annulé
        } else {
            $s1 = 'active'; $width = '15%';
            if(strpos($st, 'confirm')!==false || strpos($st, 'expedi')!==false || strpos($st, 'livre')!==false){ $s2 = 'active'; $width = '50%'; }
            if(strpos($st, 'expedi')!==false || strpos($st, 'livre')!==false){ $s3 = 'active'; $width = '80%'; }
            if(strpos($st, 'livre')!==false){ $s4 = 'active'; $width = '100%'; }
        }
    ?>

    <?php if(strpos($st, 'annul') !== false): ?>
        <div style="background:#ffebee; color:#c62828; padding:20px; border-radius:8px; text-align:center; margin-bottom:30px; border:1px solid #ef9a9a;">
            <i class="fas fa-times-circle" style="font-size:20px; margin-bottom:10px; display:block;"></i> 
            <strong>Commande Annulée</strong><br>
            Cette commande a été annulée. Si vous avez des questions, contactez le service client.
        </div>
    <?php else: ?>
        <div class="timeline-card">
            <div class="progress-track">
                <div class="progress-bar-fill" style="width: <?= $width ?>"></div>
                <div class="step <?= $s1 ?>">
                    <div class="step-icon"><i class="fas fa-check"></i></div>
                    <div class="step-text">Reçue</div>
                </div>
                <div class="step <?= $s2 ?>">
                    <div class="step-icon"><i class="fas fa-box-open"></i></div>
                    <div class="step-text">Préparation</div>
                </div>
                <div class="step <?= $s3 ?>">
                    <div class="step-icon"><i class="fas fa-shipping-fast"></i></div>
                    <div class="step-text">En route</div>
                </div>
                <div class="step <?= $s4 ?>">
                    <div class="step-icon"><i class="fas fa-home"></i></div>
                    <div class="step-text">Livrée</div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="content-grid">
        
        <div class="products-card">
            <div class="products-header">
                <i class="fas fa-shopping-bag"></i>
                <h3>Contenu de votre colis</h3>
            </div>

            <div class="product-list">
                <?php 
                    $subTotal = 0;
                    foreach($details as $d): 
                        $total_ligne = $d['prix_unitaire'] * $d['quantite'];
                        $subTotal += $total_ligne;
                        
                        // Gestion Image Robuste
                        $img = 'assets/img/default.jpg';
                        if(!empty($d['image'])){
                            if(file_exists('admin/'.$d['image'])) $img = 'admin/'.$d['image'];
                            elseif(file_exists('admin/uploads/produits/'.$d['image'])) $img = 'admin/uploads/produits/'.$d['image'];
                            elseif(file_exists('uploads/produits/'.$d['image'])) $img = 'uploads/produits/'.$d['image'];
                            elseif(file_exists($d['image'])) $img = $d['image'];
                        }
                ?>
                <div class="product-item">
                    <div class="prod-main">
                        <div class="prod-img-container">
                            <img src="<?= htmlspecialchars($img) ?>" class="prod-img" alt="Produit">
                            <div class="qty-badge">x<?= $d['quantite'] ?></div>
                        </div>
                        <div class="prod-details">
                            <h4><?= htmlspecialchars($d['nom']) ?></h4>
                            <div class="prod-meta">
                                <span>Prix unitaire :</span>
                                <span class="prod-unit-price"><?= number_format($d['prix_unitaire'], 2) ?> DH</span>
                            </div>
                        </div>
                    </div>
                    <div class="prod-total-price">
                        <?= number_format($total_ligne, 2) ?> DH
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="summary-section">
                <?php 
                    $livraison = ($commande['total'] - $subTotal);
                    if($livraison < 1) $livraison = 0; 
                ?>
                
                <div class="summary-row">
                    <span>Sous-total articles</span>
                    <span><?= number_format($subTotal, 2) ?> DH</span>
                </div>
                
                <div class="summary-row">
                    <span>Frais de port</span>
                    <?php if($livraison == 0): ?>
                        <span style="color:#27ae60; font-weight:600;">Offerts</span>
                    <?php else: ?>
                        <span><?= number_format($livraison, 2) ?> DH</span>
                    <?php endif; ?>
                </div>

                <div class="summary-row total">
                    <span>Total Payé</span>
                    <span><?= number_format($commande['total'], 2) ?> DH</span>
                </div>
                
                <div style="text-align:right; font-size:11px; color:#999; margin-top:5px;">
                    <i class="fas fa-money-bill-wave"></i> Paiement à la livraison
                </div>
            </div>
        </div>

        <div class="info-card">
            <h3 class="card-title">Adresse de livraison</h3>
            <div style="font-size:14px; line-height:1.8;">
                <strong style="color:var(--primary); display:block; margin-bottom:5px; font-size:16px;">
                    <?= htmlspecialchars($commande['nom_client']) ?>
                </strong>
                <i class="fas fa-map-marker-alt" style="color:var(--gold); margin-right:5px;"></i>
                <?= htmlspecialchars($commande['adresse']) ?><br>
                <span style="margin-left:20px;"><?= htmlspecialchars($commande['ville']) ?></span>
                
                <div style="margin-top:15px; padding-top:15px; border-top:1px dashed #eee;">
                    <i class="fas fa-phone" style="color:var(--gold); margin-right:5px;"></i>
                    <?= htmlspecialchars($commande['telephone']) ?><br>
                    
                    <i class="fas fa-envelope" style="color:var(--gold); margin-right:5px;"></i>
                    <?= htmlspecialchars($commande['email_client']) ?>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>