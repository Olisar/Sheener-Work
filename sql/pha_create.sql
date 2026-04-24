/* File: sheener/pha_create.sql */
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