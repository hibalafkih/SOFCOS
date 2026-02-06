<?php
session_start();
require_once '../config.php';

// SÉCURITÉ DE BASE
if(!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit(); }

$conn = isset($pdo) ? $pdo : (isset($conn) ? $conn : new PDO("mysql:host=localhost;dbname=sofcos_db", "root", ""));

// Générer CSRF
if(!isset($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }

$success = ''; $error = '';

// ... (Gardez ici vos blocs de SUPPRESSION et TOGGLE STATUS comme avant) ...
// Si vous n'avez pas changé ces blocs, ne les touchez pas, sinon copiez ceux de la réponse précédente.
// Je remets juste la logique de suppression pour être sûr :
if(isset($_GET['delete'])) {
    if(!isset($_GET['csrf']) || !hash_equals($_SESSION['csrf_token'], $_GET['csrf'])) {
        $error = "Erreur token.";
    } else {
        $id = (int)$_GET['delete'];
        try {
            $conn->prepare("DELETE FROM details_commande WHERE commande_id IN (SELECT id FROM commandes WHERE client_id = ?)")->execute([$id]);
            // On essaie aussi de supprimer par email pour nettoyer complètement
            $stmtEmail = $conn->prepare("SELECT email FROM clients WHERE id = ?");
            $stmtEmail->execute([$id]);
            $emailDel = $stmtEmail->fetchColumn();
            
            $conn->prepare("DELETE FROM commandes WHERE client_id = ? OR email_client = ?")->execute([$id, $emailDel]);
            $conn->prepare("DELETE FROM clients WHERE id = ?")->execute([$id]);
            $success = "Client supprimé.";
        } catch(Exception $e) { $error = "Erreur suppression."; }
    }
}
if(isset($_GET['toggle_status'])) {
    $id = (int)$_GET['toggle_status'];
    $conn->prepare("UPDATE clients SET actif = NOT actif WHERE id = ?")->execute([$id]);
    $success = "Statut modifié.";
}

// RECHERCHE
$search = $_GET['search'] ?? '';
$filter_ville = $_GET['ville'] ?? '';
$order = $_GET['order'] ?? 'recent';

$sql = "SELECT * FROM clients WHERE 1=1";
$params = [];
if(!empty($search)) {
    $sql .= " AND (nom LIKE ? OR prenom LIKE ? OR email LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}
if(!empty($filter_ville)) {
    $sql .= " AND ville = ?";
    $params[] = $filter_ville;
}
if($order === 'ancien') $sql .= " ORDER BY id ASC";
elseif($order === 'nom') $sql .= " ORDER BY nom ASC";
else $sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$clients = $stmt->fetchAll();

try { $villes = $conn->query("SELECT DISTINCT ville FROM clients WHERE ville != '' ORDER BY ville")->fetchAll(PDO::FETCH_COLUMN); } catch(Exception $e) { $villes = []; }

// --- CORRECTION MAJEURE ICI ---
// On compte par ID *OU* par Email pour être sûr de tout trouver
function getNbCommandes($client_id, $client_email, $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM commandes WHERE client_id = ? OR email_client = ?");
        $stmt->execute([$client_id, $client_email]);
        return $stmt->fetchColumn();
    } catch(Exception $e) { return 0; }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Clients - SOFCOS Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #1A3C34; --bg-body: #f3f4f6; --sidebar-width: 270px; }
        body { font-family: 'Outfit', sans-serif; background: var(--bg-body); color: #444; display: flex; margin:0; }
        .sidebar { width: var(--sidebar-width); background: var(--primary); color: white; position: fixed; height: 100vh; z-index: 100; }
        .main { margin-left: var(--sidebar-width); padding: 40px; width: 100%; box-sizing: border-box; }
        h1 { color: var(--primary); margin-bottom: 30px; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .alert-success { background: #dcfce7; color: #166534; border-left: 5px solid #22c55e; }
        
        /* Table Styles */
        .table-card { background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.04); }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px 20px; background: #f8fafc; color: #64748b; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        td { padding: 15px 20px; border-bottom: 1px solid #f1f5f9; font-size: 14px; vertical-align: middle; }
        .client-avatar { width: 35px; height: 35px; background: #f0fdf4; color: var(--primary); border: 1px solid #dcfce7; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 12px; }
        .badge-active { background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
        .badge-blocked { background: #fee2e2; color: #991b1b; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
        .btn-icon { color: #64748b; font-size: 16px; margin-left: 8px; text-decoration: none; }
        .btn-icon:hover { color: var(--primary); }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main">
        <h1>Gestion des Clients</h1>
        <?php if($success) echo "<div class='alert alert-success'>$success</div>"; ?>
        
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Contact</th>
                        <th>Ville</th>
                        <th>Commandes</th> <th>Statut</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($clients as $c): 
                        $initiales = strtoupper(substr($c['prenom'],0,1) . substr($c['nom'],0,1));
                        $actif = $c['actif'];
                    ?>
                    <tr>
                        <td>
                            <div style="display:flex; align-items:center;">
                                <div class="client-avatar"><?= $initiales ?></div>
                                <div>
                                    <div style="font-weight:600; color:#1A3C34;"><?= htmlspecialchars($c['prenom'].' '.$c['nom']) ?></div>
                                    <div style="font-size:12px; color:#888;">#<?= $c['id'] ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div><?= htmlspecialchars($c['email']) ?></div>
                            <small><?= htmlspecialchars($c['telephone']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($c['ville']) ?></td>
                        <td>
                            <span style="background:#f1f5f9; padding:3px 8px; border-radius:4px; font-weight:600; font-size:12px; color:#475569;">
                                <?= getNbCommandes($c['id'], $c['email'], $conn) ?>
                            </span>
                        </td>
                        <td>
                            <?= $actif ? '<span class="badge-active">Actif</span>' : '<span class="badge-blocked">Bloqué</span>' ?>
                        </td>
                        <td style="text-align:right;">
                            <a href="client_details.php?id=<?= $c['id'] ?>" class="btn-icon"><i class="fas fa-eye"></i></a>
                            <a href="?toggle_status=<?= $c['id'] ?>&csrf=<?= $_SESSION['csrf_token'] ?>" class="btn-icon"><i class="fas <?= $actif?'fa-ban':'fa-check' ?>"></i></a>
                            <a href="?delete=<?= $c['id'] ?>&csrf=<?= $_SESSION['csrf_token'] ?>" class="btn-icon" onclick="return confirm('Supprimer ?')"><i class="fas fa-trash-alt"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>