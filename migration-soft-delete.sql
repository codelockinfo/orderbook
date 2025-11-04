-- Migration: Add Soft Delete to Existing Database
-- Run this ONLY if you already have an existing orderbook database
-- This adds the is_deleted column to your orders table

USE orderbook;

-- Add is_deleted column to orders table
ALTER TABLE orders 
ADD COLUMN is_deleted TINYINT(1) DEFAULT 0 AFTER status,
ADD INDEX idx_is_deleted (is_deleted);

-- Set all existing orders to active (is_deleted = 0)
UPDATE orders SET is_deleted = 0 WHERE is_deleted IS NULL;

-- Verify the change
SELECT 'Migration completed successfully!' as message;
SELECT COUNT(*) as active_orders FROM orders WHERE is_deleted = 0;

