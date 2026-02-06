<?php
session_start();
require_once '../config.php';

// ========== SÉCURITÉ ==========
if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$conn = $pdo;

// ========== TRAITEMENT (AJOUT / MODIF / SUPPRESSION) ==========
$success = '';
$error = '';

// 1. Suppression
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Vérifier s'il y a des produits liés
    $check = $conn->prepare("SELECT COUNT(*) FROM produits WHERE categorie_id = ?");
    $check->execute([$id]);
    if($check->fetchColumn() > 0) {
        $error = "Impossible de supprimer : cette catégorie contient des produits.";
    } else {
        $conn->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
        $success = "Catégorie supprimée avec succès.";
    }
}

// 2. Ajout ou Modification
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_category'])) {
    $id = (int)$_POST['cat_id'];
    $nom = trim($_POST['nom']);
    $description = trim($_POST['description']);

    if(!empty($nom)) {
        if($id > 0) {
            $stmt = $conn->prepare("UPDATE categories SET nom=?, description=? WHERE id=?");
            $stmt->execute([$nom, $description, $id]);
            $success = "Catégorie mise à jour !";
        } else {
            $stmt = $conn->prepare("INSERT INTO categories (nom, description) VALUES (?, ?)");
            $stmt->execute([$nom, $description]);
            $success = "Nouvelle catégorie ajoutée !";
        }
    } else {
        $error = "Le nom est obligatoire.";
    }
}

// ========== RÉCUPÉRATION DES DONNÉES ==========

// 1. Les catégories + nombre de produits
$sql = "SELECT c.*, COUNT(p.id) as nb_produits 
        FROM categories c 
        LEFT JOIN produits p ON p.categorie_id = c.id 
        GROUP BY c.id 
        ORDER BY c.nom ASC";
$categories = $conn->query($sql)->fetchAll();

// 2. Récupérer les marques liées à chaque catégorie
$sql_marques = "SELECT DISTINCT p.categorie_id, m.nom, m.image
                FROM produits p
                JOIN marques m ON p.marque_id = m.id
                WHERE p.marque_id IS NOT NULL";
$raw_brands = $conn->query($sql_marques)->fetchAll();

