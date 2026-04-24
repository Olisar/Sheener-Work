/* File: sheener/sql/add_process_map_fields_complete.sql */
-- =====================================================
-- Complete SQL Script: Add All Missing Fields for Process Visualization
-- Purpose: One script to add all required fields for process data management
-- Date: 2024-12-19
-- 
-- This script combines:
-- 1. process_map table enhancements (level, cost, value_add)
-- 2. Element junction table enhancements (usage, fixed)
-- 3. Transformation table creation
-- =====================================================

-- =====================================================
-- PART 1: Enhance process_map Table
-- =====================================================

-- Add 'level' field to store process hierarchy level
-- Note: Check if column exists before adding (MySQL doesn't support IF NOT EXISTS for ALTER TABLE)
SET @dbname = DATABASE();
SET @tablename = 'process_map';
SET @columnname = 'level';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` VARCHAR(50) NULL DEFAULT NULL COMMENT ''Process hierarchy level (L0_Enterprise, L1_HighLevel, L2_SubProcess, L3_DetailStep)'' AFTER `type`, ADD INDEX `ix_level` (`level`)')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add 'cost' field to store estimated cost
SET @columnname = 'cost';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` DECIMAL(10,2) NULL DEFAULT NULL COMMENT ''Estimated cost in currency units'' AFTER `description`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add 'value_add' field to indicate if the process step adds value
SET @columnname = 'value_add';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` TINYINT(1) NULL DEFAULT NULL COMMENT ''1 = Value-adding, 0 = Non-value-adding, NULL = Not specified'' AFTER `cost`, ADD INDEX `ix_value_add` (`value_add`)')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- =====================================================
-- PART 2: Enhance Element Junction Tables
-- =====================================================

-- Helper function to add column if not exists
-- Enhance process_map_people
SET @tablename = 'process_map_people';
SET @columnname = 'usage';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` VARCHAR(100) NULL DEFAULT NULL COMMENT ''Usage description (e.g., "2 hours", "1 hour", "Access")'' AFTER `people_id`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = 'fixed';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` TINYINT(1) NULL DEFAULT 0 COMMENT ''1 = Fixed resource, 0 = Movable resource'' AFTER `usage`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Enhance process_map_equipment
SET @tablename = 'process_map_equipment';
SET @columnname = 'usage';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` VARCHAR(100) NULL DEFAULT NULL COMMENT ''Usage description (e.g., "30 min", "5 min")'' AFTER `equipment_id`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = 'fixed';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` TINYINT(1) NULL DEFAULT 0 COMMENT ''1 = Fixed resource, 0 = Movable resource'' AFTER `usage`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Enhance process_map_material
SET @tablename = 'process_map_material';
SET @columnname = 'usage';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` VARCHAR(100) NULL DEFAULT NULL COMMENT ''Usage description (e.g., "5 kg", "10 units") - human readable format'' AFTER `measurement_unit_id`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = 'fixed';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` TINYINT(1) NULL DEFAULT 0 COMMENT ''1 = Fixed resource, 0 = Movable resource'' AFTER `usage`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Enhance process_map_energy
SET @tablename = 'process_map_energy';
SET @columnname = 'usage';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` VARCHAR(100) NULL DEFAULT NULL COMMENT ''Usage description (e.g., "10 kWh")'' AFTER `energy_id`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = 'fixed';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` TINYINT(1) NULL DEFAULT 0 COMMENT ''1 = Fixed resource, 0 = Movable resource'' AFTER `usage`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Enhance process_map_area
SET @tablename = 'process_map_area';
SET @columnname = 'usage';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` VARCHAR(100) NULL DEFAULT NULL COMMENT ''Usage description (e.g., "10 sq.m")'' AFTER `area_id`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = 'fixed';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` TINYINT(1) NULL DEFAULT 0 COMMENT ''1 = Fixed resource, 0 = Movable resource'' AFTER `usage`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- =====================================================
-- PART 3: Create Transformation Table
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
-- Verification Queries
-- =====================================================

-- Verify process_map enhancements
SELECT 'process_map enhancements' AS 'Check';
SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, COLUMN_COMMENT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'process_map' 
AND COLUMN_NAME IN ('level', 'cost', 'value_add');

-- Verify element junction table enhancements
SELECT 'Element junction tables' AS 'Check';
SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, COLUMN_COMMENT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME IN ('process_map_people', 'process_map_equipment', 'process_map_material', 'process_map_energy', 'process_map_area')
AND COLUMN_NAME IN ('usage', 'fixed')
ORDER BY TABLE_NAME, ORDINAL_POSITION;

-- Verify transformation table
SELECT 'Transformation table' AS 'Check';
SHOW CREATE TABLE `process_transformation`;

