/* File: sheener/sql/add_process_elements_fields.sql */
-- =====================================================
-- SQL Script: Add Usage and Fixed Fields to Process Element Junction Tables
-- Purpose: Support detailed element information (usage time/quantity, fixed vs movable)
-- Date: 2024-12-19
-- =====================================================

-- Add 'usage' field to process_map_people (e.g., "2 hours", "1 hour")
ALTER TABLE `process_map_people`
ADD COLUMN `usage` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Usage description (e.g., "2 hours", "1 hour", "Access")' AFTER `people_id`,
ADD COLUMN `fixed` TINYINT(1) NULL DEFAULT 0 COMMENT '1 = Fixed resource, 0 = Movable resource' AFTER `usage`;

-- Add 'usage' field to process_map_equipment (e.g., "30 min", "5 min")
ALTER TABLE `process_map_equipment`
ADD COLUMN `usage` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Usage description (e.g., "30 min", "5 min")' AFTER `equipment_id`,
ADD COLUMN `fixed` TINYINT(1) NULL DEFAULT 0 COMMENT '1 = Fixed resource, 0 = Movable resource' AFTER `usage`;

-- Add 'usage' field to process_map_material (e.g., "5 kg", "10 units")
-- Note: quantity and measurement_unit_id already exist, but usage provides a human-readable format
ALTER TABLE `process_map_material`
ADD COLUMN `usage` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Usage description (e.g., "5 kg", "10 units") - human readable format' AFTER `measurement_unit_id`,
ADD COLUMN `fixed` TINYINT(1) NULL DEFAULT 0 COMMENT '1 = Fixed resource, 0 = Movable resource' AFTER `usage`;

-- Add 'usage' field to process_map_energy (e.g., "10 kWh")
ALTER TABLE `process_map_energy`
ADD COLUMN `usage` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Usage description (e.g., "10 kWh")' AFTER `energy_id`,
ADD COLUMN `fixed` TINYINT(1) NULL DEFAULT 0 COMMENT '1 = Fixed resource, 0 = Movable resource' AFTER `usage`;

-- Add 'usage' field to process_map_area (e.g., "10 sq.m")
ALTER TABLE `process_map_area`
ADD COLUMN `usage` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Usage description (e.g., "10 sq.m")' AFTER `area_id`,
ADD COLUMN `fixed` TINYINT(1) NULL DEFAULT 0 COMMENT '1 = Fixed resource, 0 = Movable resource' AFTER `usage`;

-- =====================================================
-- Verification Queries (Optional - run after applying)
-- =====================================================
-- SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, COLUMN_COMMENT 
-- FROM INFORMATION_SCHEMA.COLUMNS 
-- WHERE TABLE_SCHEMA = DATABASE() 
-- AND TABLE_NAME IN ('process_map_people', 'process_map_equipment', 'process_map_material', 'process_map_energy', 'process_map_area')
-- AND COLUMN_NAME IN ('usage', 'fixed')
-- ORDER BY TABLE_NAME, ORDINAL_POSITION;

