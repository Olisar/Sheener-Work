/* File: sheener/sql/schema_consolidation_migration.sql */
-- ============================================================================
-- Schema Consolidation Migration Script
-- ============================================================================
-- This script consolidates user/login data and unifies risk/hazard registers
-- 
-- Part 1: User/Login Consolidation
--   - Drops redundant 'users' table
--   - Renames 'personalinformation' to 'user_accounts'
--   - Redirects foreign keys to 'people' table
--
-- Part 2: Risk/Hazard Unification
--   - Redirects 'controls' table to use 'risk_register'
--   - Redirects 'process_map_risk' to use 'risk_register'
--   - Drops redundant 'risks' and 'hazards' tables
--
-- IMPORTANT: Backup your database before running this script!
-- Run this script in a transaction to allow rollback on errors
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

START TRANSACTION;

-- ============================================================================
-- PART 1: USER/LOGIN CONSOLIDATION
-- ============================================================================

-- Step A: Drop Redundant Table
-- Eliminates the duplicate users table, which is superseded by personalinformation
DROP TABLE IF EXISTS `users`;

-- Step B: Rename Auth Table (Clarity)
-- Renames the surviving authentication table for better clarity
-- Note: If table doesn't exist, this will fail gracefully in transaction
RENAME TABLE `personalinformation` TO `user_accounts`;

-- Step C: Redirect Foreign Keys
-- Redirect foreign key references away from the now-dropped/renamed authentication 
-- tables and toward the core people table

