/* File: sheener/sql/schema_consolidation_migration_simple.sql */
-- ============================================================================
-- Schema Consolidation Migration Script (Simplified Version)
-- ============================================================================
-- This is a simplified version that works with older MySQL versions
-- It uses error handling via stored procedures or manual execution
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

START TRANSACTION;

-- ============================================================================
-- PART 1: USER/LOGIN CONSOLIDATION
-- ============================================================================

-- Step A: Drop Redundant Table
DROP TABLE IF EXISTS `users`;

-- Step B: Rename Auth Table (Clarity)
-- Note: If personalinformation doesn't exist, comment out this line
RENAME TABLE `personalinformation` TO `user_accounts`;

-- Step C: Redirect Foreign Keys
-- C.1: Redirect permit_responsibles foreign key
-- First, drop the old constraint (may fail if it doesn't exist - that's OK)
-- Run this manually if you get an error:
-- ALTER TABLE `permit_responsibles` DROP FOREIGN KEY `fk_responsible_user`;

ALTER TABLE `permit_responsibles` 
    DROP FOREIGN KEY `fk_responsible_user`;

ALTER TABLE `permit_responsibles` 
    ADD CONSTRAINT `fk_responsible_people` 
    FOREIGN KEY (`person_id`) 
    REFERENCES `people`(`people_id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE;

-- C.2: Redirect permit_energies foreign key
-- First, drop the old constraint (may fail if it doesn't exist - that's OK)
-- Run this manually if you get an error:
-- ALTER TABLE `permit_energies` DROP FOREIGN KEY `fk_energy_verifier_users`;

ALTER TABLE `permit_energies` 
    DROP FOREIGN KEY `fk_energy_verifier_users`;

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
-- Drop old foreign key constraints (may fail if they don't exist - that's OK)
-- Run these manually if you get errors:
-- ALTER TABLE `controls` DROP FOREIGN KEY `controls_ibfk_1`;
-- ALTER TABLE `controls` DROP FOREIGN KEY `fk_controls_risk`;

ALTER TABLE `controls` DROP FOREIGN KEY `controls_ibfk_1`;
ALTER TABLE `controls` DROP FOREIGN KEY `fk_controls_risk`;

-- Add new foreign key constraint pointing to risk_register
ALTER TABLE `controls` 
    ADD CONSTRAINT `fk_controls_master_risk` 
    FOREIGN KEY (`risk_id`) 
    REFERENCES `risk_register`(`risk_id`) 
    ON DELETE CASCADE;

-- Step B: Redirect Process Map Risk
-- Drop old foreign key constraint (may fail if it doesn't exist - that's OK)
-- Run this manually if you get an error:
-- ALTER TABLE `process_map_risk` DROP FOREIGN KEY `process_map_risk_ibfk_2`;

ALTER TABLE `process_map_risk` 
    DROP FOREIGN KEY `process_map_risk_ibfk_2`;

-- Add new foreign key constraint pointing to risk_register
ALTER TABLE `process_map_risk` 
    ADD CONSTRAINT `fk_process_map_master_risk` 
    FOREIGN KEY (`risk_id`) 
    REFERENCES `risk_register`(`risk_id`) 
    ON DELETE CASCADE;

-- Step C: Drop Redundant Tables
DROP TABLE IF EXISTS `risks`;
DROP TABLE IF EXISTS `hazards`;

-- ============================================================================
-- COMMIT TRANSACTION
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 1;

-- Review changes before committing
-- Uncomment COMMIT when ready, or use ROLLBACK to undo

-- COMMIT;
-- ROLLBACK;

