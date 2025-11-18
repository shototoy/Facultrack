-- Add manual_status column to faculty table for manual status override
-- Run this SQL command in your MySQL database to add the manual status feature

ALTER TABLE faculty 
ADD COLUMN manual_status ENUM('available', 'in-meeting') NULL DEFAULT NULL 
COMMENT 'Manual status override - NULL means auto-detect, in-meeting overrides available status';

-- Optional: Add index for better performance
CREATE INDEX idx_faculty_manual_status ON faculty(manual_status);

-- Example usage:
-- UPDATE faculty SET manual_status = 'in-meeting' WHERE faculty_id = 1; -- Set to In Meeting
-- UPDATE faculty SET manual_status = NULL WHERE faculty_id = 1;         -- Reset to auto-detect