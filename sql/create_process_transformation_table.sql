/* File: sheener/sql/create_process_transformation_table.sql */
-- =====================================================
-- SQL Script: Create process_transformation Table
-- Purpose: Store input/output transformation data for process steps
-- Date: 2024-12-19
-- =====================================================

CREATE TABLE IF NOT EXISTS `process_transformation` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `process_map_id` INT(11) NOT NULL COMMENT 'Reference to process_map.id',
  `type` ENUM('input', 'output') NOT NULL COMMENT 'Whether this is an input or output',
  `material_name` VARCHAR(255) NOT NULL COMMENT 'Name of the material/component',
  `quantity` VARCHAR(50) NULL DEFAULT NULL COMMENT 'Quantity (e.g., "5kg", "4.8kg", "10 units")',
  `unit` VARCHAR(50) NULL DEFAULT NULL COMMENT 'Unit of measurement (e.g., "kg", "units", "liters")',
  `description` TEXT NULL DEFAULT NULL COMMENT 'Additional description or notes',
  `order` INT(11) DEFAULT 0 COMMENT 'Order for display purposes',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_process_map_id` (`process_map_id`),
  KEY `ix_type` (`type`),
  KEY `ix_order` (`process_map_id`, `type`, `order`),
  CONSTRAINT `process_transformation_ibfk_process` 
    FOREIGN KEY (`process_map_id`) 
    REFERENCES `process_map` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Stores input/output transformation data for process steps';

-- =====================================================
-- Example Insert Statements
-- =====================================================
-- INSERT INTO `process_transformation` (`process_map_id`, `type`, `material_name`, `quantity`, `unit`, `order`) 
-- VALUES 
--   (1021, 'input', 'Raw Material X', '5', 'kg', 1),
--   (1021, 'output', 'Component Y', '4.8', 'kg', 1);

-- =====================================================
-- Verification Query (Optional - run after applying)
-- =====================================================
-- SHOW CREATE TABLE `process_transformation`;

