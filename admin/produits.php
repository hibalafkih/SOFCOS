<?php
session_start();
require_once '../config.php';

// ========== SÉCURITÉ ==========
if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$conn = $pdo;

// ========== TRAITEMENT (SUPPRESSION) ==========
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->prepare("DELETE FROM produits WHERE id = ?")->execute([$id]);
    header("Location: produits.php?msg=deleted");
    exit();
}

// ========== RÉCUPÉRATION DES DONNÉES ==========
// On récupère les produits avec le nom de la catégorie
$sql = "SELECT p.*, c.nom as categorie_nom 
        FROM produits p 
        LEFT JOIN categories c ON p.categorie_id = c.id 
        ORDER BY p.id DESC";
$produits = $conn->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produits - SOFCOS Admin</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            /* --- THÈME NATURE (Même que Dashboard) --- */
            --sidebar-bg: #1A3C34;
            --sidebar-hover: #265c4f;
            --accent: #10b981;       /* Vert vif pour actions */
            --gold: #C5A059;         /* Doré pour détails */
            --bg-body: #f6fcf8;
            --white: #ffffff;
            --text-dark: #1f2937;
            --text-grey: #6b7280;
            --danger: #ef4444;
            --success: #059669;
            --warning: #f59e0b;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Open Sans', sans-serif; background: var(--bg-body); color: var(--text-dark); display: flex; min-height: 100vh; }
        a { text-decoration: none; color: inherit; }
        h1, h2, h3 { font-family: 'Poppins', sans-serif; }

        /* --- SIDEBAR --- */
        .sidebar { width: 260px; background: var(--sidebar-bg); color: var(--white); display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; z-index: 100; box-shadow: 4px 0 10px rgba(0,0,0,0.1); }
        
        .brand { 
            height: 80px; display: flex; align-items: center; padding: 0 25px; 
            font-size: 22px; font-weight: 600; letter-spacing: 1px;
            border-bottom: 1px solid rgba(255,255,255,0.1); color: var(--white);
        }
        .brand i { color: var(--accent); margin-right: 12px; font-size: 24px; }

        .nav-links { list-style: none; padding: 20px 15px; flex: 1; }
        .nav-links li { margin-bottom: 8px; }
        .nav-links a { display: flex; align-items: center; padding: 12px 18px; color: #d1fae5; border-radius: 8px; transition: 0.3s; font-size: 14px; font-weight: 500; }
        .nav-links a:hover, .nav-links a.active { background: rgba(255,255,255,0.1); color: var(--accent); border-left: 4px solid var(--accent); }
        .nav-links i { width: 25px; margin-right: 10px; font-size: 16px; }

        .user-profile { padding: 20px; background: rgba(0,0,0,0.2); display: flex; align-items: center; justify-content: space-between; border-top: 1px solid rgba(255,255,255,0.1); }
        .user-info div { font-size: 14px; font-weight: 600; color: white; }
        .user-info span { font-size: 11px; color: #a7f3d0; display: block; }

        /* --- CONTENU PRINCIPAL --- */
        .main { margin-left: 260px; flex: 1; padding: 30px; width: calc(100% - 260px); }

        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-title { font-size: 26px; font-weight: 600; color: var(--sidebar-bg); }
        
        /* Bouton Ajouter */
        .btn-add { 
            background: var(--sidebar-bg); color: white; padding: 12px 25px; border-radius: 50px; 
            font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 10px; 
            box-shadow: 0 4px 10px rgba(26, 60, 52, 0.3); transition: 0.3s; 
        }
        .btn-add:hover { background: var(--accent); transform: translateY(-2px); }

        /* TABLEAU STYLISÉ */
        .table-container { background: var(--white); border-radius: 16px; padding: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        
        table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        th { text-align: left; padding: 15px; color: var(--text-grey); font-size: 12px; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; }
        td { padding: 15px; background: #fafafa; border-top: 1px solid #eee; border-bottom: 1px solid #eee; vertical-align: middle; }
        
        /* Arrondis du tableau */
        tr td:first-child { border-left: 1px solid #eee; border-top-left-radius: 10px; border-bottom-left-radius: 10px; }
        tr td:last-child { border-right: 1px solid #eee; border-top-right-radius: 10px; border-bottom-right-radius: 10px; }

        /* Image Produit */
        .product-img { width: 50px; height: 50px; border-radius: 8px; object-fit: cover; border: 1px solid #eee; }
        
        /* Badges Stock */
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-block; }
        .badge-instock { background: #d1fae5; color: #065f46; }
        .badge-low { background: #fef3c7; color: #92400e; }
        .badge-out { background: #fee2e2; color: #991b1b; }
        
        /* Actions */
        .action-btn { width: 32px; height: 32px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; transition: 0.2s; margin-right: 5px; }
        .btn-edit { background: #e0f2fe; color: #0284c7; }
        .btn-edit:hover { background: #0284c7; color: white; }
        .btn-delete { background: #fee2e2; color: #dc2626; }
        .btn-delete:hover { background: #dc2626; color: white; }

        /* Prix */
        .price-tag { font-weight: 700; color: var(--sidebar-bg); }

    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="main">
        
        <header class="header">
            <div>
                <h1 class="page-title">Gestion des Produits</h1>
                <p style="color:var(--text-grey); font-size:14px; margin-top:5px;">Gérez votre catalogue cosmétique.</p>
            </div>
            <a href="produits_ajout.php" class="btn-add">
                <i class="fas fa-plus"></i> Ajouter un Produit
            </a>
        </header>

        <div class="table-container">
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                <div style="background:#dcfce7; color:#166534; padding:15px; border-radius:8px; margin-bottom:20px;">
                    <i class="fas fa-check-circle"></i> Produit supprimé avec succès.
                </div>
            <?php endif; ?>

            <table cellpadding="0" cellspacing="0">
                <thead>
                    <tr>
                        <th width="80">Image</th>
                        <th>Nom du produit</th>
                        <th>Catégorie</th>
                        <th>Prix</th>
                        <th>Stock</th>
                        <th>État</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($produits) > 0): ?>
                        <?php foreach($produits as $p): ?>
                        <tr>
                            <td>
                                <?php 
                                    // Gestion chemin image (si stockée dans uploads/produits/ ou images/)
                                    $img = !empty($p['image']) ? '../uploads/produits/'.$p['image'] : '../images/default.jpg';
                                    if(!file_exists($img)) $img = '../images/logo.png'; // Fallback
                                ?>
                                <img src="<?= $img ?>" class="product-img" alt="Produit">
                            </td>
                            <td>
                                <div style="font-weight:600; color:var(--text-dark);"><?= htmlspecialchars($p['nom']) ?></div>
                                <div style="font-size:12px; color:var(--text-grey);">Réf: #<?= $p['id'] ?></div>
                            </td>
                            <td>
                                <span style="font-size:13px; color:var(--sidebar-bg); background:#e6fffa; padding:4px 8px; border-radius:4px;">
                                    <?= htmlspecialchars($p['categorie_nom'] ?? 'Non classé') ?>
                                </span>
                            </td>
                            <td class="price-tag"><?= number_format($p['prix'], 2) ?> DH</td>
                            <td>
                                <?php
                                    if($p['stock'] == 0) {
                                        echo '<span class="badge badge-out">Rupture</span>';
                                    } elseif($p['stock'] < 5) {
                                        echo '<span class="badge badge-low">Faible ('.$p['stock'].')</span>';
                                    } else {
                                        echo '<span class="badge badge-instock">En Stock ('.$p['stock'].')</span>';
                                    }
                                ?>
                            </td>
                            <td>
                                <?php if($p['actif']): ?>
                                    <i class="fas fa-eye" style="color:var(--success);" title="Visible sur le site"></i>
                                <?php else: ?>
                                    <i class="fas fa-eye-slash" style="color:var(--text-grey);" title="Caché"></i>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:right;">
                                <a href="produits_ajout.php?id=<?= $p['id'] ?>" class="action-btn btn-edit" title="Modifier">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <a href="?delete=<?= $p['id'] ?>" class="action-btn btn-delete" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce produit ?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding:40px; color:var(--text-grey);">
                                <i class="fas fa-box-open" style="font-size:40px; margin-bottom:10px; display:block; opacity:0.3;"></i>
                                Aucun produit trouvé. Ajoutez votre premier produit !
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>

</body>
</html>