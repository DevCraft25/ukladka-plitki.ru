<?php
// reset_admin_password.php
// ВРЕМЕННЫЙ СКРИПТ! После использования ОБЯЗАТЕЛЬНО УДАЛИТЬ.

require_once __DIR__ . '/config.php';

try {
    $db = getDB();

    // Yangi parolni shu yerda o'zgartirasiz:
    $newPassword = 'Plitochnik2024!'; // BU PAROLNI ESLAB QOLING

    // PHP bcrypt hash
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);

    // admin foydalanuvchining parolini yangilash
    $stmt = $db->prepare("UPDATE admin_users SET password = :password WHERE username = 'admin'");
    $stmt->execute([':password' => $hash]);

    echo 'OK: admin paroli yangilandi. Yangi parol: ' . htmlspecialchars($newPassword);
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage();
}