$brands_by_cat = [];
foreach($raw_brands as $b) {
    $brands_by_cat[$b['categorie_id']][] = [
        'nom' => $b['nom'],
        'image' => $b['image']
    ];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catégories - SOFCOS Admin</title>
    
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
            --sidebar-width: 260px; /* Important pour aligner avec sidebar.php */
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Open Sans', sans-serif; background: var(--bg-body); color: var(--text-dark); display: flex; min-height: 100vh; }
        a { text-decoration: none; color: inherit; }
        h1, h2, h3 { font-family: 'Poppins', sans-serif; }

        /* Le style de la sidebar est maintenant géré par sidebar.php ou CSS global, 
           mais on garde le margin-left pour le contenu principal */
        
        /* MAIN CONTENT */
        .main { margin-left: var(--sidebar-width); flex: 1; padding: 30px; width: calc(100% - var(--sidebar-width)); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-title { font-size: 26px; font-weight: 600; color: var(--sidebar-bg); }
        
        /* CONTENT GRID */
        .content-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }
        @media(max-width: 1000px) { .content-grid { grid-template-columns: 1fr; } }

        /* CARDS */
        .card { background: var(--white); border-radius: 16px; padding: 0; box-shadow: 0 10px 30px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #f0f0f0; }
        .form-card { padding: 30px; position: sticky; top: 30px; }

        /* TABLE */
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 18px 25px; color: var(--text-grey); font-size: 12px; text-transform: uppercase; font-weight: 600; background: #f9fafb; border-bottom: 1px solid #eee; }
        td { padding: 18px 25px; border-bottom: 1px solid #eee; color: var(--text-dark); font-size: 14px; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #fdfdfd; }
        .count-badge { background: #f3f4f6; color: var(--text-grey); padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }

        /* BOUTON MARQUES */
        .btn-show-brands {
            background: #fffbeb; color: #b45309; border: 1px solid #fcd34d;
            padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;
            cursor: pointer; display: inline-flex; align-items: center; gap: 5px;
            transition: 0.2s;
        }
        .btn-show-brands:hover { background: #fcd34d; color: #78350f; }

        /* FORMULAIRE */
        .form-title { font-size: 18px; font-weight: 600; color: var(--sidebar-bg); margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 13px; color: var(--text-dark); }
        .form-control { width: 100%; padding: 12px 15px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; transition: 0.3s; background: #f9fafb; font-size: 14px; }
        .form-control:focus { border-color: var(--sidebar-bg); outline: none; background: white; box-shadow: 0 0 0 3px rgba(26, 60, 52, 0.1); }
        
        .btn-submit { width: 100%; padding: 12px; background: var(--sidebar-bg); color: white; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn-submit:hover { background: #143029; }
        .btn-reset { width: 100%; padding: 10px; background: transparent; color: #888; border: none; cursor: pointer; font-size: 13px; margin-top: 5px; }

        .action-btn { color: #888; margin-left: 10px; cursor: pointer; transition: 0.2s; background:none; border:none; font-size:14px; }
        .action-btn:hover { color: var(--accent); }
        .btn-delete:hover { color: #ef4444; }

        /* MODAL STYLES */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); z-index: 1000; display: none;
            justify-content: center; align-items: center;
        }
        .modal-content {
            background: white; width: 500px; max-width: 90%; border-radius: 12px;
            padding: 30px; position: relative; box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            animation: slideDown 0.3s ease-out;
        }
        @keyframes slideDown { from { transform: translateY(-20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        
        .close-modal { position: absolute; top: 15px; right: 15px; cursor: pointer; font-size: 20px; color: #888; }
        .modal-header { font-size: 18px; font-weight: 700; color: var(--sidebar-bg); margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        
        .brands-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 15px; }
        .brand-item { text-align: center; border: 1px solid #eee; padding: 10px; border-radius: 8px; transition: 0.2s; }
        .brand-item:hover { border-color: var(--gold); background: #fffcf5; }
        .brand-logo { width: 50px; height: 50px; object-fit: contain; margin-bottom: 5px; }
        .brand-name { font-size: 12px; font-weight: 600; color: #444; }

    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="main">
        <header class="header">
            <div>
                <h1 class="page-title">Gestion des Catégories</h1>
                <p style="color:var(--text-grey); font-size:14px; margin-top:5px;">Organisez vos produits par gammes.</p>
            </div>
        </header>

        <div class="content-grid">
            
            <div class="card">
                <?php if($success): ?>
                    <div style="padding:15px; background:#dcfce7; color:#166534; font-size:14px; border-bottom:1px solid #bbf7d0;">
                        <i class="fas fa-check-circle"></i> <?= $success ?>
                    </div>
                <?php endif; ?>
                <?php if($error): ?>
                    <div style="padding:15px; background:#fee2e2; color:#991b1b; font-size:14px; border-bottom:1px solid #fecaca;">
                        <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
                    </div>
                <?php endif; ?>

                <table>
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Description</th>
                            <th>Produits</th>
                            <th>Marques</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($categories as $cat): 
                            $nb_marques = isset($brands_by_cat[$cat['id']]) ? count($brands_by_cat[$cat['id']]) : 0;
                        ?>
                        <tr>
                            <td>
                                <strong style="font-size:15px;"><?= htmlspecialchars($cat['nom']) ?></strong>
                            </td>
                            <td style="color:#666; font-size:13px;">
                                <?= htmlspecialchars(substr($cat['description'], 0, 50)) ?>...
                            </td>
                            <td>
                                <span class="count-badge"><?= $cat['nb_produits'] ?> produits</span>
                            </td>
                            <td>
                                <?php if($nb_marques > 0): ?>
                                    <button class="btn-show-brands" onclick="openBrandsModal(<?= $cat['id'] ?>, '<?= addslashes($cat['nom']) ?>')">
                                        <i class="fas fa-eye"></i> <?= $nb_marques ?> Marques
                                    </button>
                                <?php else: ?>
                                    <span style="font-size:12px; color:#aaa;">Aucune marque</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:right;">
                                <button onclick='editCat(<?= json_encode($cat) ?>)' class="action-btn" title="Modifier">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <a href="?delete=<?= $cat['id'] ?>" class="action-btn btn-delete" title="Supprimer" onclick="return confirm('Confirmer la suppression ?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(count($categories) == 0): ?>
                            <tr><td colspan="5" style="text-align:center; padding:30px; color:#888;">Aucune catégorie créée.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div>
                <div class="card form-card">
                    <div class="form-title" id="formTitle">Ajouter une catégorie</div>
                    
                    <form method="POST">
                        <input type="hidden" name="save_category" value="1">
                        <input type="hidden" name="cat_id" id="cat_id" value="0">

                        <div class="form-group">
                            <label>Nom de la catégorie</label>
                            <input type="text" name="nom" id="nom" class="form-control" required placeholder="Ex: Soins Visage">
                        </div>

                        <div class="form-group">
                            <label>Description (courte)</label>
                            <textarea name="description" id="description" class="form-control" rows="3" placeholder="Description pour le site..."></textarea>
                        </div>

                        <button type="submit" class="btn-submit" id="submitBtn">Ajouter</button>
                        <button type="button" class="btn-reset" onclick="resetForm()">Annuler / Nouveau</button>
                    </form>
                </div>
            </div>

        </div>
    </main>

    <div class="modal-overlay" id="brandsModal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeBrandsModal()">&times;</span>
            <div class="modal-header">Marques dans <span id="modalCatName" style="color:var(--gold)"></span></div>
            <div class="brands-list" id="modalBrandsList">
                </div>
        </div>
    </div>

    <script>
        const brandsData = <?= json_encode($brands_by_cat) ?>;

        function openBrandsModal(catId, catName) {
            const listContainer = document.getElementById('modalBrandsList');
            document.getElementById('modalCatName').innerText = catName;
            listContainer.innerHTML = '';

            if(brandsData[catId]) {
                brandsData[catId].forEach(brand => {
                    let imgHtml = '';
                    if(brand.image) {
                        imgHtml = `<img src="../uploads/marques/${brand.image}" class="brand-logo" alt="${brand.nom}">`;
                    } else {
                        imgHtml = `<div style="height:50px; display:flex; align-items:center; justify-content:center; color:#ccc; font-weight:bold; background:#fafafa; border-radius:50%; width:50px; margin:0 auto 5px;">${brand.nom.charAt(0)}</div>`;
                    }

                    const html = `
                        <div class="brand-item">
                            ${imgHtml}
                            <div class="brand-name">${brand.nom}</div>
                        </div>
                    `;
                    listContainer.innerHTML += html;
                });
            }
            document.getElementById('brandsModal').style.display = 'flex';
        }

        function closeBrandsModal() {
            document.getElementById('brandsModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('brandsModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        function editCat(cat) {
            document.getElementById('cat_id').value = cat.id;
            document.getElementById('nom').value = cat.nom;
            document.getElementById('description').value = cat.description;
            document.getElementById('formTitle').innerText = "Modifier la catégorie";
            document.getElementById('submitBtn').innerText = "Mettre à jour";
        }

        function resetForm() {
            document.getElementById('cat_id').value = "0";
            document.getElementById('nom').value = "";
            document.getElementById('description').value = "";
            document.getElementById('formTitle').innerText = "Ajouter une catégorie";
            document.getElementById('submitBtn').innerText = "Ajouter";
        }
    </script>

</body>
</html>