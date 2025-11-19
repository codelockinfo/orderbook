-- Migration: Add requested_by column to group_join_requests table
-- This column tracks who initiated the request:
-- NULL = user initiated the request themselves
-- admin_id = admin invited the user

USE orderbook;

-- Add requested_by column
ALTER TABLE group_join_requests 
ADD COLUMN requested_by INT NULL COMMENT 'User who initiated the request (NULL = user themselves, admin_id = invited by admin)' AFTER responded_by;

-- Add foreign key constraint
ALTER TABLE group_join_requests 
ADD CONSTRAINT fk_requested_by 
FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE SET NULL;

-- Add index for better query performance
ALTER TABLE group_join_requests 
ADD INDEX idx_requested_by (requested_by);

-- Update existing records: Set requested_by to NULL for all existing requests
-- (assuming they were user-initiated, or you can manually update admin invitations)
UPDATE group_join_requests SET requested_by = NULL WHERE requested_by IS NULL;

