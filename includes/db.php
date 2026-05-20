<?php
/**
 * AdHub - Database Connection
 * Handles MySQL connection using PDO
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'adhub_db');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // In production, log this error instead of displaying it
    die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
}

/**
 * SQL schema to run once to set up the database.
 * Execute this in phpMyAdmin or MySQL CLI:
 *
 * CREATE DATABASE IF NOT EXISTS adhub_db;
 * USE adhub_db;
 *
 * CREATE TABLE users (
 *   id INT AUTO_INCREMENT PRIMARY KEY,
 *   name VARCHAR(100) NOT NULL,
 *   email VARCHAR(150) NOT NULL UNIQUE,
 *   password VARCHAR(255) NOT NULL,
 *   role ENUM('admin','client') DEFAULT 'client',
 *   company VARCHAR(150),
 *   avatar VARCHAR(255),
 *   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
 * );
 *
 * CREATE TABLE campaigns (
 *   id INT AUTO_INCREMENT PRIMARY KEY,
 *   title VARCHAR(200) NOT NULL,
 *   description TEXT,
 *   client_id INT NOT NULL,
 *   status ENUM('draft','active','review','completed','paused') DEFAULT 'draft',
 *   budget DECIMAL(12,2) DEFAULT 0.00,
 *   start_date DATE,
 *   end_date DATE,
 *   progress INT DEFAULT 0,
 *   created_by INT NOT NULL,
 *   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 *   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 *   FOREIGN KEY (client_id) REFERENCES users(id),
 *   FOREIGN KEY (created_by) REFERENCES users(id)
 * );
 *
 * CREATE TABLE campaign_assets (
 *   id INT AUTO_INCREMENT PRIMARY KEY,
 *   campaign_id INT NOT NULL,
 *   filename VARCHAR(255) NOT NULL,
 *   original_name VARCHAR(255),
 *   file_type VARCHAR(50),
 *   file_size INT,
 *   uploaded_by INT NOT NULL,
 *   uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 *   FOREIGN KEY (campaign_id) REFERENCES campaigns(id),
 *   FOREIGN KEY (uploaded_by) REFERENCES users(id)
 * );
 *
 * CREATE TABLE approvals (
 *   id INT AUTO_INCREMENT PRIMARY KEY,
 *   campaign_id INT NOT NULL,
 *   asset_id INT,
 *   client_id INT NOT NULL,
 *   status ENUM('pending','approved','revision') DEFAULT 'pending',
 *   notes TEXT,
 *   reviewed_at TIMESTAMP NULL,
 *   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 *   FOREIGN KEY (campaign_id) REFERENCES campaigns(id),
 *   FOREIGN KEY (client_id) REFERENCES users(id)
 * );
 *
 * CREATE TABLE reports (
 *   id INT AUTO_INCREMENT PRIMARY KEY,
 *   campaign_id INT NOT NULL,
 *   impressions INT DEFAULT 0,
 *   clicks INT DEFAULT 0,
 *   conversions INT DEFAULT 0,
 *   spend DECIMAL(12,2) DEFAULT 0.00,
 *   report_date DATE NOT NULL,
 *   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 *   FOREIGN KEY (campaign_id) REFERENCES campaigns(id)
 * );
 *
 * -- Demo admin user (password: admin123)
 * INSERT INTO users (name, email, password, role, company) VALUES
 * ('Admin User', 'admin@adhub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'AdHub Agency'),
 * ('Demo Client', 'client@adhub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'Demo Corp');
 */
