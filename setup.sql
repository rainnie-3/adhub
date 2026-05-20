-- ============================================================
-- AdHub — Database Setup Script
-- Run this in phpMyAdmin, MySQL Workbench, or CLI:
--   mysql -u root -p < setup.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS adhub_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE adhub_db;

-- ── Users ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100)  NOT NULL,
    email      VARCHAR(150)  NOT NULL UNIQUE,
    password   VARCHAR(255)  NOT NULL,
    role       ENUM('admin','client') DEFAULT 'client',
    company    VARCHAR(150),
    avatar     VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── Campaigns ─────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS campaigns (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(200) NOT NULL,
    description TEXT,
    client_id   INT NOT NULL,
    status      ENUM('draft','active','review','completed','paused') DEFAULT 'draft',
    budget      DECIMAL(12,2) DEFAULT 0.00,
    start_date  DATE,
    end_date    DATE,
    progress    INT DEFAULT 0,
    created_by  INT NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id)  REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── Campaign Assets ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS campaign_assets (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id   INT NOT NULL,
    filename      VARCHAR(255) NOT NULL,
    original_name VARCHAR(255),
    file_type     VARCHAR(100),
    file_size     INT,
    uploaded_by   INT NOT NULL,
    uploaded_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- ── Approvals ─────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS approvals (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    asset_id    INT DEFAULT NULL,
    client_id   INT NOT NULL,
    status      ENUM('pending','approved','revision') DEFAULT 'pending',
    notes       TEXT,
    reviewed_at TIMESTAMP NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id)   REFERENCES users(id)     ON DELETE CASCADE,
    FOREIGN KEY (asset_id)    REFERENCES campaign_assets(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ── Reports ───────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS reports (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    impressions INT DEFAULT 0,
    clicks      INT DEFAULT 0,
    conversions INT DEFAULT 0,
    spend       DECIMAL(12,2) DEFAULT 0.00,
    report_date DATE NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- Demo Seed Data
-- Password for ALL demo users is: password
-- (bcrypt hash of 'password')
-- ============================================================

INSERT INTO users (name, email, password, role, company) VALUES
('Admin User',   'admin@adhub.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin',  'AdHub Agency'),
('Demo Client',  'client@adhub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'Demo Corp'),
('Sarah Johnson', 'sarah@techco.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'TechCo Ltd')
ON DUPLICATE KEY UPDATE id=id;

-- Demo campaigns (client_id=2 = Demo Client, created_by=1 = Admin)
INSERT INTO campaigns (title, description, client_id, status, budget, start_date, end_date, progress, created_by) VALUES
('Q3 Product Launch',     'Full-funnel campaign for the new product line.',       2, 'active',    15000.00, '2026-07-01', '2026-09-30', 65, 1),
('Summer Social Ads',     'Instagram and Facebook paid social for summer promo.', 2, 'review',     8500.00, '2026-06-01', '2026-08-31', 40, 1),
('Email Re-engagement',   'Win-back sequence for dormant subscribers.',           2, 'active',     3200.00, '2026-05-15', '2026-06-30', 80, 1),
('Brand Awareness Push',  'Display network campaign targeting new audiences.',    3, 'draft',     12000.00, '2026-08-01', '2026-10-31', 10, 1),
('Holiday Campaign 2026', 'Multi-channel holiday season campaign.',               2, 'draft',     20000.00, '2026-11-01', '2026-12-31',  0, 1)
ON DUPLICATE KEY UPDATE id=id;

-- Demo approvals
INSERT INTO approvals (campaign_id, client_id, status, notes) VALUES
(1, 2, 'approved', 'Looks great, love the creative direction!'),
(2, 2, 'pending',  NULL),
(3, 2, 'revision', 'Please update the subject line and change CTA to orange.'),
(5, 2, 'pending',  NULL)
ON DUPLICATE KEY UPDATE id=id;

-- Demo report data for analytics charts
INSERT INTO reports (campaign_id, impressions, clicks, conversions, spend, report_date) VALUES
(1, 45000, 1800, 72, 2100.00, '2026-05-01'),
(1, 52000, 2100, 89, 2450.00, '2026-05-08'),
(3, 18000,  720, 55, 650.00,  '2026-05-05'),
(2, 32000, 1280, 48, 1800.00, '2026-06-01'),
(1, 61000, 2440, 98, 2900.00, '2026-04-01'),
(3, 22000,  880, 62, 720.00,  '2026-04-15')
ON DUPLICATE KEY UPDATE id=id;
