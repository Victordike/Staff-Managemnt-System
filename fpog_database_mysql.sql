-- MySQL database dump for FPOG Staff Management System

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Create database
CREATE DATABASE IF NOT EXISTS fpog_sms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fpog_sms;

-- Users table (Super Admin)
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) UNIQUE NOT NULL,
  email VARCHAR(100) UNIQUE,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(50) DEFAULT 'superadmin',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin users table
CREATE TABLE IF NOT EXISTS admin_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  firstname VARCHAR(100) NOT NULL,
  surname VARCHAR(100) NOT NULL,
  email VARCHAR(100) UNIQUE,
  staff_id VARCHAR(50) UNIQUE NOT NULL,
  phone VARCHAR(20),
  position VARCHAR(100),
  department VARCHAR(100),
  password VARCHAR(255) NOT NULL,
  profile_picture VARCHAR(255),
  passport_photo VARCHAR(255),
  employment_status VARCHAR(50),
  employment_date DATE,
  bank_name VARCHAR(100),
  account_number VARCHAR(50),
  account_holder_name VARCHAR(100),
  nok_name VARCHAR(100),
  nok_relationship VARCHAR(50),
  nok_phone VARCHAR(20),
  nok_address TEXT,
  address TEXT,
  state VARCHAR(50),
  lga VARCHAR(50),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin roles table
CREATE TABLE IF NOT EXISTS admin_roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT NOT NULL,
  role_name VARCHAR(100) NOT NULL,
  assigned_by INT,
  assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  removed_at DATETIME,
  FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pre-users table (CSV uploads)
CREATE TABLE IF NOT EXISTS pre_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  staff_id VARCHAR(50) UNIQUE NOT NULL,
  firstname VARCHAR(100),
  surname VARCHAR(100),
  email VARCHAR(100),
  phone VARCHAR(20),
  position VARCHAR(100),
  department VARCHAR(100),
  verified TINYINT(1) DEFAULT 0,
  verified_at DATETIME,
  uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Memos table
CREATE TABLE IF NOT EXISTS memos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255),
  description TEXT,
  file_path VARCHAR(255),
  file_type VARCHAR(50),
  sender_id INT,
  sent_to VARCHAR(50) DEFAULT 'all',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (sender_id) REFERENCES admin_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Memo recipients table (for tracking read status)
CREATE TABLE IF NOT EXISTS memo_recipients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  memo_id INT NOT NULL,
  recipient_id INT NOT NULL,
  is_read TINYINT(1) DEFAULT 0,
  read_at DATETIME,
  is_archived TINYINT(1) DEFAULT 0,
  archived_at DATETIME,
  FOREIGN KEY (memo_id) REFERENCES memos(id) ON DELETE CASCADE,
  FOREIGN KEY (recipient_id) REFERENCES admin_users(id) ON DELETE CASCADE,
  UNIQUE KEY unique_memo_recipient (memo_id, recipient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sessions table
CREATE TABLE IF NOT EXISTS sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  user_type VARCHAR(50),
  session_token VARCHAR(255) UNIQUE,
  ip_address VARCHAR(45),
  user_agent TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME,
  FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data

-- Sample Super Admin user (password: 'Admin@123')
INSERT INTO users (username, email, password, role) VALUES 
('superadmin', 'admin@fpog.edu.ng', '$2y$10$N9qo8uLOickgx2ZMRZoMye4bZ8SkBiPpJGYzS9J5K5rGK2CzVfxQC', 'superadmin')
ON DUPLICATE KEY UPDATE username=VALUES(username);

-- Create indexes for performance
CREATE INDEX idx_staff_id ON admin_users(staff_id);
CREATE INDEX idx_email ON admin_users(email);
CREATE INDEX idx_memo_id ON memo_recipients(memo_id);
CREATE INDEX idx_recipient_id ON memo_recipients(recipient_id);
CREATE INDEX idx_memo_created ON memos(created_at);

