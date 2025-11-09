<?php
/**
 * API Endpoints for Frontend
 * Укладка плитки - Public API
 */

require_once 'config.php';

setCorsHeaders();

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        
        // GET /api.php?action=videos - Get all active videos
        case 'videos':
            if ($method === 'GET') {
                getVideos();
            } else {
                jsonResponse(['error' => 'Method not allowed'], 405);
            }
            break;
        
        // POST /api.php?action=lead - Submit lead form
        case 'lead':
            if ($method === 'POST') {
                submitLead();
            } else {
                jsonResponse(['error' => 'Method not allowed'], 405);
            }
            break;
        
        // POST /api.php?action=video_view - Increment video views
        case 'video_view':
            if ($method === 'POST') {
                incrementVideoView();
            } else {
                jsonResponse(['error' => 'Method not allowed'], 405);
            }
            break;
        
        default:
            jsonResponse(['error' => 'Invalid action'], 400);
    }
    
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    jsonResponse(['error' => 'Internal server error'], 500);
}

/**
 * Get all active videos
 */
function getVideos() {
    $db = getDB();
    
    $stmt = $db->prepare("
        SELECT id, title, description, video_url, thumbnail_url, duration, views, display_order
        FROM videos
        WHERE is_active = 1
        ORDER BY display_order ASC, created_at DESC
    ");
    
    $stmt->execute();
    $videos = $stmt->fetchAll();
    
    jsonResponse([
        'success' => true,
        'count' => count($videos),
        'videos' => $videos
    ]);
}

/**
 * Submit lead (zayavka)
 */
function submitLead() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (empty($data['name']) || empty($data['phone'])) {
        jsonResponse(['error' => 'Name and phone are required'], 400);
    }
    
    $name = sanitize($data['name']);
    $phone = sanitize($data['phone']);
    $email = !empty($data['email']) ? sanitize($data['email']) : null;
    $message = !empty($data['message']) ? sanitize($data['message']) : null;
    $source = sanitize($data['source'] ?? 'website');
    
    // Get client info
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $db = getDB();
    
    $stmt = $db->prepare("
        INSERT INTO leads (name, phone, email, message, source, ip_address, user_agent)
        VALUES (:name, :phone, :email, :message, :source, :ip_address, :user_agent)
    ");
    
    $stmt->execute([
        ':name' => $name,
        ':phone' => $phone,
        ':email' => $email,
        ':message' => $message,
        ':source' => $source,
        ':ip_address' => $ipAddress,
        ':user_agent' => $userAgent
    ]);
    
    $leadId = $db->lastInsertId();
    
    // Send email notification (optional)
    sendLeadNotification($name, $phone, $email, $message);
    
    jsonResponse([
        'success' => true,
        'message' => 'Заявка успешно отправлена!',
        'lead_id' => $leadId
    ]);
}

/**
 * Increment video view count
 */
function incrementVideoView() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['video_id'])) {
        jsonResponse(['error' => 'Video ID required'], 400);
    }
    
    $videoId = (int)$data['video_id'];
    $db = getDB();
    
    $stmt = $db->prepare("UPDATE videos SET views = views + 1 WHERE id = :id");
    $stmt->execute([':id' => $videoId]);
    
    jsonResponse(['success' => true]);
}

/**
 * Send email notification for new lead
 */
function sendLeadNotification($name, $phone, $email, $message) {
    $to = ADMIN_EMAIL;
    $subject = "Новая заявка - Укладка плитки";
    
    $body = "
        <html>
        <head><title>Новая заявка</title></head>
        <body>
            <h2>Новая заявка с сайта!</h2>
            <p><strong>Имя:</strong> $name</p>
            <p><strong>Телефон:</strong> $phone</p>
            <p><strong>Email:</strong> " . ($email ?: 'Не указан') . "</p>
            <p><strong>Сообщение:</strong> " . ($message ?: 'Нет сообщения') . "</p>
            <p><strong>Дата:</strong> " . date('d.m.Y H:i') . "</p>
        </body>
        </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: noreply@укладка-плитки.рф" . "\r\n";
    
    @mail($to, $subject, $body, $headers);
}
?>
