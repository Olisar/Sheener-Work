/* File: sheener/sql/schema_consolidation_verify.sql */
-- ============================================================================
-- Schema Consolidation Verification Script
-- ============================================================================
-- Run this script AFTER the migration to verify all changes were applied correctly
-- ============================================================================

-- ============================================================================
-- PART 1 VERIFICATION: USER/LOGIN CONSOLIDATION
-- ============================================================================

-- Verify users table is dropped
SELECT 
    'users table check' AS check_type,
    CASE 
        WHEN COUNT(*) = 0 THEN '✓ PASS: users table does not exist'
        ELSE '✗ FAIL: users table still exists'
    END AS result
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
    AND table_name = 'users';

-- Verify user_accounts table exists (renamed from personalinformation)
SELECT 
    'user_accounts table check' AS check_type,
    CASE 
        WHEN COUNT(*) > 0 THEN CONCAT('✓ PASS: user_accounts table exists (', COUNT(*), ' rows)')
        ELSE '✗ FAIL: user_accounts table does not exist'
    END AS result
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
    AND table_name = 'user_accounts';

-- Verify personalinformation table is renamed (should not exist)
SELECT 
    'personalinformation table check' AS check_type,
    CASE 
        WHEN COUNT(*) = 0 THEN '✓ PASS: personalinformation table renamed to user_accounts'
        ELSE '✗ FAIL: personalinformation table still exists'
    END AS result
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
    AND table_name = 'personalinformation';

-- Verify permit_responsibles foreign key points to people
SELECT 
    'permit_responsibles FK check' AS check_type,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME,
    CASE 
        WHEN REFERENCED_TABLE_NAME = 'people' AND REFERENCED_COLUMN_NAME = 'people_id' 
        THEN '✓ PASS: Foreign key correctly points to people table'
        ELSE CONCAT('✗ FAIL: Foreign key points to ', REFERENCED_TABLE_NAME, '.', REFERENCED_COLUMN_NAME)
    END AS result
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'permit_responsibles'
    AND CONSTRAINT_NAME = 'fk_responsible_people';

-- Verify permit_energies foreign key points to people
SELECT 
    'permit_energies FK check' AS check_type,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME,
    CASE 
        WHEN REFERENCED_TABLE_NAME = 'people' AND REFERENCED_COLUMN_NAME = 'people_id' 
        THEN '✓ PASS: Foreign key correctly points to people table'
        ELSE CONCAT('✗ FAIL: Foreign key points to ', REFERENCED_TABLE_NAME, '.', REFERENCED_COLUMN_NAME)
    END AS result
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'permit_energies'
    AND CONSTRAINT_NAME = 'fk_energy_verifier_people';

-- ============================================================================
-- PART 2 VERIFICATION: RISK/HAZARD UNIFICATION
-- ============================================================================

-- Verify risks table is dropped
SELECT 
    'risks table check' AS check_type,
    CASE 
        WHEN COUNT(*) = 0 THEN '✓ PASS: risks table does not exist'
        ELSE '✗ FAIL: risks table still exists'
    END AS result
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
    AND table_name = 'risks';

-- Verify hazards table is dropped
SELECT 
    'hazards table check' AS check_type,
    CASE 
        WHEN COUNT(*) = 0 THEN '✓ PASS: hazards table does not exist'
        ELSE '✗ FAIL: hazards table still exists'
    END AS result
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
    AND table_name = 'hazards';

-- Verify risk_register table exists
SELECT 
    'risk_register table check' AS check_type,
    CASE 
        WHEN COUNT(*) > 0 THEN CONCAT('✓ PASS: risk_register table exists (', COUNT(*), ' rows)')
        ELSE '✗ FAIL: risk_register table does not exist'
    END AS result
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
    AND table_name = 'risk_register';

-- Verify controls foreign key points to risk_register
SELECT 
    'controls FK check' AS check_type,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME,
    CASE 
        WHEN REFERENCED_TABLE_NAME = 'risk_register' AND REFERENCED_COLUMN_NAME = 'risk_id' 
        THEN '✓ PASS: Foreign key correctly points to risk_register table'
        ELSE CONCAT('✗ FAIL: Foreign key points to ', IFNULL(REFERENCED_TABLE_NAME, 'NULL'), '.', IFNULL(REFERENCED_COLUMN_NAME, 'NULL'))
    END AS result
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'controls'
    AND CONSTRAINT_NAME = 'fk_controls_master_risk';

-- Verify process_map_risk foreign key points to risk_register
SELECT 
    'process_map_risk FK check' AS check_type,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME,
    CASE 
        WHEN REFERENCED_TABLE_NAME = 'risk_register' AND REFERENCED_COLUMN_NAME = 'risk_id' 
        THEN '✓ PASS: Foreign key correctly points to risk_register table'
        ELSE CONCAT('✗ FAIL: Foreign key points to ', IFNULL(REFERENCED_TABLE_NAME, 'NULL'), '.', IFNULL(REFERENCED_COLUMN_NAME, 'NULL'))
    END AS result
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'process_map_risk'
    AND CONSTRAINT_NAME = 'fk_process_map_master_risk';

-- ============================================================================
-- SUMMARY: All Foreign Keys Check
-- ============================================================================

SELECT 
    'SUMMARY: All Foreign Keys' AS check_type,
    TABLE_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME IN ('permit_responsibles', 'permit_energies', 'controls', 'process_map_risk')
    AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME, CONSTRAINT_NAME;

