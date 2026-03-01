<?php $page = basename($_SERVER['PHP_SELF']); ?>
<div class="bottom-nav">
    <a href="index.php" class="nav-item <?= $page == 'index.php' ? 'active' : '' ?>">
        <i class="fas fa-list"></i> <span>Tournée</span>
    </a>
    <a href="scan.php" class="nav-item scan-item <?= $page == 'scan.php' ? 'active' : '' ?>">
        <i class="fas fa-qrcode"></i> <span>Scan</span>
    </a>
    <a href="historique.php" class="nav-item <?= $page == 'historique.php' ? 'active' : '' ?>">
        <i class="fas fa-clock"></i> <span>Historique</span>
    </a>
    <a href="profil.php" class="nav-item <?= $page == 'profil.php' ? 'active' : '' ?>">
        <i class="fas fa-user"></i> <span>Profil</span>
    </a>
</div>