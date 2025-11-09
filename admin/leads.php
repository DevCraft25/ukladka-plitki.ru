<?php
require_once 'auth.php';
requireAdmin();

$db = getDB();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $lead_id = (int)$_POST['lead_id'];
    $new_status = $_POST['status'];
    
    $stmt = $db->prepare("UPDATE leads SET status = :status WHERE id = :id");
    $stmt->execute([':status' => $new_status, ':id' => $lead_id]);
    
    header('Location: leads.php?updated=1');
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $lead_id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM leads WHERE id = :id");
    $stmt->execute([':id' => $lead_id]);
    
    header('Location: leads.php?deleted=1');
    exit;
}

// Filters
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$query = "SELECT * FROM leads WHERE 1=1";
$params = [];

if ($status_filter) {
    $query .= " AND status = :status";
    $params[':status'] = $status_filter;
}

if ($search) {
    $query .= " AND (name LIKE :search OR phone LIKE :search OR email LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$query .= " ORDER BY created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$leads = $stmt->fetchAll();

// Get status counts
$status_counts = [];
$stmt = $db->query("SELECT status, COUNT(*) as count FROM leads GROUP BY status");
foreach ($stmt->fetchAll() as $row) {
    $status_counts[$row['status']] = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заявки - Админ Панель</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin-style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="page-header">
                <h1>📩 Заявки клиентов</h1>
                <p>Управление заявками с сайта</p>
            </div>
            
            <?php if (isset($_GET['updated'])): ?>
                <div class="alert alert-success">✅ Статус заявки обновлен!</div>
            <?php endif; ?>
            
            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success">✅ Заявка удалена!</div>
            <?php endif; ?>
            
            <!-- Filters -->
            <div class="filters-bar">
                <form method="GET" class="filter-form">
                    <div class="filter-group">
                        <input type="text" name="search" placeholder="Поиск по имени, телефону, email..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    
                    <div class="filter-group">
                        <select name="status">
                            <option value="">Все статусы</option>
                            <option value="new" <?= $status_filter === 'new' ? 'selected' : '' ?>>Новые (<?= $status_counts['new'] ?? 0 ?>)</option>
                            <option value="contacted" <?= $status_filter === 'contacted' ? 'selected' : '' ?>>Связались (<?= $status_counts['contacted'] ?? 0 ?>)</option>
                            <option value="in_progress" <?= $status_filter === 'in_progress' ? 'selected' : '' ?>>В работе (<?= $status_counts['in_progress'] ?? 0 ?>)</option>
                            <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Завершено (<?= $status_counts['completed'] ?? 0 ?>)</option>
                            <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Отменено (<?= $status_counts['cancelled'] ?? 0 ?>)</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Поиск
                    </button>
                    
                    <a href="leads.php" class="btn btn-secondary">Сбросить</a>
                </form>
            </div>
            
            <!-- Leads Table -->
            <div class="content-section">
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Имя</th>
                                <th>Телефон</th>
                                <th>Email</th>
                                <th>Сообщение</th>
                                <th>Источник</th>
                                <th>Статус</th>
                                <th>Дата</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leads as $lead): ?>
                                <tr>
                                    <td><?= $lead['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($lead['name']) ?></strong></td>
                                    <td>
                                        <a href="tel:<?= $lead['phone'] ?>" class="phone-link">
                                            <i class="fas fa-phone"></i> <?= htmlspecialchars($lead['phone']) ?>
                                        </a>
                                    </td>
                                    <td><?= $lead['email'] ? '<a href="mailto:' . $lead['email'] . '">' . htmlspecialchars($lead['email']) . '</a>' : '-' ?></td>
                                    <td>
                                        <?php if ($lead['message']): ?>
                                            <span class="message-preview" title="<?= htmlspecialchars($lead['message']) ?>">
                                                <?= mb_substr(htmlspecialchars($lead['message']), 0, 50) ?>...
                                            </span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($lead['source']) ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
                                            <select name="status" class="status-select" onchange="this.form.submit()">
                                                <option value="new" <?= $lead['status'] === 'new' ? 'selected' : '' ?>>Новая</option>
                                                <option value="contacted" <?= $lead['status'] === 'contacted' ? 'selected' : '' ?>>Связались</option>
                                                <option value="in_progress" <?= $lead['status'] === 'in_progress' ? 'selected' : '' ?>>В работе</option>
                                                <option value="completed" <?= $lead['status'] === 'completed' ? 'selected' : '' ?>>Завершено</option>
                                                <option value="cancelled" <?= $lead['status'] === 'cancelled' ? 'selected' : '' ?>>Отменено</option>
                                            </select>
                                            <input type="hidden" name="update_status" value="1">
                                        </form>
                                    </td>
                                    <td><?= date('d.m.Y H:i', strtotime($lead['created_at'])) ?></td>
                                    <td>
                                        <a href="tel:<?= $lead['phone'] ?>" class="btn-icon btn-success" title="Позвонить">
                                            <i class="fas fa-phone"></i>
                                        </a>
                                        <a href="?delete=<?= $lead['id'] ?>" class="btn-icon btn-danger" title="Удалить" onclick="return confirm('Удалить заявку?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($leads)): ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 60px;">
                                        <i class="fas fa-inbox" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                                        <p>Нет заявок по выбранным фильтрам</p>
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
