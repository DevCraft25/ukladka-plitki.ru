<?php
require_once 'auth.php';
requireAdmin();

$admin = getCurrentAdmin();
$db = getDB();

// Get statistics
$stats = [];

// Total leads
$stmt = $db->query("SELECT COUNT(*) as count FROM leads");
$stats['total_leads'] = $stmt->fetch()['count'];

// New leads (today)
$stmt = $db->query("SELECT COUNT(*) as count FROM leads WHERE DATE(created_at) = CURDATE()");
$stats['today_leads'] = $stmt->fetch()['count'];

// Total videos
$stmt = $db->query("SELECT COUNT(*) as count FROM videos WHERE is_active = 1");
$stats['total_videos'] = $stmt->fetch()['count'];

// Total views
$stmt = $db->query("SELECT SUM(views) as total FROM videos");
$stats['total_views'] = $stmt->fetch()['total'] ?? 0;

// Recent leads
$stmt = $db->query("SELECT * FROM leads ORDER BY created_at DESC LIMIT 10");
$recent_leads = $stmt->fetchAll();

// Lead status distribution
$stmt = $db->query("
    SELECT status, COUNT(*) as count 
    FROM leads 
    GROUP BY status
");
$status_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Админ Панель</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin-style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="page-header">
                <h1>📊 Dashboard</h1>
                <p>Добро пожаловать, <?= htmlspecialchars($admin['full_name'] ?? $admin['username']) ?>!</p>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card blue">
                    <div class="stat-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $stats['total_leads'] ?></h3>
                        <p>Всего заявок</p>
                    </div>
                </div>
                
                <div class="stat-card green">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $stats['today_leads'] ?></h3>
                        <p>Заявок сегодня</p>
                    </div>
                </div>
                
                <div class="stat-card purple">
                    <div class="stat-icon">
                        <i class="fas fa-video"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $stats['total_videos'] ?></h3>
                        <p>Активных видео</p>
                    </div>
                </div>
                
                <div class="stat-card orange">
                    <div class="stat-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($stats['total_views']) ?></h3>
                        <p>Просмотров видео</p>
                    </div>
                </div>
            </div>
            
            <!-- Recent Leads -->
            <div class="content-section">
                <div class="section-header">
                    <h2>📩 Последние заявки</h2>
                    <a href="leads.php" class="btn btn-primary">Все заявки</a>
                </div>
                
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Имя</th>
                                <th>Телефон</th>
                                <th>Email</th>
                                <th>Статус</th>
                                <th>Дата</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_leads as $lead): ?>
                                <tr>
                                    <td><?= $lead['id'] ?></td>
                                    <td><?= htmlspecialchars($lead['name']) ?></td>
                                    <td><a href="tel:<?= $lead['phone'] ?>"><?= htmlspecialchars($lead['phone']) ?></a></td>
                                    <td><?= $lead['email'] ? htmlspecialchars($lead['email']) : '-' ?></td>
                                    <td><span class="status-badge status-<?= $lead['status'] ?>"><?= ucfirst($lead['status']) ?></span></td>
                                    <td><?= date('d.m.Y H:i', strtotime($lead['created_at'])) ?></td>
                                    <td>
                                        <a href="leads.php?id=<?= $lead['id'] ?>" class="btn-icon" title="Просмотр">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($recent_leads)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px;">
                                        Нет заявок
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
