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

