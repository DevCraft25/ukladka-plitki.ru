-- SprintHost MySQL Database Schema
-- Укладка плитки - Video Gallery & Leads System

-- 1. Videos table - Video malumotlari
CREATE TABLE IF NOT EXISTS videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    video_url VARCHAR(500) NOT NULL,
    thumbnail_url VARCHAR(500),
    duration INT DEFAULT 0,
    views INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Leads table - Client zayavkalari
CREATE TABLE IF NOT EXISTS leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    email VARCHAR(255),
    message TEXT,
    source VARCHAR(100) DEFAULT 'website',
    status ENUM('new', 'contacted', 'in_progress', 'completed', 'cancelled') DEFAULT 'new',
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Admin users table - Admin panel foydalanuvchilari
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    full_name VARCHAR(255),
    role ENUM('admin', 'manager') DEFAULT 'manager',
    is_active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Settings table - Sayt sozlamalari
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type VARCHAR(50) DEFAULT 'text',
    description VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default admin user - Username: admin, Password: admin123 (CHANGE THIS!)
INSERT INTO admin_users (username, password, email, full_name, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@ukladka-plitki.rf', 'Administrator', 'admin');

-- Default settings
INSERT INTO settings (setting_key, setting_value, setting_type, description) VALUES
('site_phone', '+7 (999) 123-45-67', 'text', 'Телефон компании'),
('site_email', 'info@укладка-плитки.рф', 'text', 'Email компании'),
('whatsapp_number', '79991234567', 'text', 'WhatsApp номер'),
('telegram_username', 'ukladka_plitki', 'text', 'Telegram username');

-- Sample videos
INSERT INTO videos (title, description, video_url, display_order, is_active) VALUES
('Укладка керамогранита премиум', 'Премиальные материалы • Профессиональный инструмент', 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4', 1, 1),
('Натуральный мрамор', 'Эксклюзивная работа с мрамором. Идеальные швы и полировка', 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4', 2, 1),
('Мозаика для ванной', 'Сложная мозаичная укладка. Водостойкие материалы премиум класса', 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4', 3, 1),
('Теплый пол', 'Монтаж теплого пола под плитку. Современные технологии комфорта', 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4', 4, 1),
('Дизайнерская плитка', 'Уникальные дизайнерские решения. От эскиза до реализации', 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerFun.mp4', 5, 1),
('Кухонный фартук элит', 'От проекта до результата. Натуральный камень с LED-подсветкой', 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerJoyrides.mp4', 6, 1);
