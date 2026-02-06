<?php
session_start();
require_once '../config.php';
if(!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit(); }
$conn = $pdo;
$msg = "";

// --- 1. AJOUTER UN LIVREUR ---
if(isset($_POST['btn_add'])) {
    $nom = $_POST['nom'];
    $tel = $_POST['telephone'];
    // On vérifie si le numéro existe déjà
    $check = $conn->prepare("SELECT id FROM livreurs WHERE telephone = ?");
    $check->execute([$tel]);
    if($check->rowCount() > 0) {
        $msg = "<div class='alert error'>Ce numéro de téléphone existe déjà.</div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO livreurs (nom, telephone) VALUES (?, ?)");
        $stmt->execute([$nom, $tel]);
        $msg = "<div class='alert success'>Livreur ajouté avec succès !</div>";
    }
}

// --- 2. SUPPRIMER / ACTION ---
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // On ne supprime pas vraiment pour garder l'historique, on peut désactiver (ou supprimer si pas de commande)
    // Ici suppression simple :
    $conn->prepare("DELETE FROM livreurs WHERE id = ?")->execute([$id]);
    header("Location: equipe_livreurs.php"); exit();
}

// --- 3. REQUÊTE INTELLIGENTE (AVEC STATS) ---
// On joint la table commandes pour compter
$sql = "SELECT 
            l.*, 
            COUNT(CASE WHEN c.statut = 'livre' THEN 1 END) as total_livre,
            COUNT(CASE WHEN c.statut = 'expedie' THEN 1 END) as en_cours,
            SUM(CASE WHEN c.statut = 'livre' THEN c.total ELSE 0 END) as chiffre_affaire
        FROM livreurs l
        LEFT JOIN commandes c ON l.id = c.livreur_id
        GROUP BY l.id
        ORDER BY l.actif DESC, total_livre DESC";

