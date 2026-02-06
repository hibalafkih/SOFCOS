<?php
session_start();
require_once '../config.php';

// Sécurité
if(!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit(); }

$db = isset($pdo) ? $pdo : (isset($conn) ? $conn : new PDO("mysql:host=localhost;dbname=sofcos_db", "root", ""));
$msg_alert = "";

// Supprimer un message
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->prepare("DELETE FROM messages WHERE id = ?")->execute([$id]);
    $msg_alert = "<div class='alert success'><i class='fas fa-check-circle'></i> Message supprimé.</div>";
}

// Récupérer les messages
$messages = $db->query("SELECT * FROM messages ORDER BY date_envoi DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Messagerie - SOFCOS Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #1A3C34;
            --gold: #C5A059;
            --bg-body: #f3f4f6;
            --sidebar-width: 270px;
        }

        body { font-family: 'Outfit', sans-serif; background: var(--bg-body); color: #444; display: flex; margin:0; }
        
        /* --- STYLE POUR SIDEBAR (doit correspondre à votre sidebar.php) --- */
        .sidebar { width: var(--sidebar-width); background: var(--primary); color: white; position: fixed; height: 100vh; z-index:100; }

        .main { margin-left: var(--sidebar-width); padding: 40px; width: 100%; box-sizing: border-box; }
        
        h1 { margin-top: 0; color: var(--primary); font-size: 24px; margin-bottom: 30px; }

        .card { background: white; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.04); overflow: hidden; }
        
        table { width: 100%; border-collapse: collapse; }
        
        th { 
            text-align: left; padding: 20px; background: #fcfcfc; 
            color: #888; font-size: 13px; text-transform: uppercase; font-weight: 600;
            border-bottom: 1px solid #eee;
        }
        
        td { padding: 20px; border-bottom: 1px solid #f0f0f0; font-size: 15px; vertical-align: middle; }
        tr:hover { background-color: #fafafa; }
        
        /* Message non lu en gras */
        .row-unread td { font-weight: 600; color: #1A3C34; background: #fffcf5; }
        
        /* --- DOUBLES BADGES --- */
        .status-container { display: flex; flex-direction: column; gap: 5px; }

        .badge { 
            padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; 
            text-transform: uppercase; display: inline-flex; align-items: center; gap: 5px;
            width: fit-content;
        }

        /* États d'ouverture */
        .bg-nouveau { background: #e0f2fe; color: #0284c7; }
        .bg-lu { background: #f3f4f6; color: #64748b; }

        /* États de réponse */
        .bg-repondu { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .bg-attente { background: #fff7ed; color: #9a3412; border: 1px solid #fed7aa; }

        .btn-icon { 
            display: inline-flex; align-items: center; justify-content: center;
            width: 35px; height: 35px; border-radius: 8px;
            color: #666; transition: 0.2s; text-decoration: none;
            background: #f8fafc; margin-left: 5px;
        }
        .btn-icon:hover { background: var(--primary); color: white; }
        .btn-del:hover { background: #fee2e2; color: #ef4444; }

        .alert { padding: 15px; margin-bottom: 20px; border-radius: 8px; background: #dcfce7; color: #166534; }
        
        .avatar {
            width: 40px; height: 40px; background: #e2e8f0; color: #64748b;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-weight: bold; margin-right: 15px; float: left;
        }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main">
        <h1>Boîte de réception</h1>
        <?= $msg_alert ?>

        <div class="card">
            <?php if(count($messages) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th width="18%">État du Message</th>
                        <th width="25%">Expéditeur</th>
                        <th>Sujet</th>
                        <th width="15%">Date</th>
                        <th width="10%" style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($messages as $m): 
                        $is_new = ($m['statut'] == 'nouveau');
                        $is_replied = ($m['statut'] == 'repondu');
                        
                        $initial = strtoupper(substr($m['nom'], 0, 1));
                    ?>
                    <tr class="<?= $is_new ? 'row-unread' : '' ?>">
                        <td>
                            <div class="status-container">
                                <?php if($is_new): ?>
                                    <span class="badge bg-nouveau"><i class="fas fa-circle" style="font-size:8px;"></i> Nouveau</span>
                                <?php else: ?>
                                    <span class="badge bg-lu"><i class="fas fa-envelope-open"></i> Lu</span>
                                <?php endif; ?>

                                <?php if($is_replied): ?>
                                    <span class="badge bg-repondu"><i class="fas fa-check-double"></i> Répondu</span>
                                <?php else: ?>
                                    <span class="badge bg-attente"><i class="fas fa-clock"></i> En attente</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="avatar"><?= $initial ?></div>
                            <div style="padding-top:2px;">
                                <?= htmlspecialchars($m['nom']) ?><br>
                                <small style="font-weight:normal; color:#888; font-size:12px;"><?= htmlspecialchars($m['email']) ?></small>
                            </div>
                        </td>
                        <td>
                            <?= htmlspecialchars($m['sujet'] ?? '(Sans sujet)') ?>
                        </td>
                        <td style="color:#888;">
                            <?= date('d/m/Y H:i', strtotime($m['date_envoi'])) ?>
                        </td>
                        <td style="text-align:right;">
                            <a href="details_message.php?id=<?= $m['id'] ?>" class="btn-icon" title="Ouvrir"><i class="fas fa-eye"></i></a>
                            <a href="?delete=<?= $m['id'] ?>" class="btn-icon btn-del" onclick="return confirm('Supprimer ce message ?')" title="Supprimer"><i class="fas fa-trash-alt"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div style="padding: 50px; text-align: center; color: #888;">
                    <i class="fas fa-inbox" style="font-size: 40px; margin-bottom: 15px; opacity: 0.3;"></i>
                    <p>Aucun message pour le moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>