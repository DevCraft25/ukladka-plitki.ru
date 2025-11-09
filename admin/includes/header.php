<?php
$admin = getCurrentAdmin();
?>
<header class="admin-header">
    <div class="header-left">
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <div class="header-right">
        <a href="../index.html" target="_blank" class="btn btn-sm btn-outline">
            <i class="fas fa-external-link-alt"></i> Открыть сайт
        </a>
        
        <div class="user-menu">
            <div class="user-info">
                <span class="user-name"><?= htmlspecialchars($admin['full_name'] ?? $admin['username']) ?></span>
                <span class="user-role"><?= ucfirst($admin['role']) ?></span>
            </div>
            <div class="user-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
        </div>
    </div>
</header>

<script>
// Sidebar toggle
document.getElementById('sidebarToggle')?.addEventListener('click', function() {
    document.querySelector('.sidebar')?.classList.toggle('collapsed');
    document.querySelector('.admin-content')?.classList.toggle('expanded');
});
</script>
