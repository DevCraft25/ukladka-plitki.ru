<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <h2>🎯 Админ Панель</h2>
        <p>Укладка плитки</p>
    </div>
    
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-item <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        
        <a href="leads.php" class="nav-item <?= $current_page === 'leads.php' ? 'active' : '' ?>">
            <i class="fas fa-envelope"></i>
            <span>Заявки</span>
            <?php
            $db = getDB();
            $stmt = $db->query("SELECT COUNT(*) FROM leads WHERE status = 'new'");
            $new_count = $stmt->fetchColumn();
            if ($new_count > 0):
            ?>
                <span class="badge"><?= $new_count ?></span>
            <?php endif; ?>
        </a>
        
        <a href="videos.php" class="nav-item <?= $current_page === 'videos.php' ? 'active' : '' ?>">
            <i class="fas fa-video"></i>
            <span>Видео</span>
        </a>
        
        <a href="settings.php" class="nav-item <?= $current_page === 'settings.php' ? 'active' : '' ?>">
            <i class="fas fa-cog"></i>
            <span>Настройки</span>
        </a>
        
        <a href="logout.php" class="nav-item logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Выход</span>
        </a>
    </nav>
</aside>
