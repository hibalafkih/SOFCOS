<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>
    
</body>
</html>
<?php
// Détection de la page active pour le menu
$page = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">
    <div class="brand">
        <i class="fas fa-leaf"></i> SOFCOS
    </div>

    <ul class="nav-links">
        <li>
            <a href="dashboard.php" class="<?= $page == 'dashboard.php' ? 'active' : '' ?>">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
        </li>

        <li>
            <a href="commandes.php" class="<?= ($page == 'commandes.php' || $page == 'details_commande.php') ? 'active' : '' ?>">
                <i class="fas fa-shopping-basket"></i> Commandes
            </a>
        </li>

        <li>
            <a href="produits.php" class="<?= ($page == 'produits.php' || $page == 'ajouter_produit.php' || $page == 'modifier_produit.php') ? 'active' : '' ?>">
                <i class="fas fa-box-open"></i> Produits
            </a>
        </li>

        <li>
            <a href="categories.php" class="<?= $page == 'categories.php' ? 'active' : '' ?>">
                <i class="fas fa-tags"></i> Catégories
            </a>
        </li>

        <li>
            <a href="contact.php" class="<?= ($page == 'contact.php' || $page == 'details_message.php') ? 'active' : '' ?>">
                <i class="fas fa-envelope"></i> Messages
            </a>
        </li>
<li>
    <a href="admins.php" class="<?= $page == 'admins.php' ? 'active' : '' ?>">
        <i class="fas fa-user-shield"></i> Administrateurs
    </a>
</li>
        <li>
            <a href="clients.php" class="<?= ($page == 'clients.php' || $page == 'client_details.php') ? 'active' : '' ?>">
                <i class="fas fa-users"></i> Clients
            </a>
        </li>
<li>
            <a href="livraisons.php" class="<?= ($page == 'livraisons.php') ? 'active' : '' ?>">
                <i class="fas fa-truck"></i> Livraisons
            </a>
        </li>
        <li>
            <a href="equipe_livreurs.php" class="<?= ($page == 'equipe_livreurs.php') ? 'active' : '' ?>">
                <i class="fas fa-truck"></i> Équipe Livreurs
            </a>
        </li>
        <li>
    <a href="historique.php" class="<?= ($page == 'historique.php') ? 'active' : '' ?>">
        <i class="fas fa-history"></i> Historique
    </a>
</li>
<li>
    <a href="marques.php" class="<?= ($page == 'marques.php') ? 'active' : '' ?>">
        <i class="fas fa-certificate"></i> Marques
    </a>
</li>

        <?php if(isset($_SESSION['admin_role']) && $_SESSION['admin_role'] == 'super_admin'): ?>
        <li>
            <a href="admins.php" class="<?= $page == 'admins.php' ? 'active' : '' ?>">
                <i class="fas fa-user-shield"></i> Équipe
            </a>
        </li>
        <?php endif; ?>
    </ul>

    <div class="user-profile">
        <div class="user-info">
            <div><?= htmlspecialchars($_SESSION['admin_nom'] ?? 'Admin') ?></div>
            <span><i class="fas fa-circle" style="color:#10b981; font-size:8px;"></i> En ligne</span>
        </div>
        <a href="logout.php" class="logout-btn" title="Déconnexion"><i class="fas fa-power-off"></i></a>
    </div>
</aside>