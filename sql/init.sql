-- ============================================================
-- Randevu Sistemi — Database Schema
-- MySQL 5.7+ / MariaDB 10.3+
-- ============================================================

CREATE DATABASE IF NOT EXISTS randevu_sistemi DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE randevu_sistemi;

-- -----------------------------------------------------------
-- Admin Users
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- Branches
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS branches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- Services
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    branch_id INT DEFAULT NULL,
    name VARCHAR(100) NOT NULL,
    duration INT NOT NULL COMMENT 'Minutes',
    price DECIMAL(10,2) NOT NULL,
    description TEXT DEFAULT NULL,
    requires_payment TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- Employees
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    branch_id INT DEFAULT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NULL,
    phone VARCHAR(20) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS employee_hours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    day_of_week TINYINT NOT NULL,
    is_open TINYINT(1) DEFAULT 1,
    open_time TIME DEFAULT '09:00',
    close_time TIME DEFAULT '18:00',
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- Working Hours
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS working_hours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    branch_id INT DEFAULT NULL,
    day_of_week TINYINT NOT NULL,
    is_open TINYINT(1) DEFAULT 1,
    open_time TIME DEFAULT '09:00',
    close_time TIME DEFAULT '18:00',
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- Break Times
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS break_times (
    id INT AUTO_INCREMENT PRIMARY KEY,
    branch_id INT DEFAULT NULL,
    employee_id INT DEFAULT NULL,
    day_of_week TINYINT NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    label VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- Customers
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NULL,
    phone VARCHAR(20) NOT NULL,
    total_appointments INT DEFAULT 0,
    total_spent DECIMAL(10,2) DEFAULT 0,
    loyalty_points INT DEFAULT 0,
    notes TEXT,
    last_visit DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_email (email)
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- Appointments
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    branch_id INT DEFAULT NULL,
    service_id INT NOT NULL,
    employee_id INT DEFAULT NULL,
    customer_id INT DEFAULT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    duration INT DEFAULT NULL,
    price DECIMAL(10,2) DEFAULT NULL,
    notes TEXT,
    status ENUM('pending','confirmed','cancelled','completed') DEFAULT 'pending',
    token VARCHAR(64) DEFAULT NULL,
    cancel_token VARCHAR(64) DEFAULT NULL,
    checked_in_at DATETIME DEFAULT NULL,
    payment_status ENUM('pending','paid','refunded') DEFAULT 'pending',
    payment_id VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    INDEX idx_employee (employee_id),
    INDEX idx_customer (customer_id)
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- Appointment Series (Recurring)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS appointment_series (
    id INT AUTO_INCREMENT PRIMARY KEY,
    branch_id INT DEFAULT NULL,
    service_id INT NOT NULL,
    employee_id INT DEFAULT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    appointment_time TIME NOT NULL,
    frequency ENUM('weekly','biweekly','monthly') NOT NULL,
    day_of_week TINYINT DEFAULT NULL,
    day_of_month TINYINT DEFAULT NULL,
    start_date DATE NOT NULL,
    end_date DATE DEFAULT NULL,
    notes TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- Reviews (Mood Tracker)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    customer_id INT DEFAULT NULL,
    mood_score TINYINT NOT NULL CHECK (mood_score BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- Packages
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    total_sessions INT NOT NULL DEFAULT 1,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    valid_days INT DEFAULT 365,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS package_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    package_id INT NOT NULL,
    service_id INT NOT NULL,
    sessions INT NOT NULL DEFAULT 1,
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS customer_packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    package_id INT NOT NULL,
    sessions_used INT DEFAULT 0,
    sessions_total INT NOT NULL,
    purchased_price DECIMAL(10,2) NOT NULL,
    expires_at DATE DEFAULT NULL,
    status ENUM('active','expired','cancelled') DEFAULT 'active',
    purchased_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS package_usages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_package_id INT NOT NULL,
    appointment_id INT NOT NULL,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_package_id) REFERENCES customer_packages(id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- Settings
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- Security
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS security_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip (ip),
    INDEX idx_event (event_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) NOT NULL,
    username VARCHAR(100) NOT NULL,
    success TINYINT(1) DEFAULT 0,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip (ip),
    INDEX idx_attempted (attempted_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) NOT NULL,
    endpoint VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_endpoint (ip, endpoint),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS blocked_ips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) NOT NULL UNIQUE,
    reason VARCHAR(255) DEFAULT NULL,
    blocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME DEFAULT NULL,
    INDEX idx_ip (ip),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB;

-- ============================================================
-- Seed Data
-- ============================================================

-- Default admin user (password: admin123)
INSERT INTO admin_users (username, password_hash) VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON DUPLICATE KEY UPDATE username = username;

-- Default branch
INSERT INTO branches (name, address, phone) VALUES ('Merkez Şube', 'Ana Cadde No:1', '02121112233')
ON DUPLICATE KEY UPDATE name = name;

-- Default services
INSERT INTO services (name, duration, price, requires_payment) VALUES
    ('Saç Kesimi', 30, 150.00, 0),
    ('Sakal Tıraşı', 20, 80.00, 0),
    ('Saç + Sakal', 45, 200.00, 0),
    ('Cilt Bakımı', 45, 250.00, 1),
    ('Saç Boyama', 60, 350.00, 1);

-- Default working hours (Mon-Sat)
INSERT INTO working_hours (day_of_week, is_open, open_time, close_time) VALUES
    (0, 0, NULL, NULL),
    (1, 1, '09:00', '18:00'),
    (2, 1, '09:00', '18:00'),
    (3, 1, '09:00', '18:00'),
    (4, 1, '09:00', '18:00'),
    (5, 1, '09:00', '18:00'),
    (6, 1, '10:00', '16:00');

-- Default employees
INSERT INTO employees (name, email, phone) VALUES
    ('Ahmet Yılmaz', 'ahmet@example.com', '05321112233'),
    ('Ayşe Demir', 'ayse@example.com', '05332223344');

-- Default employee hours (copied from working_hours)
INSERT INTO employee_hours (employee_id, day_of_week, is_open, open_time, close_time)
    SELECT e.id, wh.day_of_week, wh.is_open, wh.open_time, wh.close_time
    FROM employees e CROSS JOIN working_hours wh;

-- Default settings
INSERT INTO settings (setting_key, setting_value) VALUES
    ('mail_smtp_host', 'smtp.gmail.com'),
    ('mail_smtp_port', '587'),
    ('mail_smtp_secure', 'tls'),
    ('mail_smtp_auth', '1'),
    ('mail_smtp_username', ''),
    ('mail_smtp_password', ''),
    ('mail_from_email', ''),
    ('mail_from_name', 'Randevu Sistemi'),
    ('sms_provider', 'log'),
    ('sms_twilio_sid', ''),
    ('sms_twilio_token', ''),
    ('sms_twilio_from', ''),
    ('sms_netgsm_user', ''),
    ('sms_netgsm_pass', ''),
    ('sms_netgsm_msgheader', ''),
    ('payment_provider', 'iyzico'),
    ('payment_iyzico_api_key', 'sandbox-'),
    ('payment_iyzico_secret_key', 'sandbox-'),
    ('payment_iyzico_sandbox', '1'),
    ('payment_iyzico_base_url', 'https://sandbox-api.iyzipay.com'),
    ('app_language', 'tr'),
    ('app_language_auto', '1'),
    ('app_short_name', 'Randevu'),
    ('app_theme_color', '#4F46E5'),
    ('app_bg_color', '#F9FAFB'),
    ('api_enabled', '1'),
    ('api_key', ''),
    ('backup_auto_enabled', '0'),
    ('backup_frequency', 'daily'),
    ('backup_retention', '7'),
    ('last_backup', ''),
    ('last_backup_file', ''),
    ('mysqldump_path', ''),
    ('sentry_dsn', ''),
    ('error_log_level', '2'),
    ('cache_type', 'file'),
    ('cache_server', '127.0.0.1'),
    ('cache_port', '6379'),
    ('cache_prefix', 'randv_'),
    ('cache_ttl', '3600');
