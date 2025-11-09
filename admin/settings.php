<?php
require_once 'auth.php';
requireAdmin();

$db = getDB();

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $value) {
        if ($key !== 'save_settings') {
            $stmt = $db->prepare("
                UPDATE settings 
                SET setting_value = :value 
                WHERE setting_key = :key
            ");
            $stmt->execute([':value' => $value, ':key' => $key]);
        }
    }
    
    header('Location: settings.php?saved=1');
    exit;
}

// Get all settings
$stmt = $db->query("SELECT * FROM settings ORDER BY setting_key");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки - Админ Панель</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin-style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="page-header">
                <h1>⚙️ Настройки сайта</h1>
                <p>Управление контактной информацией</p>
            </div>
            
            <?php if (isset($_GET['saved'])): ?>
                <div class="alert alert-success">✅ Настройки успешно сохранены!</div>
            <?php endif; ?>
            
            <div class="settings-container">
                <form method="POST" class="settings-form">
                    <div class="settings-section">
                        <h2>📞 Контактная информация</h2>
                        
                        <div class="form-group">
                            <label for="site_phone">Телефон компании</label>
                            <input type="tel" id="site_phone" name="site_phone" value="<?= htmlspecialchars($settings['site_phone'] ?? '') ?>">
                            <small>Формат: +7 (999) 123-45-67</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="site_email">Email компании</label>
                            <input type="email" id="site_email" name="site_email" value="<?= htmlspecialchars($settings['site_email'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="settings-section">
                        <h2>💬 Мессенджеры</h2>
                        
                        <div class="form-group">
                            <label for="whatsapp_number">WhatsApp номер</label>
                            <input type="tel" id="whatsapp_number" name="whatsapp_number" value="<?= htmlspecialchars($settings['whatsapp_number'] ?? '') ?>">
                            <small>Только цифры, без + и пробелов: 79991234567</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="telegram_username">Telegram username</label>
                            <div class="input-prefix">
                                <span>@</span>
                                <input type="text" id="telegram_username" name="telegram_username" value="<?= htmlspecialchars($settings['telegram_username'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="save_settings" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Сохранить настройки
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
