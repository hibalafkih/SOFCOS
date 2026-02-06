<?php
session_start();
require_once '../config.php';
if(!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit(); }
$conn = $pdo;
$msg = "";

// --- 1. AJOUTER UNE MARQUE ---
if(isset($_POST['btn_add'])) {
    $nom = trim($_POST['nom']);
    $image_nom = "";

    // Gestion de l'image (Logo)
    if(isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $image_nom = "marque_" . time() . "." . $ext;
        move_uploaded_file($_FILES['logo']['tmp_name'], "../uploads/marques/" . $image_nom);
    }

    if(!empty($nom)) {
        $stmt = $conn->prepare("INSERT INTO marques (nom, image) VALUES (?, ?)");
        $stmt->execute([$nom, $image_nom]);
        $msg = "<div class='alert success'>Marque ajoutée avec succès !</div>";
    } else {
        $msg = "<div class='alert error'>Le nom est obligatoire.</div>";
    }
}

// --- 2. SUPPRIMER UNE MARQUE ---
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Optionnel : Supprimer l'image du dossier avant de supprimer la ligne
    // $old = $conn->query("SELECT image FROM marques WHERE id=$id")->fetchColumn();
    // if($old) unlink("../uploads/marques/$old");

    $conn->prepare("DELETE FROM marques WHERE id = ?")->execute([$id]);
    
    // Remettre à NULL les produits liés à cette marque
    $conn->prepare("UPDATE produits SET marque_id = NULL WHERE marque_id = ?")->execute([$id]);
    
    header("Location: marques.php"); exit();
}

// --- 3. LISTE DES MARQUES (Avec compteur de produits) ---
// On compte combien de produits appartiennent à chaque marque
$sql = "SELECT m.*, COUNT(p.id) as nb_produits 
        FROM marques m 
        LEFT JOIN produits p ON m.id = p.marque_id 
        GROUP BY m.id 
        ORDER BY m.nom ASC";
$marques = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Marques - SOFCOS</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #1A3C34; --gold: #C5A059; --bg-body: #f3f4f6; --sidebar-width: 270px; }
        body { font-family: 'Outfit', sans-serif; background: var(--bg-body); margin: 0; display: flex; }
        .main { margin-left: var(--sidebar-width); padding: 40px; width: 100%; box-sizing: border-box; }
        
        /* LAYOUT 2 COLONNES */
        .layout-grid { display: grid; grid-template-columns: 350px 1fr; gap: 30px; align-items: start; }

        /* CARDS */
        .card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
        .card h3 { margin-top: 0; color: var(--primary); margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px; }

        /* FORMULAIRE */
        .form-group { margin-bottom: 15px; }
        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px; color: #6b7280; }
        input[type="text"] { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        input[type="file"] { width: 100%; font-size: 12px; }
        
        .btn-submit { background: var(--primary); color: white; border: none; padding: 12px; border-radius: 8px; width: 100%; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-submit:hover { background: #14302a; }

        /* LISTE GRILLE VISUELLE */
        .brands-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
        
        .brand-item { 
            background: white; border-radius: 12px; padding: 20px; 
            text-align: center; border: 1px solid #eee; position: relative; 
            transition: 0.2s;
        }
        .brand-item:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); border-color: var(--gold); }

        .logo-box { 
            width: 80px; height: 80px; margin: 0 auto 15px auto; 
            border-radius: 50%; border: 1px solid #f3f4f6; 
            display: flex; align-items: center; justify-content: center; overflow: hidden; background: #fafafa;
        }
        .logo-box img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .logo-placeholder { font-size: 24px; color: #ccc; font-weight: bold; }

        .brand-name { font-weight: 600; color: #333; margin-bottom: 5px; }
        .prod-count { font-size: 12px; color: #888; background: #f3f4f6; padding: 2px 8px; border-radius: 10px; display: inline-block; }

        .btn-del { 
            position: absolute; top: 10px; right: 10px; 
            color: #ef4444; background: #fee2e2; 
            width: 25px; height: 25px; border-radius: 50%; 
            display: flex; align-items: center; justify-content: center; 
            font-size: 12px; text-decoration: none; opacity: 0; transition: 0.2s;
        }
        .brand-item:hover .btn-del { opacity: 1; }

        .alert { padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 13px; }
        .success { background: #dcfce7; color: #166534; }
        .error { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main">
        <h1 style="color:var(--primary); margin-bottom:10px;">Nos Marques</h1>
        <p style="color:#666; margin-bottom:30px;">Gérez les partenaires et fabricants.</p>

        <div class="layout-grid">
            
            <div class="card">
                <h3><i class="fas fa-plus-circle"></i> Ajouter une marque</h3>
                <?= $msg ?>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Nom de la marque</label>
                        <input type="text" name="nom" placeholder="Ex: L'Oréal, Nivea..." required>
                    </div>
                    <div class="form-group">
                        <label>Logo (Optionnel)</label>
                        <input type="file" name="logo" accept="image/*">
                        <small style="color:#999; display:block; margin-top:5px;">Format: JPG, PNG. Max 2Mo.</small>
                    </div>
                    <button type="submit" name="btn_add" class="btn-submit">Enregistrer</button>
                </form>
            </div>

            <div>
                <?php if(empty($marques)): ?>
                    <div class="card" style="text-align:center; color:#aaa; padding:50px;">
                        <i class="fas fa-tags" style="font-size:40px; margin-bottom:15px;"></i><br>
                        Aucune marque enregistrée.
                    </div>
                <?php else: ?>
                    <div class="brands-grid">
                        <?php foreach($marques as $m): 
                            $img_path = !empty($m['image']) ? "../uploads/marques/".$m['image'] : "";
                        ?>
                        <div class="brand-item">
                            <a href="?delete=<?= $m['id'] ?>" class="btn-del" onclick="return confirm('Supprimer cette marque ?')" title="Supprimer">
                                <i class="fas fa-times"></i>
                            </a>
                            
                            <div class="logo-box">
                                <?php if($img_path && file_exists($img_path)): ?>
                                    <img src="<?= $img_path ?>" alt="<?= htmlspecialchars($m['nom']) ?>">
                                <?php else: ?>
                                    <span class="logo-placeholder"><?= strtoupper(substr($m['nom'], 0, 1)) ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="brand-name"><?= htmlspecialchars($m['nom']) ?></div>
                            <div class="prod-count"><?= $m['nb_produits'] ?> produits</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

</body>
</html>