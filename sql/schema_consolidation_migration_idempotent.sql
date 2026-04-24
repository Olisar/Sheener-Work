/* File: sheener/sql/schema_consolidation_migration_idempotent.sql */
-- ============================================================================
-- Schema Consolidation Migration Script (Idempotent Version)
-- ============================================================================
-- This version can be run multiple times safely - it checks if changes
-- have already been applied before attempting them
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

START TRANSACTION;

-- ============================================================================
-- PART 1: USER/LOGIN CONSOLIDATION
-- ============================================================================

-- Step A: Drop Redundant Table (only if it exists)
SET @table_exists = (
    SELECT COUNT(*) 
    FROM information_schema.tables 
    WHERE table_schema = DATABASE() 
        AND table_name = 'users'
);

SET @sql = IF(@table_exists > 0,
    'DROP TABLE `users`',
    'SELECT "users table does not exist, skipping drop" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step B: Rename Auth Table (only if personalinformation exists and user_accounts doesn't)
SET @personalinfo_exists = (
    SELECT COUNT(*) 
    FROM information_schema.tables 
    WHERE table_schema = DATABASE() 
        AND table_name = 'personalinformation'
);

SET @user_accounts_exists = (
    SELECT COUNT(*) 
    FROM information_schema.tables 
    WHERE table_schema = DATABASE() 
        AND table_name = 'user_accounts'
);

SET @sql = IF(@personalinfo_exists > 0 AND @user_accounts_exists = 0,
    'RENAME TABLE `personalinformation` TO `user_accounts`',
    'SELECT "personalinformation already renamed or user_accounts already exists, skipping rename" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step C: Redirect Foreign Keys
-- C.1: Redirect permit_responsibles foreign key
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

-- Check if new FK already exists
SET @new_fk_exists = (
    SELECT COUNT(*) 
    FROM information_schema.TABLE_CONSTRAINTS 
    WHERE CONSTRAINT_SCHEMA = DATABASE()
        AND TABLE_NAME = 'permit_responsibles'
        AND CONSTRAINT_NAME = 'fk_responsible_people'
        AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @sql = IF(@new_fk_exists = 0,
    'ALTER TABLE `permit_responsibles` ADD CONSTRAINT `fk_responsible_people` FOREIGN KEY (`person_id`) REFERENCES `people`(`people_id`) ON DELETE CASCADE ON UPDATE CASCADE',
    'SELECT "Foreign key fk_responsible_people already exists, skipping add" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- C.2: Redirect permit_energies foreign key
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

-- Check if new FK already exists
SET @new_fk_exists = (
    SELECT COUNT(*) 
    FROM information_schema.TABLE_CONSTRAINTS 
    WHERE CONSTRAINT_SCHEMA = DATABASE()
        AND TABLE_NAME = 'permit_energies'
        AND CONSTRAINT_NAME = 'fk_energy_verifier_people'
        AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @sql = IF(@new_fk_exists = 0,
    'ALTER TABLE `permit_energies` ADD CONSTRAINT `fk_energy_verifier_people` FOREIGN KEY (`isolation_verified_by`) REFERENCES `people`(`people_id`) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT "Foreign key fk_energy_verifier_people already exists, skipping add" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- PART 2: RISK/HAZARD UNIFICATION
-- ============================================================================

-- Step A: Redirect Controls Table
-- Drop old foreign key constraints (only if they exist)
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

-- Check if new FK already exists
SET @new_fk_exists = (
    SELECT COUNT(*) 
    FROM information_schema.TABLE_CONSTRAINTS 
    WHERE CONSTRAINT_SCHEMA = DATABASE()
        AND TABLE_NAME = 'controls'
        AND CONSTRAINT_NAME = 'fk_controls_master_risk'
        AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @sql = IF(@new_fk_exists = 0,
    'ALTER TABLE `controls` ADD CONSTRAINT `fk_controls_master_risk` FOREIGN KEY (`risk_id`) REFERENCES `risk_register`(`risk_id`) ON DELETE CASCADE',
    'SELECT "Foreign key fk_controls_master_risk already exists, skipping add" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step B: Redirect Process Map Risk
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

-- Check if new FK already exists
SET @new_fk_exists = (
    SELECT COUNT(*) 
    FROM information_schema.TABLE_CONSTRAINTS 
    WHERE CONSTRAINT_SCHEMA = DATABASE()
        AND TABLE_NAME = 'process_map_risk'
        AND CONSTRAINT_NAME = 'fk_process_map_master_risk'
        AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @sql = IF(@new_fk_exists = 0,
    'ALTER TABLE `process_map_risk` ADD CONSTRAINT `fk_process_map_master_risk` FOREIGN KEY (`risk_id`) REFERENCES `risk_register`(`risk_id`) ON DELETE CASCADE',
    'SELECT "Foreign key fk_process_map_master_risk already exists, skipping add" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step C: Drop Redundant Tables (only if they exist)
SET @table_exists = (
    SELECT COUNT(*) 
    FROM information_schema.tables 
    WHERE table_schema = DATABASE() 
        AND table_name = 'risks'
);

SET @sql = IF(@table_exists > 0,
    'DROP TABLE `risks`',
    'SELECT "risks table does not exist, skipping drop" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @table_exists = (
    SELECT COUNT(*) 
    FROM information_schema.tables 
    WHERE table_schema = DATABASE() 
        AND table_name = 'hazards'
);

SET @sql = IF(@table_exists > 0,
    'DROP TABLE `hazards`',
    'SELECT "hazards table does not exist, skipping drop" AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- COMMIT TRANSACTION
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 1;

-- Review changes before committing
-- Uncomment COMMIT when ready, or use ROLLBACK to undo

COMMIT;
-- ROLLBACK;

-- ============================================================================
-- VERIFICATION SUMMARY
-- ============================================================================

SELECT 'Migration completed successfully!' AS status;
SELECT 
    'Verification: Check if all changes were applied' AS message,
    (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'user_accounts') AS user_accounts_exists,
    (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'users') AS users_dropped,
    (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'risks') AS risks_dropped,
    (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'hazards') AS hazards_dropped;

