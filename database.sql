-- Order Book Database Schema
-- Create Database
CREATE DATABASE IF NOT EXISTS orderbook;
USE orderbook;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Password Reset Codes Table
CREATE TABLE IF NOT EXISTS password_reset_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    code_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    attempts INT DEFAULT 0,
    used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_password_resets_user_id (user_id),
    INDEX idx_password_resets_expires (expires_at),
    INDEX idx_password_resets_used (used)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Orders Table (Simplified with Soft Delete)
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) NOT NULL,
    order_date DATE NOT NULL,
    order_time TIME NOT NULL,
    status ENUM('Pending', 'Processing', 'Completed', 'Cancelled') DEFAULT 'Pending',
    is_deleted TINYINT(1) DEFAULT 0,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_order_date (order_date),
    INDEX idx_status (status),
    INDEX idx_user_id (user_id),
    INDEX idx_order_number (order_number),
    INDEX idx_is_deleted (is_deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMENT ON COLUMN orders.is_deleted IS '0 = Active (Show), 1 = Deleted (Hidden)';

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password) 
VALUES ('admin', 'admin@orderbook.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert dummy orders for testing
INSERT INTO orders (order_number, order_date, order_time, status, user_id) VALUES
('ORD-2024001', '2024-11-04', '09:30:00', 'Pending', 1),
('ORD-2024002', '2024-11-04', '10:15:00', 'Processing', 1),
('ORD-2024003', '2024-11-04', '14:45:00', 'Completed', 1),
('ORD-2024004', '2024-11-03', '08:00:00', 'Pending', 1),
('ORD-2024005', '2024-11-03', '11:30:00', 'Processing', 1),
('ORD-2024006', '2024-11-03', '15:20:00', 'Cancelled', 1),
('ORD-2024007', '2024-11-02', '09:45:00', 'Completed', 1),
('ORD-2024008', '2024-11-02', '13:00:00', 'Pending', 1),
('ORD-2024009', '2024-11-01', '10:00:00', 'Processing', 1),
('ORD-2024010', '2024-11-01', '16:30:00', 'Completed', 1),
('ORD-2024011', '2024-10-31', '08:30:00', 'Pending', 1),
('ORD-2024012', '2024-10-31', '12:45:00', 'Completed', 1),
('ORD-2024013', '2024-10-30', '09:15:00', 'Processing', 1),
('ORD-2024014', '2024-10-30', '14:00:00', 'Cancelled', 1),
('ORD-2024015', '2024-10-29', '10:30:00', 'Completed', 1);

-- Groups Table
CREATE TABLE IF NOT EXISTS groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Group Members Table
CREATE TABLE IF NOT EXISTS group_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('ADMIN', 'MEMBER') DEFAULT 'MEMBER',
    status ENUM('active', 'pending') DEFAULT 'active',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_group_user (group_id, user_id),
    INDEX idx_group_id (group_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Group Join Requests Table
CREATE TABLE IF NOT EXISTS group_join_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    responded_at TIMESTAMP NULL,
    responded_by INT NULL,
    requested_by INT NULL COMMENT 'User who initiated the request (NULL = user themselves, admin_id = invited by admin)',
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (responded_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_pending_request (group_id, user_id, status),
    INDEX idx_group_id (group_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_requested_by (requested_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

