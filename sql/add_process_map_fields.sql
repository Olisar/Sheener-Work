/* File: sheener/sql/add_process_map_fields.sql */
-- =====================================================
-- SQL Script: Add Missing Fields to process_map Table
-- Purpose: Support process visualization with level, cost, and value_add fields
-- Date: 2024-12-19
-- =====================================================

-- Add 'level' field to store process hierarchy level (L0_Enterprise, L1_HighLevel, etc.)
ALTER TABLE `process_map`
ADD COLUMN `level` VARCHAR(50) NULL DEFAULT NULL COMMENT 'Process hierarchy level (L0_Enterprise, L1_HighLevel, L2_SubProcess, L3_DetailStep)' AFTER `type`,
ADD INDEX `ix_level` (`level`);

-- Add 'cost' field to store estimated cost for the process step
ALTER TABLE `process_map`
ADD COLUMN `cost` DECIMAL(10,2) NULL DEFAULT NULL COMMENT 'Estimated cost in currency units' AFTER `description`;

-- Add 'value_add' field to indicate if the process step adds value
ALTER TABLE `process_map`
ADD COLUMN `value_add` TINYINT(1) NULL DEFAULT NULL COMMENT '1 = Value-adding, 0 = Non-value-adding, NULL = Not specified' AFTER `cost`;

-- Add index on value_add for filtering
ALTER TABLE `process_map`
ADD INDEX `ix_value_add` (`value_add`);

-- =====================================================
-- Verification Queries (Optional - run after applying)
-- =====================================================
-- SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, COLUMN_COMMENT 
-- FROM INFORMATION_SCHEMA.COLUMNS 
-- WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'process_map' 
-- AND COLUMN_NAME IN ('level', 'cost', 'value_add');

