<?php
/**
 * Admin Authentication
 */

require_once __DIR__ . '/../config.php';

session_name(ADMIN_SESSION_NAME);
session_start();

/**
 * Check if admin is logged in
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_username']);
}

/**
 * Require admin login
 */
function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

/**
 * Login admin user
 */
function loginAdmin($username, $password) {
    $db = getDB();
    
    $stmt = $db->prepare("
        SELECT id, username, password, email, full_name, role
        FROM admin_users
        WHERE username = :username AND is_active = 1
    ");
    
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        // Login successful
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['admin_role'] = $user['role'];
        $_SESSION['admin_name'] = $user['full_name'];
        
        // Update last login
        $updateStmt = $db->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = :id");
        $updateStmt->execute([':id' => $user['id']]);
        
        return true;
    }
    
    return false;
}

/**
 * Logout admin
 */
function logoutAdmin() {
    session_destroy();
    header('Location: index.php');
    exit;
}

/**
 * Get current admin info
 */
function getCurrentAdmin() {
    if (!isAdminLoggedIn()) {
        return null;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM admin_users WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['admin_id']]);
    
    return $stmt->fetch();
}
?>
