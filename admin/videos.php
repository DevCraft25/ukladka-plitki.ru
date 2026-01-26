<?php
require_once 'auth.php';
requireAdmin();

$db = getDB();

// Handle add/edit video
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_video'])) {
    $id = $_POST['id'] ?? null;
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $video_url = sanitize($_POST['video_url']);
    $thumbnail_url = sanitize($_POST['thumbnail_url'] ?? '');
    $duration = (int)($_POST['duration'] ?? 0);
    $display_order = (int)($_POST['display_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if ($id) {
        // Update
        $stmt = $db->prepare("
            UPDATE videos SET 
            title = :title, description = :description, video_url = :video_url,
            thumbnail_url = :thumbnail_url, duration = :duration, 
            display_order = :display_order, is_active = :is_active
            WHERE id = :id
        ");
        $stmt->execute([
            ':id' => $id, ':title' => $title, ':description' => $description,
            ':video_url' => $video_url, ':thumbnail_url' => $thumbnail_url,
            ':duration' => $duration, ':display_order' => $display_order,
            ':is_active' => $is_active
        ]);
    } else {
        // Insert
        $stmt = $db->prepare("
            INSERT INTO videos (title, description, video_url, thumbnail_url, duration, display_order, is_active)
            VALUES (:title, :description, :video_url, :thumbnail_url, :duration, :display_order, :is_active)
        ");
        $stmt->execute([
            ':title' => $title, ':description' => $description, ':video_url' => $video_url,
            ':thumbnail_url' => $thumbnail_url, ':duration' => $duration,
            ':display_order' => $display_order, ':is_active' => $is_active
        ]);
    }
    
    header('Location: videos.php?saved=1');
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM videos WHERE id = :id");
    $stmt->execute([':id' => $id]);
    
    header('Location: videos.php?deleted=1');
    exit;
}

// Handle toggle active
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $db->prepare("UPDATE videos SET is_active = NOT is_active WHERE id = :id");
    $stmt->execute([':id' => $id]);
    
    header('Location: videos.php');
    exit;
}

// Get video for editing
$edit_video = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM videos WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $edit_video = $stmt->fetch();
}

// Get all videos
$stmt = $db->query("SELECT * FROM videos ORDER BY display_order ASC, created_at DESC");
$videos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление видео - Админ Панель</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin-style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="page-header">
                <h1>🎬 Управление видео</h1>
                <button class="btn btn-primary" onclick="document.getElementById('videoModal').style.display='flex'">
                    <i class="fas fa-plus"></i> Добавить видео
                </button>
            </div>
            
            <?php if (isset($_GET['saved'])): ?>
                <div class="alert alert-success">✅ Видео успешно сохранено!</div>
            <?php endif; ?>
            
            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success">✅ Видео удалено!</div>
            <?php endif; ?>
            
            <!-- Videos Grid -->
            <div class="videos-grid">
                <?php foreach ($videos as $video): ?>
                    <div class="video-card <?= $video['is_active'] ? '' : 'inactive' ?>">
                        <div class="video-preview">
                            <?php if ($video['thumbnail_url']): ?>
                                <img src="<?= htmlspecialchars($video['thumbnail_url']) ?>" alt="<?= htmlspecialchars($video['title']) ?>">
                            <?php else: ?>
                                <div class="video-placeholder">
                                    <i class="fas fa-video"></i>
                                </div>
                            <?php endif; ?>
                            <div class="video-overlay">
                                <a href="<?= htmlspecialchars($video['video_url']) ?>" target="_blank" class="btn-icon btn-primary">
                                    <i class="fas fa-play"></i>
                                </a>
                            </div>
                            <?php if (!$video['is_active']): ?>
                                <div class="inactive-badge">Неактивно</div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="video-info">
                            <h3><?= htmlspecialchars($video['title']) ?></h3>
                            <p><?= htmlspecialchars($video['description']) ?></p>
                            
                            <div class="video-stats">
                                <span><i class="fas fa-eye"></i> <?= number_format($video['views']) ?></span>
                                <span><i class="fas fa-sort"></i> Позиция: <?= $video['display_order'] ?></span>
                            </div>
                        </div>
                        
                        <div class="video-actions">
                            <button onclick="editVideo(<?= $video['id'] ?>)" class="btn-icon btn-primary" title="Редактировать">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="?toggle=<?= $video['id'] ?>" class="btn-icon btn-warning" title="<?= $video['is_active'] ? 'Деактивировать' : 'Активировать' ?>">
                                <i class="fas fa-<?= $video['is_active'] ? 'eye-slash' : 'eye' ?>"></i>
                            </a>
                            <a href="?delete=<?= $video['id'] ?>" class="btn-icon btn-danger" title="Удалить" onclick="return confirm('Удалить видео?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($videos)): ?>
                    <div class="empty-state">
                        <i class="fas fa-video"></i>
                        <p>Нет видео. Добавьте первое видео!</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- Video Modal -->
    <div id="videoModal" class="modal" style="display: <?= $edit_video ? 'flex' : 'none' ?>">
        <div class="modal-content">
            <div class="modal-header">
                <h2><?= $edit_video ? 'Редактировать видео' : 'Добавить видео' ?></h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            
            <form method="POST" class="video-form">
                <?php if ($edit_video): ?>
                    <input type="hidden" name="id" value="<?= $edit_video['id'] ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="title">Название видео *</label>
                    <input type="text" id="title" name="title" required value="<?= htmlspecialchars($edit_video['title'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="description">Описание</label>
                    <textarea id="description" name="description" rows="3"><?= htmlspecialchars($edit_video['description'] ?? '') ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="video_url">URL видео *</label>
                    <input type="url" id="video_url" name="video_url" required value="<?= htmlspecialchars($edit_video['video_url'] ?? '') ?>" placeholder="https://example.com/video-url">
                    <small>Прямая ссылка на видеофайл (MP4)</small>
                </div>
                
                <div class="form-group">
                    <label for="thumbnail_url">URL превью (опционально)</label>
                    <input type="url" id="thumbnail_url" name="thumbnail_url" value="<?= htmlspecialchars($edit_video['thumbnail_url'] ?? '') ?>" placeholder="https://example.com/thumbnail.jpg">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="duration">Длительность (сек)</label>
                        <input type="number" id="duration" name="duration" value="<?= $edit_video['duration'] ?? 0 ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="display_order">Порядок отображения</label>
                        <input type="number" id="display_order" name="display_order" value="<?= $edit_video['display_order'] ?? 0 ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" <?= ($edit_video['is_active'] ?? 1) ? 'checked' : '' ?>>
                        <span>Активно (показывать на сайте)</span>
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="save_video" class="btn btn-primary">
                        <i class="fas fa-save"></i> Сохранить
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Отмена</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function editVideo(id) {
            window.location.href = 'videos.php?edit=' + id;
        }
        
        function closeModal() {
            document.getElementById('videoModal').style.display = 'none';
            if (window.location.search.includes('edit=')) {
                window.location.href = 'videos.php';
            }
        }
        
        // Close modal on outside click
        window.onclick = function(event) {
            const modal = document.getElementById('videoModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
