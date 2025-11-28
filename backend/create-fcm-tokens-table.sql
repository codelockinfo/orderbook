-- ============================================
-- FCM TOKENS TABLE CREATION
-- This table stores FCM (Firebase Cloud Messaging) tokens for mobile app users
-- ============================================

USE orderbook;

-- Create fcm_tokens table if it doesn't exist
CREATE TABLE IF NOT EXISTS fcm_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    fcm_token VARCHAR(255) NOT NULL,
    device_type VARCHAR(50) DEFAULT 'unknown' COMMENT 'e.g., ios, android, web',
    is_active TINYINT(1) DEFAULT 1 COMMENT '1 = active, 0 = inactive',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_token (user_id, fcm_token),
    INDEX idx_user_id (user_id),
    INDEX idx_fcm_token (fcm_token),
    INDEX idx_is_active (is_active),
    INDEX idx_device_type (device_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add comments
ALTER TABLE fcm_tokens 
MODIFY COLUMN device_type VARCHAR(50) DEFAULT 'unknown' COMMENT 'Device type: ios, android, web, etc.';

-- ============================================
-- VERIFICATION
-- ============================================

SELECT '============================================' AS '';
SELECT '✅ FCM TOKENS TABLE CREATED SUCCESSFULLY!' AS 'Status';
SELECT '============================================' AS '';

-- Show table structure
DESCRIBE fcm_tokens;

SELECT '============================================' AS '';
SELECT '🎉 YOUR DATABASE IS NOW READY FOR FCM!' AS 'Result';
SELECT 'Next: Register FCM tokens from your mobile app' AS 'Next Step';
SELECT '============================================' AS '';

