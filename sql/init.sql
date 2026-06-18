CREATE DATABASE IF NOT EXISTS randevu_sistemi DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE randevu_sistemi;

CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    duration INT NOT NULL COMMENT 'Dakika cinsinden',
    price DECIMAL(10,2) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO services (name, duration, price) VALUES
('Saç Kesimi', 30, 150.00),
('Sakal Tıraşı', 20, 80.00),
('Saç + Sakal', 45, 200.00),
('Cilt Bakımı', 45, 250.00),
('Saç Boyama', 60, 350.00);

CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    notes TEXT,
    status ENUM('pending','confirmed','cancelled','completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS working_hours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    day_of_week TINYINT NOT NULL COMMENT '0=Pazar, 1=Pazartesi...',
    is_open TINYINT(1) DEFAULT 1,
    open_time TIME DEFAULT '09:00',
    close_time TIME DEFAULT '18:00'
) ENGINE=InnoDB;

INSERT INTO working_hours (day_of_week, is_open, open_time, close_time) VALUES
(0, 0, NULL, NULL),
(1, 1, '09:00', '18:00'),
(2, 1, '09:00', '18:00'),
(3, 1, '09:00', '18:00'),
(4, 1, '09:00', '18:00'),
(5, 1, '09:00', '18:00'),
(6, 1, '10:00', '16:00');
