-- Migration: Add group_id column to orders table
-- This allows orders to be associated with groups

USE orderbook;

-- Add group_id column to orders table
ALTER TABLE orders 
ADD COLUMN group_id INT NULL AFTER user_id,
ADD INDEX idx_group_id (group_id),
ADD FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE SET NULL;

-- Note: group_id is nullable to allow orders without groups (backward compatibility)

