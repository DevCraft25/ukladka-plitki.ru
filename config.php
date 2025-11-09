<?php
/**
 * SprintHost Database Configuration
 * Укладка плитки - Backend Config
 */

// SprintHost database credentials
// ВАЖНО: Замените эти данные на ваши реальные данные из SprintHost панели
define('DB_HOST', 'localhost'); // Обычно localhost на SprintHost
define('DB_NAME', 'your_database_name'); // Имя базы данных из SprintHost
define('DB_USER', 'your_database_user'); // Пользователь БД из SprintHost
define('DB_PASS', 'your_database_password'); // Пароль БД из SprintHost
define('DB_CHARSET', 'utf8mb4');

// Site settings
define('SITE_URL', 'https://укладка-плитки.рф');
define('ADMIN_EMAIL', 'admin@укладка-плитки.рф');

// Security
define('ADMIN_SESSION_NAME', 'ukladka_admin_session');
define('ADMIN_SESSION_LIFETIME', 3600 * 8); // 8 hours

// Error reporting (set to 0 on production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Скрыть ошибки на продакшене
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php-errors.log');

// Timezone
date_default_timezone_set('Asia/Tashkent');

// Database Connection Class
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            die("Database connection failed. Please contact administrator.");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Helper function to get database connection
function getDB() {
    return Database::getInstance()->getConnection();
}

// Helper function for JSON response
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Helper function to sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// CORS headers for API
function setCorsHeaders() {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}
?>
