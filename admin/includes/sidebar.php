<aside class="sidebar">
    <div class="sidebar-header">
        <h2><i class="fas fa-spa"></i> SOFCOS Admin</h2>
    </div>
    
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-line"></i> Tableau de bord
        </a>
        
        <a href="produits.php" class="<?= basename($_SERVER['PHP_SELF']) == 'produits.php' ? 'active' : '' ?>">
            <i class="fas fa-box"></i> Produits
        </a>
        
        <a href="categories.php" class="<?= basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : '' ?>">
            <i class="fas fa-tags"></i> Catégories
        </a>
        
        <a href="commandes.php" class="<?= basename($_SERVER['PHP_SELF']) == 'commandes.php' ? 'active' : '' ?>">
            <i class="fas fa-shopping-cart"></i> Commandes
        </a>
        
        <a href="clients.php" class="<?= basename($_SERVER['PHP_SELF']) == 'clients.php' ? 'active' : '' ?>">
            <i class="fas fa-users"></i> Clients
        </a>
        
        <a href="livraisons.php" class="<?= basename($_SERVER['PHP_SELF']) == 'livraisons.php' ? 'active' : '' ?>">
            <i class="fas fa-truck"></i> Livraisons
        </a>
        
        <a href="admins.php" class="<?= basename($_SERVER['PHP_SELF']) == 'admins.php' ? 'active' : '' ?>">
            <i class="fas fa-user-shield"></i> Administrateurs
        </a>
        
        <a href="mon_compte.php" class="<?= basename($_SERVER['PHP_SELF']) == 'mon_compte.php' ? 'active' : '' ?>">
            <i class="fas fa-user-circle"></i> Mon Compte
        </a>

        <div style="margin: 20px 0; border-top: 1px solid rgba(255,255,255,0.1);"></div>

        <a href="../index.php" target="_blank">
            <i class="fas fa-external-link-alt"></i> Voir le site
        </a>
        
        <a href="logout.php" style="color: #ff6b6b;">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
        </a>
    </nav>
</aside>