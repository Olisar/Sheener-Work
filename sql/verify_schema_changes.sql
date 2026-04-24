/* File: sheener/sql/verify_schema_changes.sql */
-- =====================================================
-- Verification Queries: Check All Schema Changes
-- Purpose: Verify that all new fields and tables were created successfully
-- Date: 2024-12-19
-- =====================================================

-- 1. Verify process_map table enhancements
SELECT '=== process_map Table Fields ===' AS 'Verification';
SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_COMMENT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'process_map' 
AND COLUMN_NAME IN ('level', 'cost', 'value_add')
ORDER BY ORDINAL_POSITION;

-- 2. Verify process_map indexes
SELECT '=== process_map Indexes ===' AS 'Verification';
SHOW INDEXES FROM `process_map` WHERE Key_name IN ('ix_level', 'ix_value_add');

-- 3. Verify element junction table enhancements
SELECT '=== Element Junction Tables (usage, fixed) ===' AS 'Verification';
SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_COMMENT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME IN ('process_map_people', 'process_map_equipment', 'process_map_material', 'process_map_energy', 'process_map_area')
AND COLUMN_NAME IN ('usage', 'fixed')
ORDER BY TABLE_NAME, ORDINAL_POSITION;

-- 4. Verify process_transformation table exists
SELECT '=== process_transformation Table ===' AS 'Verification';
SELECT TABLE_NAME, ENGINE, TABLE_COLLATION, TABLE_COMMENT
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'process_transformation';

-- 5. Show process_transformation table structure
SELECT '=== process_transformation Columns ===' AS 'Verification';
SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_COMMENT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'process_transformation'
ORDER BY ORDINAL_POSITION;

-- 6. Summary count
SELECT '=== Summary ===' AS 'Verification';
SELECT 
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'process_map' AND COLUMN_NAME IN ('level', 'cost', 'value_add')) AS 'process_map_new_fields',
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('process_map_people', 'process_map_equipment', 'process_map_material', 'process_map_energy', 'process_map_area') AND COLUMN_NAME IN ('usage', 'fixed')) AS 'junction_tables_new_fields',
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'process_transformation') AS 'transformation_table_exists';

