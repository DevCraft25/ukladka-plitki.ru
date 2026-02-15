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
    // Read raw input and try to decode JSON
    $rawBody = file_get_contents('php://input');
    $data = json_decode($rawBody, true);

    // Fallback: if JSON parsing failed, try standard POST data
    if (!is_array($data)) {
        $data = $_POST ?? [];
    }

    $rawName = isset($data['name']) ? trim($data['name']) : '';
    $rawPhone = isset($data['phone']) ? trim($data['phone']) : '';

    $name = sanitize($rawName);
    $phone = sanitize($rawPhone);
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
    $subjectText = "Новая заявка - ELITE TILER";

    $safeName = htmlspecialchars((string)$name, ENT_QUOTES, 'UTF-8');
    $safePhone = htmlspecialchars((string)$phone, ENT_QUOTES, 'UTF-8');
    $safeEmail = $email ? htmlspecialchars((string)$email, ENT_QUOTES, 'UTF-8') : null;
    $safeMessage = $message ? nl2br(htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8')) : null;

    $dateStr = date('d.m.Y H:i');
    $body = "<html><head><title>Новая заявка</title></head><body>" .
        "<h2>Новая заявка с сайта!</h2>" .
        "<p><strong>Имя:</strong> {$safeName}</p>" .
        "<p><strong>Телефон:</strong> {$safePhone}</p>" .
        "<p><strong>Email:</strong> " . ($safeEmail ?: 'Не указан') . "</p>" .
        "<p><strong>Сообщение:</strong> " . ($safeMessage ?: 'Нет сообщения') . "</p>" .
        "<p><strong>Дата:</strong> {$dateStr}</p>" .
        "</body></html>";

    $fromName = 'ELITE TILER';
    $host = parse_url(SITE_URL, PHP_URL_HOST);
    if (!$host) {
        $host = $_SERVER['SERVER_NAME'] ?? 'example.com';
    }
    if (function_exists('idn_to_ascii')) {
        $asciiHost = idn_to_ascii($host, 0, defined('INTL_IDNA_VARIANT_UTS46') ? INTL_IDNA_VARIANT_UTS46 : 0);
        if (!empty($asciiHost)) {
            $host = $asciiHost;
        }
    }

    $fromEmail = 'noreply@' . $host;

    if (defined('SMTP_FROM_EMAIL') && !empty(SMTP_FROM_EMAIL)) {
        $fromEmail = SMTP_FROM_EMAIL;
    }

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= 'From: ' . mb_encode_mimeheader($fromName, 'UTF-8') . " <{$fromEmail}>\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $headers .= "Reply-To: {$email}\r\n";
    }

    $subject = mb_encode_mimeheader($subjectText, 'UTF-8');
    $sent = false;

    if (defined('SMTP_ENABLED') && SMTP_ENABLED && defined('SMTP_HOST') && !empty(SMTP_HOST) && defined('SMTP_USERNAME') && !empty(SMTP_USERNAME)) {
        $sent = smtpSendHtmlMail([
            'host' => SMTP_HOST,
            'port' => defined('SMTP_PORT') ? SMTP_PORT : 587,
            'encryption' => defined('SMTP_ENCRYPTION') ? SMTP_ENCRYPTION : 'tls',
            'username' => SMTP_USERNAME,
            'password' => defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '',
        ], $to, $subject, $body, $fromEmail, $fromName, (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) ? $email : null);
    }

    if (!$sent) {
        $sent = mail($to, $subject, $body, $headers);
    }
    if (!$sent) {
        error_log('Lead notification email failed to send to ' . $to);
    }
}

function smtpSendHtmlMail($smtp, $to, $subject, $htmlBody, $fromEmail, $fromName, $replyTo = null) {
    $host = (string)($smtp['host'] ?? '');
    $port = (int)($smtp['port'] ?? 587);
    $encryption = (string)($smtp['encryption'] ?? 'tls');
    $username = (string)($smtp['username'] ?? '');
    $password = (string)($smtp['password'] ?? '');

    if ($host === '' || $port <= 0 || $username === '') {
        return false;
    }

    $remote = $host . ':' . $port;
    if ($encryption === 'ssl') {
        $remote = 'ssl://' . $remote;
    }

    $fp = @stream_socket_client($remote, $errno, $errstr, 12, STREAM_CLIENT_CONNECT);
    if (!$fp) {
        error_log('SMTP connect failed: ' . $errstr . ' (' . $errno . ')');
        return false;
    }

    stream_set_timeout($fp, 12);

    $expect = function ($codes) use ($fp) {
        $data = '';
        while (!feof($fp)) {
            $line = fgets($fp, 515);
            if ($line === false) break;
            $data .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') break;
        }
        $code = (int)substr($data, 0, 3);
        if (is_array($codes)) {
            return in_array($code, $codes, true) ? $data : false;
        }
        return ($code === (int)$codes) ? $data : false;
    };

    $send = function ($cmd) use ($fp) {
        fwrite($fp, $cmd . "\r\n");
    };

    if ($expect([220]) === false) {
        fclose($fp);
        return false;
    }

    $localhost = $_SERVER['SERVER_NAME'] ?? 'localhost';
    $send('EHLO ' . $localhost);
    if ($expect([250]) === false) {
        $send('HELO ' . $localhost);
        if ($expect([250]) === false) {
            fclose($fp);
            return false;
        }
    }

    if ($encryption === 'tls') {
        $send('STARTTLS');
        if ($expect([220]) === false) {
            fclose($fp);
            return false;
        }
        $cryptoOk = @stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        if ($cryptoOk !== true) {
            error_log('SMTP STARTTLS failed');
            fclose($fp);
            return false;
        }
        $send('EHLO ' . $localhost);
        if ($expect([250]) === false) {
            fclose($fp);
            return false;
        }
    }

    $send('AUTH LOGIN');
    if ($expect([334]) === false) {
        fclose($fp);
        return false;
    }
    $send(base64_encode($username));
    if ($expect([334]) === false) {
        fclose($fp);
        return false;
    }
    $send(base64_encode($password));
    if ($expect([235]) === false) {
        fclose($fp);
        return false;
    }

    $send('MAIL FROM:<' . $fromEmail . '>');
    if ($expect([250]) === false) {
        fclose($fp);
        return false;
    }

    $send('RCPT TO:<' . $to . '>');
    if ($expect([250, 251]) === false) {
        fclose($fp);
        return false;
    }

    $send('DATA');
    if ($expect([354]) === false) {
        fclose($fp);
        return false;
    }

    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = 'From: ' . mb_encode_mimeheader($fromName, 'UTF-8') . ' <' . $fromEmail . '>';
    if (!empty($replyTo)) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }
    $headers[] = 'To: <' . $to . '>';
    $headers[] = 'Subject: ' . $subject;

    $data = implode("\r\n", $headers) . "\r\n\r\n" . $htmlBody;
    $data = str_replace(["\r\n.", "\n."], ["\r\n..", "\n.."], $data);
    fwrite($fp, $data . "\r\n.\r\n");

    if ($expect([250]) === false) {
        fclose($fp);
        return false;
    }

    $send('QUIT');
    $expect([221, 250]);
    fclose($fp);
    return true;
}
?>