-- C.1: Redirect permit_responsibles foreign key
-- Drop old foreign key constraint (check if exists first)
SET @fk_exists = (
    SELECT COUNT(*) 
    FROM information_schema.TABLE_CONSTRAINTS 
    WHERE CONSTRAINT_SCHEMA = DATABASE()
        AND TABLE_NAME = 'permit_responsibles'
        AND CONSTRAINT_NAME = 'fk_responsible_user'
        AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @sql = IF(@fk_exists > 0,
    'ALTER TABLE `permit_responsibles` DROP FOREIGN KEY `fk_responsible_user`',
    'SELECT "Foreign key fk_responsible_user does not exist, skipping drop" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add new foreign key constraint pointing to people table
ALTER TABLE `permit_responsibles` 
    ADD CONSTRAINT `fk_responsible_people` 
    FOREIGN KEY (`person_id`) 
    REFERENCES `people`(`people_id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE;

-- C.2: Redirect permit_energies foreign key
-- Drop old foreign key constraint (check if exists first)
SET @fk_exists = (
    SELECT COUNT(*) 
    FROM information_schema.TABLE_CONSTRAINTS 
    WHERE CONSTRAINT_SCHEMA = DATABASE()
        AND TABLE_NAME = 'permit_energies'
        AND CONSTRAINT_NAME = 'fk_energy_verifier_users'
        AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @sql = IF(@fk_exists > 0,
    'ALTER TABLE `permit_energies` DROP FOREIGN KEY `fk_energy_verifier_users`',
    'SELECT "Foreign key fk_energy_verifier_users does not exist, skipping drop" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add new foreign key constraint pointing to people table
ALTER TABLE `permit_energies` 
    ADD CONSTRAINT `fk_energy_verifier_people` 
    FOREIGN KEY (`isolation_verified_by`) 
    REFERENCES `people`(`people_id`) 
    ON DELETE SET NULL 
    ON UPDATE CASCADE;

-- ============================================================================
-- PART 2: RISK/HAZARD UNIFICATION
-- ============================================================================

-- Step A: Redirect Controls Table
-- Redirects the controls table to use the central risk_register table,
-- completely abandoning the simpler risks table for controls tracking

-- Drop old foreign key constraints from controls table (check if exists first)
SET @fk_exists = (
    SELECT COUNT(*) 
    FROM information_schema.TABLE_CONSTRAINTS 
    WHERE CONSTRAINT_SCHEMA = DATABASE()
        AND TABLE_NAME = 'controls'
        AND CONSTRAINT_NAME = 'controls_ibfk_1'
        AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @sql = IF(@fk_exists > 0,
    'ALTER TABLE `controls` DROP FOREIGN KEY `controls_ibfk_1`',
    'SELECT "Foreign key controls_ibfk_1 does not exist, skipping drop" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists = (
    SELECT COUNT(*) 
    FROM information_schema.TABLE_CONSTRAINTS 
    WHERE CONSTRAINT_SCHEMA = DATABASE()
        AND TABLE_NAME = 'controls'
        AND CONSTRAINT_NAME = 'fk_controls_risk'
        AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @sql = IF(@fk_exists > 0,
    'ALTER TABLE `controls` DROP FOREIGN KEY `fk_controls_risk`',
    'SELECT "Foreign key fk_controls_risk does not exist, skipping drop" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add new foreign key constraint pointing to risk_register
ALTER TABLE `controls` 
    ADD CONSTRAINT `fk_controls_master_risk` 
    FOREIGN KEY (`risk_id`) 
    REFERENCES `risk_register`(`risk_id`) 
    ON DELETE CASCADE;

-- Step B: Redirect Process Map Risk
-- Redirects process-level risk mapping (process_map_risk) to the master risk_register

-- Drop old foreign key constraint (check if exists first)
SET @fk_exists = (
    SELECT COUNT(*) 
    FROM information_schema.TABLE_CONSTRAINTS 
    WHERE CONSTRAINT_SCHEMA = DATABASE()
        AND TABLE_NAME = 'process_map_risk'
        AND CONSTRAINT_NAME = 'process_map_risk_ibfk_2'
        AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @sql = IF(@fk_exists > 0,
    'ALTER TABLE `process_map_risk` DROP FOREIGN KEY `process_map_risk_ibfk_2`',
    'SELECT "Foreign key process_map_risk_ibfk_2 does not exist, skipping drop" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add new foreign key constraint pointing to risk_register
ALTER TABLE `process_map_risk` 
    ADD CONSTRAINT `fk_process_map_master_risk` 
    FOREIGN KEY (`risk_id`) 
    REFERENCES `risk_register`(`risk_id`) 
    ON DELETE CASCADE;

-- Step C: Drop Redundant Tables
-- Eliminates the simpler tables, relying entirely on the comprehensive risk_register
-- and associated tables like hira_hazard_links for hazard classification

-- Drop risks table (superseded by risk_register)
DROP TABLE IF EXISTS `risks`;

-- Drop hazards table (hazard data now managed through risk_register and hira_hazard_links)
DROP TABLE IF EXISTS `hazards`;

-- ============================================================================
-- COMMIT TRANSACTION
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 1;

-- Review changes before committing
-- If everything looks good, uncomment the COMMIT line below
-- If there are issues, use ROLLBACK instead

-- COMMIT;
-- ROLLBACK;  -- Use this if you need to undo changes

-- ============================================================================
-- VERIFICATION QUERIES
-- ============================================================================
-- Run these queries after committing to verify the changes:

-- Verify user_accounts table exists
-- SELECT COUNT(*) as user_accounts_count FROM user_accounts;

-- Verify users table is dropped
-- SHOW TABLES LIKE 'users';

-- Verify foreign keys are redirected correctly
-- SELECT 
--     TABLE_NAME,
--     CONSTRAINT_NAME,
--     REFERENCED_TABLE_NAME,
--     REFERENCED_COLUMN_NAME
-- FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
-- WHERE TABLE_SCHEMA = DATABASE()
--     AND TABLE_NAME IN ('permit_responsibles', 'permit_energies', 'controls', 'process_map_risk')
--     AND REFERENCED_TABLE_NAME IS NOT NULL
-- ORDER BY TABLE_NAME, CONSTRAINT_NAME;

-- Verify risks and hazards tables are dropped
-- SHOW TABLES LIKE 'risks';
-- SHOW TABLES LIKE 'hazards';

