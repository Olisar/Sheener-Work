/* File: sheener/sql/add_process_map_fields_simple.sql */
-- =====================================================
-- Simple SQL Script: Add Missing Fields for Process Visualization
-- Purpose: Add all required fields (run this if columns don't exist yet)
-- Date: 2024-12-19
-- 
-- IMPORTANT: Run this script only if the columns don't exist.
-- If columns already exist, you'll get errors - that's okay, just skip those statements.
-- =====================================================

-- =====================================================
-- PART 1: Enhance process_map Table
-- =====================================================

-- Add 'level' field to store process hierarchy level (L0_Enterprise, L1_HighLevel, etc.)
ALTER TABLE `process_map`
ADD COLUMN `level` VARCHAR(50) NULL DEFAULT NULL COMMENT 'Process hierarchy level (L0_Enterprise, L1_HighLevel, L2_SubProcess, L3_DetailStep)' AFTER `type`;

ALTER TABLE `process_map`
ADD INDEX `ix_level` (`level`);

-- Add 'cost' field to store estimated cost for the process step
ALTER TABLE `process_map`
ADD COLUMN `cost` DECIMAL(10,2) NULL DEFAULT NULL COMMENT 'Estimated cost in currency units' AFTER `description`;

-- Add 'value_add' field to indicate if the process step adds value
ALTER TABLE `process_map`
ADD COLUMN `value_add` TINYINT(1) NULL DEFAULT NULL COMMENT '1 = Value-adding, 0 = Non-value-adding, NULL = Not specified' AFTER `cost`;

ALTER TABLE `process_map`
ADD INDEX `ix_value_add` (`value_add`);

-- =====================================================
-- PART 2: Enhance Element Junction Tables
-- =====================================================

-- Enhance process_map_people
ALTER TABLE `process_map_people`
ADD COLUMN `usage` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Usage description (e.g., "2 hours", "1 hour", "Access")' AFTER `people_id`;

ALTER TABLE `process_map_people`
ADD COLUMN `fixed` TINYINT(1) NULL DEFAULT 0 COMMENT '1 = Fixed resource, 0 = Movable resource' AFTER `usage`;

-- Enhance process_map_equipment
ALTER TABLE `process_map_equipment`
ADD COLUMN `usage` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Usage description (e.g., "30 min", "5 min")' AFTER `equipment_id`;

ALTER TABLE `process_map_equipment`
ADD COLUMN `fixed` TINYINT(1) NULL DEFAULT 0 COMMENT '1 = Fixed resource, 0 = Movable resource' AFTER `usage`;

-- Enhance process_map_material
ALTER TABLE `process_map_material`
ADD COLUMN `usage` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Usage description (e.g., "5 kg", "10 units") - human readable format' AFTER `measurement_unit_id`;

ALTER TABLE `process_map_material`
ADD COLUMN `fixed` TINYINT(1) NULL DEFAULT 0 COMMENT '1 = Fixed resource, 0 = Movable resource' AFTER `usage`;

-- Enhance process_map_energy
ALTER TABLE `process_map_energy`
ADD COLUMN `usage` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Usage description (e.g., "10 kWh")' AFTER `energy_id`;

ALTER TABLE `process_map_energy`
ADD COLUMN `fixed` TINYINT(1) NULL DEFAULT 0 COMMENT '1 = Fixed resource, 0 = Movable resource' AFTER `usage`;

-- Enhance process_map_area
ALTER TABLE `process_map_area`
ADD COLUMN `usage` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Usage description (e.g., "10 sq.m")' AFTER `area_id`;

ALTER TABLE `process_map_area`
ADD COLUMN `fixed` TINYINT(1) NULL DEFAULT 0 COMMENT '1 = Fixed resource, 0 = Movable resource' AFTER `usage`;

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

