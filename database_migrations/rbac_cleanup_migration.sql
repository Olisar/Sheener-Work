/* File: sheener/database_migrations/rbac_cleanup_migration.sql */
-- ============================================================================
-- RBAC Schema Cleanup Migration
-- Purpose: Clean up redundant role fields and ensure proper FK constraints
-- Date: 2024
-- ============================================================================

-- Step 1: Backup existing data (optional - uncomment if needed)
-- CREATE TABLE personalinformation_backup AS SELECT * FROM personalinformation;
-- CREATE TABLE people_backup AS SELECT * FROM people;

-- ============================================================================
-- STEP 2: Migrate data from redundant fields to proper tables
-- ============================================================================

-- Migrate people.role_id to people_roles if not already present
INSERT INTO people_roles (PersonID, RoleID)
SELECT p.people_id, p.role_id
FROM people p
WHERE p.role_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM people_roles pr 
    WHERE pr.PersonID = p.people_id AND pr.RoleID = p.role_id
  );

-- Migrate personalinformation.RoleID to people_roles if not already present
-- This ensures login default role is also in the mapping table
INSERT INTO people_roles (PersonID, RoleID)
SELECT pi.PersonID, pi.RoleID
FROM personalinformation pi
WHERE pi.PersonID IS NOT NULL 
  AND pi.RoleID IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM people_roles pr 
    WHERE pr.PersonID = pi.PersonID AND pr.RoleID = pi.RoleID
  );

-- ============================================================================
-- STEP 3: Remove redundant columns
-- ============================================================================

-- Remove people.role_id (redundant - use people_roles table instead)
ALTER TABLE people DROP FOREIGN KEY IF EXISTS fk_people_role;
ALTER TABLE people DROP INDEX IF EXISTS idx_people_role;
ALTER TABLE people DROP COLUMN IF EXISTS role_id;

-- Remove personalinformation.Role (redundant varchar field - use RoleID instead)
ALTER TABLE personalinformation DROP COLUMN IF EXISTS `Role`;

-- ============================================================================
-- STEP 4: Ensure proper Foreign Key constraints
-- ============================================================================

-- Ensure personalinformation.PersonID has proper FK
ALTER TABLE personalinformation 
  DROP FOREIGN KEY IF EXISTS fk_person_info;

ALTER TABLE personalinformation
  ADD CONSTRAINT fk_personalinfo_person 
  FOREIGN KEY (PersonID) REFERENCES people(people_id) 
  ON DELETE SET NULL ON UPDATE CASCADE;

-- Ensure personalinformation.RoleID has proper FK (keep as login default role)
ALTER TABLE personalinformation 
  DROP FOREIGN KEY IF EXISTS fk_role_info;
ALTER TABLE personalinformation 
  DROP FOREIGN KEY IF EXISTS fk_role_info_unique;

ALTER TABLE personalinformation
  ADD CONSTRAINT fk_personalinfo_role 
  FOREIGN KEY (RoleID) REFERENCES roles(RoleID) 
  ON DELETE SET NULL ON UPDATE CASCADE;

-- Ensure people_roles has proper FK constraints
ALTER TABLE people_roles 
  DROP FOREIGN KEY IF EXISTS people_roles_ibfk_1;
ALTER TABLE people_roles 
  DROP FOREIGN KEY IF EXISTS people_roles_ibfk_2;

ALTER TABLE people_roles
  ADD CONSTRAINT fk_people_roles_person 
  FOREIGN KEY (PersonID) REFERENCES people(people_id) 
  ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE people_roles
  ADD CONSTRAINT fk_people_roles_role 
  FOREIGN KEY (RoleID) REFERENCES roles(RoleID) 
  ON DELETE CASCADE ON UPDATE CASCADE;

-- Ensure rolepermissions has proper FK constraints
-- Note: Table name is 'rolepermissions' (not 'role_permissions') - keeping existing name
ALTER TABLE rolepermissions 
  DROP FOREIGN KEY IF EXISTS rolepermissions_ibfk_1;
ALTER TABLE rolepermissions 
  DROP FOREIGN KEY IF EXISTS rolepermissions_ibfk_2;

ALTER TABLE rolepermissions
  ADD CONSTRAINT fk_rolepermissions_role 
  FOREIGN KEY (RoleID) REFERENCES roles(RoleID) 
  ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE rolepermissions
  ADD CONSTRAINT fk_rolepermissions_permission 
  FOREIGN KEY (PermissionID) REFERENCES permissions(PermissionID) 
  ON DELETE CASCADE ON UPDATE CASCADE;

-- ============================================================================
-- STEP 5: Add indexes for performance
-- ============================================================================

-- Index on personalinformation for login lookups
CREATE INDEX IF NOT EXISTS idx_personalinfo_username ON personalinformation(Username);
CREATE INDEX IF NOT EXISTS idx_personalinfo_personid ON personalinformation(PersonID);
CREATE INDEX IF NOT EXISTS idx_personalinfo_roleid ON personalinformation(RoleID);

-- Index on people_roles for role lookups
CREATE INDEX IF NOT EXISTS idx_people_roles_personid ON people_roles(PersonID);
CREATE INDEX IF NOT EXISTS idx_people_roles_roleid ON people_roles(RoleID);

-- Index on rolepermissions
CREATE INDEX IF NOT EXISTS idx_rolepermissions_roleid ON rolepermissions(RoleID);
CREATE INDEX IF NOT EXISTS idx_rolepermissions_permissionid ON rolepermissions(PermissionID);

-- ============================================================================
-- STEP 6: Add comments/documentation
-- ============================================================================

-- Add comments to clarify purpose of personalinformation.RoleID
ALTER TABLE personalinformation 
  MODIFY COLUMN RoleID int(11) DEFAULT NULL 
  COMMENT 'Login default role - used for initial role assignment at login. All roles should also exist in people_roles table.';

-- ============================================================================
-- Migration Complete
-- ============================================================================
-- Summary of changes:
-- 1. Removed people.role_id (redundant - use people_roles table)
-- 2. Removed personalinformation.Role (redundant varchar field)
-- 3. Kept personalinformation.RoleID as "login default role"
-- 4. Ensured all FK constraints are properly set with CASCADE rules
-- 5. Added proper indexes for performance
-- 
-- RBAC Structure:
-- - people: Core person information
-- - personalinformation: Login credentials + RoleID (login default role)
-- - people_roles: Authoritative mapping of PersonID -> RoleID (supports multiple roles)
-- - roles: Role definitions (RoleID, RoleName, Description)
-- - permissions: Permission definitions (PermissionID, PermissionName)
-- - rolepermissions: Role -> Permission mapping (RoleID, PermissionID)
-- ============================================================================

