/* File: sheener/database_migrations/fix_investigations_foreign_key.sql */
-- Fix investigations table foreign key to reference events table instead of operational_events
-- Run this SQL script to update the foreign key constraint

-- Step 1: Drop the existing foreign key constraint
ALTER TABLE `investigations` 
DROP FOREIGN KEY `fk_investigation_event`;

-- Step 2: Add new foreign key constraint pointing to events table
ALTER TABLE `investigations` 
ADD CONSTRAINT `fk_investigation_event` 
FOREIGN KEY (`event_id`) 
REFERENCES `events` (`event_id`) 
ON DELETE CASCADE;

-- Verify the change
-- SELECT 
--     CONSTRAINT_NAME,
--     TABLE_NAME,
--     REFERENCED_TABLE_NAME,
--     REFERENCED_COLUMN_NAME
-- FROM information_schema.KEY_COLUMN_USAGE
-- WHERE TABLE_SCHEMA = 'sheener'
--   AND TABLE_NAME = 'investigations'
--   AND CONSTRAINT_NAME = 'fk_investigation_event';

