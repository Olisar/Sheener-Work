/* File: sheener/pha_schema.sql */
-- =================================================================
-- Script to Amend Database Schema for Process Hazard Assessments
-- =================================================================

-- -----------------------------------------------------
-- Table: process_hazard_assessments
-- Purpose: To store the main record for each completed assessment.
-- -----------------------------------------------------
CREATE TABLE `process_hazard_assessments` (
  `assessment_id` int(11) NOT NULL AUTO_INCREMENT,
  `assessment_code` varchar(50) NOT NULL UNIQUE, -- e.g., HZA-2024-08
  `process_name` varchar(255) NOT NULL,
  `process_overview` text DEFAULT NULL,
  `assessment_date` date NOT NULL,
  `assessed_by_id` int(11) NOT NULL,
  `status` enum('Draft','In Review','Approved','Archived') NOT NULL DEFAULT 'Draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`assessment_id`),
  KEY `assessed_by_id` (`assessed_by_id`),
  CONSTRAINT `fk_pha_assessed_by` FOREIGN KEY (`assessed_by_id`) REFERENCES `people` (`people_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- -----------------------------------------------------
-- Alter Table: hazards
-- Purpose: To add fields required for the detailed risk matrix.
-- -----------------------------------------------------
ALTER TABLE `hazards`
ADD COLUMN `assessment_id` int(11) NULL AFTER `hazard_type_id`,
ADD COLUMN `process_step` varchar(255) NULL AFTER `assessment_id`,
ADD COLUMN `existing_controls` text NULL AFTER `process_step`,
ADD COLUMN `initial_likelihood` int(11) NULL AFTER `existing_controls`,
ADD COLUMN `initial_severity` int(11) NULL AFTER `initial_likelihood`,
ADD COLUMN `residual_likelihood` int(11) NULL AFTER `initial_severity`,
ADD COLUMN `residual_severity` int(11) NULL AFTER `residual_likelihood`,
ADD INDEX `idx_hazards_assessment` (`assessment_id`);

-- Add foreign key constraint for the new assessment_id
ALTER TABLE `hazards`
ADD CONSTRAINT `fk_hazards_assessment` FOREIGN KEY (`assessment_id`) REFERENCES `process_hazard_assessments` (`assessment_id`) ON DELETE CASCADE;


-- -----------------------------------------------------
-- Alter Table: controls
-- Purpose: To change the linkage from 'risks' to 'hazards'.
-- This involves renaming the column and updating the foreign key.
-- -----------------------------------------------------
-- First, update the risk_id to the corresponding hazard_id from risks table
UPDATE `controls` SET `risk_id` = (SELECT `hazard_id` FROM `risks` WHERE `risks`.`risk_id` = `controls`.`risk_id`);

-- Drop the existing foreign key constraint
ALTER TABLE `controls` DROP FOREIGN KEY `controls_ibfk_1`;

-- Rename the column from risk_id to hazard_id
ALTER TABLE `controls` CHANGE `risk_id` `hazard_id` int(11) NOT NULL;

-- Add the new foreign key constraint pointing to the hazards table
ALTER TABLE `controls`
ADD CONSTRAINT `fk_controls_hazard` FOREIGN KEY (`hazard_id`) REFERENCES `hazards` (`hazard_id`) ON DELETE CASCADE;


-- -----------------------------------------------------
-- Table: hazard_control_actions
-- Purpose: To track the specific actions required to implement a control.
-- -----------------------------------------------------
CREATE TABLE `hazard_control_actions` (
  `action_id` int(11) NOT NULL AUTO_INCREMENT,
  `hazard_id` int(11) NOT NULL,
  `control_id` int(11) NULL, -- Can be null if the action is for a new, unrecorded control
  `description` text NOT NULL,
  `owner_id` int(11) NOT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('Pending','In Progress','Completed') NOT NULL DEFAULT 'Pending',
  `completion_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`action_id`),
  KEY `fk_actions_hazard` (`hazard_id`),
  KEY `fk_actions_control` (`control_id`),
  KEY `fk_actions_owner` (`owner_id`),
  CONSTRAINT `fk_actions_hazard` FOREIGN KEY (`hazard_id`) REFERENCES `hazards` (`hazard_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_actions_control` FOREIGN KEY (`control_id`) REFERENCES `controls` (`control_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_actions_owner` FOREIGN KEY (`owner_id`) REFERENCES `people` (`people_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- -----------------------------------------------------
-- Table: hazard_assessment_signoffs
-- Purpose: To provide a formal, auditable sign-off for the assessment.
-- -----------------------------------------------------
CREATE TABLE `hazard_assessment_signoffs` (
  `signoff_id` int(11) NOT NULL AUTO_INCREMENT,
  `assessment_id` int(11) NOT NULL,
  `signer_role` varchar(50) NOT NULL, -- e.g., 'Assessed By', 'Reviewed By'
  `signer_id` int(11) NOT NULL,
  `signature_date` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`signoff_id`),
  KEY `fk_signoffs_assessment` (`assessment_id`),
  KEY `fk_signoffs_signer` (`signer_id`),
  CONSTRAINT `fk_signoffs_assessment` FOREIGN KEY (`assessment_id`) REFERENCES `process_hazard_assessments` (`assessment_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_signoffs_signer` FOREIGN KEY (`signer_id`) REFERENCES `people` (`people_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;