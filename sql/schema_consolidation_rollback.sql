/* File: sheener/sql/schema_consolidation_rollback.sql */
-- ============================================================================
-- Schema Consolidation Rollback Script
-- ============================================================================
-- This script reverses the schema consolidation migration
-- Use this ONLY if you need to rollback the changes
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

START TRANSACTION;

-- ============================================================================
-- PART 2 ROLLBACK: RESTORE RISK/HAZARD TABLES
-- ============================================================================

-- Note: We cannot fully restore the dropped tables without their original data
-- These CREATE statements restore the structure but data will be lost

-- Recreate hazards table (if it was dropped)
CREATE TABLE IF NOT EXISTS `hazards` (
  `hazard_id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `hazard_type_id` int(11) NOT NULL,
  `hazard_description` text NOT NULL,
  `RAID` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`hazard_id`),
  KEY `task_id` (`task_id`),
  KEY `hazard_type_id` (`hazard_type_id`),
  KEY `fk_hazards_ra` (`RAID`),
  KEY `idx_hazards_task` (`task_id`),
  CONSTRAINT `fk_hazards_ra` FOREIGN KEY (`RAID`) REFERENCES `ra_registert` (`RAID`),
  CONSTRAINT `hazards_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`task_id`) ON DELETE CASCADE,
  CONSTRAINT `hazards_ibfk_2` FOREIGN KEY (`hazard_type_id`) REFERENCES `hazard_type` (`hazard_type_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Recreate risks table (if it was dropped)
CREATE TABLE IF NOT EXISTS `risks` (
  `risk_id` int(11) NOT NULL AUTO_INCREMENT,
  `hazard_id` int(11) NOT NULL,
  `risk_description` text NOT NULL,
  `likelihood_before` int(11) DEFAULT NULL CHECK (`likelihood_before` between 1 and 5),
  `severity_before` int(11) DEFAULT NULL CHECK (`severity_before` between 1 and 5),
  `likelihood_after` int(11) DEFAULT NULL CHECK (`likelihood_after` between 1 and 5),
  `severity_after` int(11) DEFAULT NULL CHECK (`severity_after` between 1 and 5),
  `risk_rate_before` int(11) GENERATED ALWAYS AS (`likelihood_before` * `severity_before`) STORED,
  `risk_rate_after` int(11) GENERATED ALWAYS AS (`likelihood_after` * `severity_after`) STORED,
  `exposure_before` tinyint(4) DEFAULT NULL,
  `exposure_after` tinyint(4) DEFAULT NULL,
  `detectability_before` tinyint(4) DEFAULT NULL,
  `detectability_after` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`risk_id`),
  KEY `hazard_id` (`hazard_id`),
  KEY `idx_risks_hazard` (`hazard_id`),
  CONSTRAINT `risks_ibfk_1` FOREIGN KEY (`hazard_id`) REFERENCES `hazards` (`hazard_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Restore process_map_risk foreign key to risks table
ALTER TABLE `process_map_risk` 
    DROP FOREIGN KEY IF EXISTS `fk_process_map_master_risk`;

ALTER TABLE `process_map_risk` 
    ADD CONSTRAINT `process_map_risk_ibfk_2` 
    FOREIGN KEY (`risk_id`) 
    REFERENCES `risks`(`risk_id`) 
    ON DELETE CASCADE;

-- Restore controls table foreign keys to risks table
ALTER TABLE `controls` 
    DROP FOREIGN KEY IF EXISTS `fk_controls_master_risk`;

ALTER TABLE `controls` 
    ADD CONSTRAINT `controls_ibfk_1` 
    FOREIGN KEY (`risk_id`) 
    REFERENCES `risks`(`risk_id`) 
    ON DELETE CASCADE;

ALTER TABLE `controls` 
    ADD CONSTRAINT `fk_controls_risk` 
    FOREIGN KEY (`risk_id`) 
    REFERENCES `risks`(`risk_id`);

-- ============================================================================
-- PART 1 ROLLBACK: RESTORE USER/LOGIN TABLES
-- ============================================================================

-- Rename user_accounts back to personalinformation
RENAME TABLE `user_accounts` TO `personalinformation`;

-- Recreate users table (structure only - data will be lost)
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `people_id` int(11) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  KEY `people_id` (`people_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people`(`people_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Restore permit_responsibles foreign key to users table
ALTER TABLE `permit_responsibles` 
    DROP FOREIGN KEY IF EXISTS `fk_responsible_people`;

ALTER TABLE `permit_responsibles` 
    ADD CONSTRAINT `fk_responsible_user` 
    FOREIGN KEY (`person_id`) 
    REFERENCES `users`(`people_id`);

-- Restore permit_energies foreign key to users table
ALTER TABLE `permit_energies` 
    DROP FOREIGN KEY IF EXISTS `fk_energy_verifier_people`;

ALTER TABLE `permit_energies` 
    ADD CONSTRAINT `fk_energy_verifier_users` 
    FOREIGN KEY (`isolation_verified_by`) 
    REFERENCES `users`(`user_id`) 
    ON DELETE SET NULL 
    ON UPDATE CASCADE;

SET FOREIGN_KEY_CHECKS = 1;

-- Review changes before committing
-- COMMIT;
-- ROLLBACK;  -- Use this if you need to undo rollback

