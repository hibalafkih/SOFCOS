<?php
session_start();
require_once '../config.php';

// ========== SÉCURITÉ ==========
if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$conn = $pdo;
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = $id > 0 ? 'modifier' : 'ajouter';

// Variables par défaut
$produit = [
    'nom' => '', 'marque' => '', 'description' => '', 'prix' => '', 'prix_promo' => '', 'stock' => '', 
    'categorie_id' => '', 'image' => '', 'actif' => 1
];

// Si MODIFICATION : on récupère les infos
if ($action == 'modifier') {
    $stmt = $conn->prepare("SELECT * FROM produits WHERE id = ?");
    $stmt->execute([$id]);
    $produit = $stmt->fetch();
    if(!$produit) { header("Location: produits.php"); exit(); }
}

// Récupérer les catégories
$categories = $conn->query("SELECT * FROM categories ORDER BY nom ASC")->fetchAll();

// --- AJOUT : Récupérer les marques ---
$marques_list = $conn->query("SELECT * FROM marques ORDER BY nom ASC")->fetchAll();

// ========== TRAITEMENT DU FORMULAIRE ==========
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nom = trim($_POST['nom']);
// On récupère l'ID, ou NULL si rien n'est sélectionné
$marque_id = !empty($_POST['marque_id']) ? (int)$_POST['marque_id'] : null; 
$description = trim($_POST['description']);
    $prix = (float)$_POST['prix'];
    $prix_promo = !empty($_POST['prix_promo']) ? (float)$_POST['prix_promo'] : null;
    $stock = (int)$_POST['stock'];
    $categorie_id = (int)$_POST['categorie_id'];
    $actif = isset($_POST['actif']) ? 1 : 0;

    // Gestion de l'image
    $imageName = $produit['image']; 

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $uploadDir = '../uploads/produits/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($ext, $allowed)) {
            $newFileName = uniqid('prod_') . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newFileName)) {
                // Si modif, supprimer l'ancienne image pour nettoyer
                if(!empty($imageName) && file_exists($uploadDir.$imageName) && $imageName != 'default.jpg') {
                    unlink($uploadDir.$imageName);
                }
                $imageName = $newFileName;
            } else {
                $error = "Erreur lors de l'upload de l'image.";
            }
        } else {
            $error = "Format d'image non valide (JPG, PNG, WEBP uniquement).";
        }
    }

    if (empty($error)) {
        $id_pour_galerie = 0; // On va stocker l'ID ici

        if ($action == 'ajouter') {
           // Remplacez 'marque' par 'marque_id' dans la liste et les valeurs
$stmt = $conn->prepare("INSERT INTO produits (nom, marque_id, description, prix, prix_promo, stock, categorie_id, image, actif, date_ajout) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
$stmt->execute([$nom, $marque_id, $description, $prix, $prix_promo, $stock, $categorie_id, $imageName, $actif]);
            
            // Récupérer l'ID du nouveau produit créé
            $id_pour_galerie = $conn->lastInsertId();
            
        } else {
            // Remplacez 'marque=?' par 'marque_id=?'
$stmt = $conn->prepare("UPDATE produits SET nom=?, marque_id=?, description=?, prix=?, prix_promo=?, stock=?, categorie_id=?, image=?, actif=? WHERE id=?");
$stmt->execute([$nom, $marque_id, $description, $prix, $prix_promo, $stock, $categorie_id, $imageName, $actif, $id]);
            
            // Message de succès
            $success = "Le produit a été mis à jour avec succès.";
            
            // L'ID est déjà connu
            $id_pour_galerie = $id;

            // Rafraichir les données pour l'affichage
            $produit = array_merge($produit, $_POST);
            $produit['image'] = $imageName;
            $produit['actif'] = $actif;
        }

        // ==========================================
        // TRAITEMENT DE LA GALERIE (Code Ajouté)
        // ==========================================
        if (isset($_FILES['galerie']) && $id_pour_galerie > 0) {
            $total_files = count($_FILES['galerie']['name']);
            
            // Créer le dossier s'il n'existe pas
            $galerieDir = '../uploads/produits/';
            if (!is_dir($galerieDir)) mkdir($galerieDir, 0777, true);

            for($i = 0; $i < $total_files; $i++) {
                if($_FILES['galerie']['error'][$i] == 0) {
                    $ext = strtolower(pathinfo($_FILES['galerie']['name'][$i], PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                    
                    if(in_array($ext, $allowed)) {
                        // Nom unique : id_produit_index_timestamp.jpg
                        $newName = $id_pour_galerie . '_gal_' . $i . '_' . time() . '.' . $ext;
                        
                        if(move_uploaded_file($_FILES['galerie']['tmp_name'][$i], $galerieDir . $newName)) {
                            // Insertion dans la table produits_images
                            $stmt_img = $conn->prepare("INSERT INTO produits_images (produit_id, chemin_image) VALUES (?, ?)");
                            $stmt_img->execute([$id_pour_galerie, $newName]);
                        }
                    }
                }
            }
        }
        // ==========================================

        // Redirection uniquement si c'était un ajout
        if ($action == 'ajouter') {
            header("Location: produits.php?msg=added");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= ucfirst($action) ?> Produit - SOFCOS Admin</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --sidebar-bg: #1A3C34;
            --sidebar-hover: #265c4f;
            --accent: #10b981;
            --gold: #C5A059;
            --bg-body: #f6fcf8;
            --white: #ffffff;
            --text-dark: #1f2937;
            --text-grey: #6b7280;
            --border: #e5e7eb;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Open Sans', sans-serif; background: var(--bg-body); color: var(--text-dark); display: flex; min-height: 100vh; }
        a { text-decoration: none; color: inherit; }
        h1, h2, h3 { font-family: 'Poppins', sans-serif; }

        /* SIDEBAR (Identique) */
        .sidebar { width: 260px; background: var(--sidebar-bg); color: var(--white); display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; z-index: 100; box-shadow: 4px 0 10px rgba(0,0,0,0.1); }
        .brand { height: 80px; display: flex; align-items: center; padding: 0 25px; font-size: 22px; font-weight: 600; letter-spacing: 1px; border-bottom: 1px solid rgba(255,255,255,0.1); color: white; }
        .brand i { color: var(--accent); margin-right: 12px; font-size: 24px; }
        .nav-links { list-style: none; padding: 20px 15px; flex: 1; }
        .nav-links li { margin-bottom: 8px; }
        .nav-links a { display: flex; align-items: center; padding: 12px 18px; color: #d1fae5; border-radius: 8px; transition: 0.3s; font-size: 14px; font-weight: 500; }
        .nav-links a:hover, .nav-links a.active { background: rgba(255,255,255,0.1); color: var(--accent); border-left: 4px solid var(--accent); }
        .nav-links i { width: 25px; margin-right: 10px; font-size: 16px; }
        .user-profile { padding: 20px; background: rgba(0,0,0,0.2); border-top: 1px solid rgba(255,255,255,0.1); display:flex; justify-content:space-between; align-items:center; }
        .user-info div { font-size: 14px; font-weight: 600; color: white; }
        .user-info span { font-size: 11px; color: #a7f3d0; display: block; }

        /* MAIN CONTENT */
        .main { margin-left: 260px; flex: 1; padding: 30px; width: calc(100% - 260px); }

        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-title { font-size: 26px; font-weight: 600; color: var(--sidebar-bg); }
        .btn-back { background: white; border: 1px solid #ddd; padding: 10px 20px; border-radius: 50px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 8px; transition: 0.2s; color: var(--text-dark); }
        .btn-back:hover { background: #eee; }

        /* FORMULAIRE CONTAINER */
        .form-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; max-width: 1200px; margin: 0 auto; }
        @media(max-width: 1000px) { .form-grid { grid-template-columns: 1fr; } }

        .card { background: var(--white); border-radius: 12px; padding: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border: 1px solid var(--border); margin-bottom: 25px; }
        .card-title { font-size: 16px; font-weight: 600; color: var(--sidebar-bg); border-bottom: 1px solid #f3f4f6; padding-bottom: 15px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }

        /* INPUTS */
        .form-group { margin-bottom: 20px; position: relative; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 13px; color: var(--text-dark); }
        .form-control { width: 100%; padding: 12px 15px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; transition: 0.3s; background: #fff; font-size: 14px; }
        .form-control:focus { border-color: var(--sidebar-bg); outline: none; box-shadow: 0 0 0 3px rgba(26, 60, 52, 0.1); }
        
        /* PRIX AVEC DISCOUNT */
        .price-wrapper { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .discount-badge { position: absolute; right: 0; top: 0; background: var(--gold); color: white; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; display: none; }

        /* DRAG & DROP IMAGE */
        .upload-area { 
            border: 2px dashed #cbd5e1; border-radius: 12px; padding: 30px; text-align: center; 
            cursor: pointer; transition: 0.3s; background: #fafafa; position: relative;
        }
        .upload-area:hover, .upload-area.dragover { border-color: var(--accent); background: #f0fdf4; }
        
        .preview-box { margin-top: 15px; display: none; position: relative; }
        .preview-img { width: 100%; max-height: 250px; object-fit: contain; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        
        .upload-icon { font-size: 40px; color: var(--sidebar-bg); opacity: 0.5; margin-bottom: 10px; }
        .upload-text { font-size: 13px; color: var(--text-grey); }

        /* SWITCH TOGGLE (MODERNE) */
        .switch-container { display: flex; align-items: center; justify-content: space-between; background: #f9fafb; padding: 15px; border-radius: 8px; border: 1px solid var(--border); }
        .switch { position: relative; display: inline-block; width: 46px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--accent); }
        input:checked + .slider:before { transform: translateX(22px); }

        /* BOUTON SAUVEGARDER */
        .sticky-actions { position: sticky; bottom: 20px; z-index: 10; margin-top: 20px; }
        .btn-submit { width: 100%; padding: 16px; background: var(--sidebar-bg); color: white; border: none; border-radius: 12px; font-size: 16px; font-weight: 600; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 10px; box-shadow: 0 10px 20px rgba(26, 60, 52, 0.2); }
        .btn-submit:hover { background: #143029; transform: translateY(-2px); }

    </style>
</head>
<body>

       <?php include 'sidebar.php'; ?>


    <main class="main">
        <header class="header">
            <h1 class="page-title"><?= $action == 'modifier' ? 'Modifier Produit' : 'Nouveau Produit' ?></h1>
            <a href="produits.php" class="btn-back"><i class="fas fa-arrow-left"></i> Annuler</a>
        </header>

        <?php if($success): ?>
            <div style="background:#dcfce7; color:#166534; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #bbf7d0;">
                <i class="fas fa-check-circle"></i> <?= $success ?>
            </div>
        <?php endif; ?>
        <?php if($error): ?>
            <div style="background:#fee2e2; color:#991b1b; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #fecaca;">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-grid">
                
                <div class="left-col">
                    
                    <div class="card">
                        <div class="card-title"><i class="fas fa-info-circle"></i> Informations de base</div>
                        
                        <div class="form-group">
                            <label>Nom du produit</label>
                            <input type="text" name="nom" class="form-control" required value="<?= htmlspecialchars($produit['nom']) ?>" placeholder="Ex: Crème Visage Hydratante">
                        </div>
                        <div class="form-group">
    <label>Marque / Fabriquant</label>
    <select name="marque_id" class="form-control">
        <option value="">-- Sélectionner une marque --</option>
        <?php foreach($marques_list as $m): ?>
            <option value="<?= $m['id'] ?>" 
                <?= (isset($produit['marque_id']) && $produit['marque_id'] == $m['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($m['nom']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
<div style="text-align:right; margin-top:-15px; margin-bottom:15px;">
    <a href="marques.php" target="_blank" style="font-size:12px; color:#10b981;">
        <i class="fas fa-plus"></i> Créer une nouvelle marque
    </a>
</div>
<?php if(!empty($produit['marque'])): ?>
    <div style="color: #888; text-transform: uppercase; font-size: 14px; font-weight: 600; margin-bottom: 5px;">
        <?= htmlspecialchars($produit['marque']) ?>
    </div>
<?php endif; ?>

<h1><?= htmlspecialchars($produit['nom']) ?></h1>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="6" placeholder="Décrivez le produit, ses bienfaits, ses ingrédients..."><?= htmlspecialchars($produit['description']) ?></textarea>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-title"><i class="fas fa-tag"></i> Prix et Inventaire</div>
                        
                        <div class="price-wrapper">
                            <div class="form-group">
                                <label>Prix de vente (DH)</label>
                                <input type="number" step="0.01" name="prix" id="prix" class="form-control" required value="<?= htmlspecialchars($produit['prix']) ?>" oninput="calculateDiscount()">
                            </div>
                            <div class="form-group">
                                <label>Prix Promo (Optionnel)</label>
                                <span id="discountBadge" class="discount-badge">-0%</span>
                                <input type="number" step="0.01" name="prix_promo" id="promo" class="form-control" value="<?= htmlspecialchars($produit['prix_promo']) ?>" placeholder="Ex: 150.00" oninput="calculateDiscount()">
                            </div>
                        </div>

                        <div class="price-wrapper" style="margin-top:15px;">
                            <div class="form-group">
                                <label>Stock disponible</label>
                                <input type="number" name="stock" class="form-control" required value="<?= htmlspecialchars($produit['stock']) ?>" placeholder="Qté">
                            </div>
                            <div class="form-group">
                                <label>Catégorie</label>
                                <select name="categorie_id" class="form-control" required>
                                    <option value="">-- Choisir --</option>
                                    <?php foreach($categories as $c): ?>
                                        <option value="<?= $c['id'] ?>" <?= $produit['categorie_id'] == $c['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c['nom']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="right-col">
                    
                    <div class="card">
                        <div class="card-title"><i class="fas fa-image"></i> Image du produit</div>
                        
                        <div class="upload-area" id="drop-area">
                            <i class="fas fa-cloud-upload-alt upload-icon"></i>
                            <div class="upload-text">Glissez une image ici<br>ou cliquez pour parcourir</div>
                            <input type="file" name="image" id="fileElem" accept="image/*" style="display:none" onchange="handleFiles(this.files)">
                        </div>

                        <div class="preview-box" id="preview-box">
                            <?php 
                                $imgSrc = '';
                                $display = 'none';
                                if(!empty($produit['image']) && file_exists('../uploads/produits/' . $produit['image'])) {
                                    $imgSrc = '../uploads/produits/' . $produit['image'];
                                    $display = 'block';
                                }
                            ?>
                            <img src="<?= $imgSrc ?>" id="gallery-img" class="preview-img" style="display:<?= $display ?>;">
                            <div style="text-align:center; margin-top:10px; font-size:12px; color:#888;">Aperçu</div>
                        </div>
                        <div class="form-group">
    <label>Galerie photos (Optionnel)</label>
    <input type="file" name="galerie[]" multiple class="form-control" accept="image/*">
    <small>Maintenez Ctrl pour sélectionner plusieurs photos</small>
</div>
                    </div>

                    <div class="card">
                        <div class="card-title"><i class="fas fa-toggle-on"></i> Visibilité</div>
                        
                        <div class="switch-container">
                            <span style="font-size:14px; font-weight:600; color:#333;">Afficher sur le site</span>
                            <label class="switch">
                                <input type="checkbox" name="actif" value="1" <?= $produit['actif'] ? 'checked' : '' ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <p style="font-size:12px; color:#666; margin-top:10px; line-height:1.4;">
                            Si désactivé, le produit sera caché aux clients mais restera visible dans l'admin.
                        </p>
                    </div>

                    <div class="sticky-actions">
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-save"></i> Enregistrer
                        </button>
                    </div>

                </div>
            </div>
        </form>
    </main>

    <script>
        // 1. Calcul du badge de réduction en temps réel
        function calculateDiscount() {
            const price = parseFloat(document.getElementById('prix').value);
            const promo = parseFloat(document.getElementById('promo').value);
            const badge = document.getElementById('discountBadge');

            if(price > 0 && promo > 0 && promo < price) {
                const percent = Math.round(((price - promo) / price) * 100);
                badge.innerText = `-${percent}%`;
                badge.style.display = 'block';
            } else {
                badge.style.display = 'none';
            }
        }
        // Lancer au chargement pour l'édition
        calculateDiscount();

        // 2. Gestion Drag & Drop Image
        const dropArea = document.getElementById('drop-area');
        
        // Empêcher les comportements par défaut
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        // Effets visuels
        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.addEventListener(eventName, () => dropArea.classList.add('dragover'), false);
        });
        ['dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, () => dropArea.classList.remove('dragover'), false);
        });

        // Gestion du drop
        dropArea.addEventListener('drop', handleDrop, false);
        dropArea.addEventListener('click', () => document.getElementById('fileElem').click());

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFiles(files);
            // Mettre les fichiers dans l'input file pour l'envoi du formulaire
            document.getElementById('fileElem').files = files;
        }

        function handleFiles(files) {
            if(files.length > 0) {
                const file = files[0];
                if (file.type.startsWith('image/')) {
                    previewFile(file);
                }
            }
        }

        function previewFile(file) {
            const reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onloadend = function() {
                const img = document.getElementById('gallery-img');
                img.src = reader.result;
                img.style.display = 'block';
                document.getElementById('preview-box').style.display = 'block';
            }
        }
    </script>

</body>
</html>