$livreurs = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Équipe & Performance - SOFCOS</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #1A3C34; --gold: #C5A059; --bg-body: #f3f4f6; --sidebar-width: 270px; }
        body { font-family: 'Outfit', sans-serif; background: var(--bg-body); margin: 0; display: flex; color: #333; }
        .main { margin-left: var(--sidebar-width); padding: 40px; width: 100%; box-sizing: border-box; }
        
        /* LAYOUT */
        .grid-split { display: grid; grid-template-columns: 300px 1fr; gap: 30px; }
        
        /* CARDS */
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); margin-bottom: 20px; }
        .card h3 { margin-top: 0; color: var(--primary); font-size: 18px; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 20px; }

        /* FORM */
        .form-group { margin-bottom: 15px; }
        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px; color: #6b7280; }
        input { width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; box-sizing: border-box; font-family: inherit; }
        input:focus { border-color: var(--gold); outline: none; }
        .btn-submit { background: var(--primary); color: white; border: none; padding: 12px; border-radius: 8px; width: 100%; cursor: pointer; font-weight: 600; transition: 0.2s; }
        .btn-submit:hover { background: #14302a; }

        /* LISTE LIVREURS (STYLE PROFIL) */
        .driver-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        
        .driver-card { 
            background: white; border-radius: 12px; overflow: hidden; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.02); border: 1px solid #f3f4f6; 
            position: relative;
        }
        
        .driver-header { padding: 20px; display: flex; align-items: center; gap: 15px; border-bottom: 1px solid #f9fafb; }
        .avatar { 
            width: 50px; height: 50px; background: #e0e7ff; color: #3730a3; 
            border-radius: 50%; display: flex; align-items: center; justify-content: center; 
            font-weight: bold; font-size: 18px; 
        }
        
        .driver-stats { display: flex; background: #fafafa; }
        .stat-box { flex: 1; text-align: center; padding: 15px 5px; border-right: 1px solid #eee; }
        .stat-box:last-child { border-right: none; }
        .stat-val { font-weight: 700; font-size: 16px; color: var(--primary); }
        .stat-lbl { font-size: 10px; text-transform: uppercase; color: #888; margin-top: 2px; }

        .tel-btn { 
            display: block; text-align: center; padding: 10px; 
            text-decoration: none; color: white; background: var(--gold); 
            font-weight: 500; font-size: 14px; transition: 0.2s;
        }
        .tel-btn:hover { background: #b08d4b; }

        .btn-trash { 
            position: absolute; top: 15px; right: 15px; 
            color: #ef4444; opacity: 0.3; transition: 0.2s; 
        }
        .btn-trash:hover { opacity: 1; }

        .alert { padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 13px; }
        .success { background: #dcfce7; color: #166534; }
        .error { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main">
        <h1 style="color:var(--primary); margin-bottom:10px;">Équipe Logistique</h1>
        <p style="color:#666; margin-bottom:30px;">Gérez vos livreurs et suivez leurs performances.</p>

        <div class="grid-split">
            
            <div>
                <div class="card">
                    <h3><i class="fas fa-user-plus"></i> Nouveau Livreur</h3>
                    <?= $msg ?>
                    <form method="POST">
                        <div class="form-group">
                            <label>Nom complet</label>
                            <input type="text" name="nom" placeholder="Ex: Karim Benali" required>
                        </div>
                        <div class="form-group">
                            <label>Numéro de téléphone</label>
                            <input type="text" name="telephone" placeholder="06 00 00 00 00" required>
                        </div>
                        <button type="submit" name="btn_add" class="btn-submit">
                            Ajouter à l'équipe
                        </button>
                    </form>
                </div>
                
                <div style="background:#e0e7ff; padding:20px; border-radius:12px; color:#3730a3; font-size:13px;">
                    <i class="fas fa-info-circle"></i> <strong>Conseil :</strong><br>
                    Ajoutez le numéro réel du livreur. Cela permettra de l'appeler directement depuis l'interface en un clic.
                </div>
            </div>

            <div>
                <h3 style="margin-top:0; color:#666; font-size:16px; margin-bottom:15px;">Membres de l'équipe (<?= count($livreurs) ?>)</h3>
                
                <div class="driver-list">
                    <?php foreach($livreurs as $l): 
                        $initiales = strtoupper(substr($l['nom'], 0, 1));
                    ?>
                    <div class="driver-card">
                        <a href="?delete=<?= $l['id'] ?>" class="btn-trash" onclick="return confirm('Supprimer ce livreur ?')" title="Supprimer"><i class="fas fa-trash"></i></a>
                        
                        <div class="driver-header">
                            <div class="avatar"><?= $initiales ?></div>
                            <div>
                                <div style="font-weight:600; font-size:16px; color:#111;"><?= htmlspecialchars($l['nom']) ?></div>
                                <div style="font-size:12px; color:#888;">ID: #<?= $l['id'] ?></div>
                            </div>
                        </div>

                        <div class="driver-stats">
                            <div class="stat-box">
                                <div class="stat-val"><?= $l['total_livre'] ?></div>
                                <div class="stat-lbl">Livrées</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-val" style="color:#f59e0b;"><?= $l['en_cours'] ?></div>
                                <div class="stat-lbl">En cours</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-val" style="color:#10b981; font-size:14px;">
                                    <?= number_format($l['chiffre_affaire'], 0, ',', ' ') ?>
                                </div>
                                <div class="stat-lbl">CA (DH)</div>
                            </div>
                        </div>

                        <a href="tel:<?= $l['telephone'] ?>" class="tel-btn">
                            <i class="fas fa-phone-alt"></i> &nbsp; <?= htmlspecialchars($l['telephone']) ?>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if(empty($livreurs)): ?>
                    <div style="text-align:center; padding:50px; color:#aaa; background:white; border-radius:12px;">
                        <i class="fas fa-users-slash" style="font-size:30px; margin-bottom:10px;"></i><br>
                        Aucun livreur dans l'équipe pour le moment.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>
</html>