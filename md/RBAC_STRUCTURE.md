# RBAC (Role-Based Access Control) Structure

## Overview

This document describes the standardized RBAC structure implemented in the SHEEner Management System. The structure follows best practices for role-based access control and supports multiple roles per user.

## Database Schema

### Core Tables

#### `people`
Core person information table.
- **Primary Key**: `people_id`
- **Notable Fields**: `FirstName`, `LastName`, `Email`, `Position`, `company_id`, `department_id`
- **Note**: `role_id` has been removed - use `people_roles` table instead

#### `personalinformation`
Login credentials and authentication information.
- **Primary Key**: `PersonalInfoID`
- **Fields**:
  - `PersonID` → FK to `people.people_id`
  - `Username` (unique)
  - `PasswordHash`
  - `RoleID` → FK to `roles.RoleID` (Login Default Role)
- **Purpose**: Stores login credentials and the default role to use at login time
- **Note**: `Role` (varchar) field has been removed - use `RoleID` instead

#### `roles`
Role definitions.
- **Primary Key**: `RoleID`
- **Fields**: `RoleID`, `RoleName`, `Description`
- **Example Roles**: Admin, Approver, User, Supervisor

#### `people_roles`
**Authoritative mapping** of users to roles (supports multiple roles per user).
- **Composite Primary Key**: (`PersonID`, `RoleID`)
- **Foreign Keys**:
  - `PersonID` → `people.people_id` (CASCADE)
  - `RoleID` → `roles.RoleID` (CASCADE)
- **Purpose**: This is the authoritative source for all user roles

#### `permissions`
Permission definitions.
- **Primary Key**: `PermissionID`
- **Fields**: `PermissionID`, `PermissionName`
- **Example Permissions**: `view_tasks`, `edit_tasks`, `delete_tasks`, `manage_users`

#### `rolepermissions`
Role to Permission mapping.
- **Composite Primary Key**: (`RoleID`, `PermissionID`)
- **Foreign Keys**:
  - `RoleID` → `roles.RoleID` (CASCADE)
  - `PermissionID` → `permissions.PermissionID` (CASCADE)
- **Purpose**: Defines which permissions each role has

## Design Decisions

### 1. Login Default Role (`personalinformation.RoleID`)
- **Purpose**: Provides a quick way to assign a default role at login time
- **Usage**: Used during login to set the initial session role
- **Best Practice**: This role should also exist in `people_roles` table for consistency

### 2. Multiple Roles Support (`people_roles`)
- **Purpose**: Allows users to have multiple roles simultaneously
- **Usage**: All role checks should query this table
- **Best Practice**: This is the authoritative source for user roles

### 3. Removed Redundant Fields
- **`people.role_id`**: Removed - redundant with `people_roles` table
- **`personalinformation.Role`**: Removed - redundant varchar field, use `RoleID` instead

## Usage Examples

### Checking User Roles

```php
require_once 'php/rbac_helper.php';

// Check if user has a specific role (by ID or name)
if (userHasRole('Admin')) {
    // User is an admin
}

if (userHasRole(1)) { // Role ID
    // User has role with ID 1
}

// Get all user roles
$roles = getUserRoles();
foreach ($roles as $role) {
    echo $role['RoleName'];
}
```

### Checking Permissions

```php
// Check if user has a specific permission
if (userHasPermission('edit_tasks')) {
    // User can edit tasks
}

// Get all user permissions
$permissions = getUserPermissions();
```

### Requiring Access

```php
// Require a specific role (redirects if not present)
requireRole('Admin', '../index.php');

// Require a specific permission (redirects if not present)
requirePermission('delete_tasks', '../index.php');
```

### Login Process

The login process (`php/login.php`) now:
1. Authenticates user credentials
2. Fetches login default role from `personalinformation.RoleID`
3. Fetches all user roles from `people_roles` table
4. Sets session variables:
   - `$_SESSION['role_id']`: Primary role ID (from login default)
   - `$_SESSION['role']`: Primary role name
   - `$_SESSION['role_ids']`: Array of all role IDs

## Foreign Key Constraints

All foreign keys are properly configured with CASCADE rules:

- `personalinformation.PersonID` → `people.people_id` (SET NULL on delete)
- `personalinformation.RoleID` → `roles.RoleID` (SET NULL on delete)
- `people_roles.PersonID` → `people.people_id` (CASCADE)
- `people_roles.RoleID` → `roles.RoleID` (CASCADE)
- `rolepermissions.RoleID` → `roles.RoleID` (CASCADE)
- `rolepermissions.PermissionID` → `permissions.PermissionID` (CASCADE)

## Migration

To apply the schema cleanup, run:
```sql
SOURCE database_migrations/rbac_cleanup_migration.sql;
```

Or execute the SQL file through your database management tool.

## Best Practices

1. **Always use `people_roles` table** for role checks - it's the authoritative source
2. **Use `personalinformation.RoleID`** only as a login default - ensure it exists in `people_roles`
3. **Use helper functions** from `php/rbac_helper.php` instead of direct database queries
4. **Check permissions, not just roles** when possible for finer-grained access control
5. **Store role IDs in session** during login for efficient permission checking

## Security Considerations

1. **Server-side validation**: Always validate roles/permissions on the server side
2. **Client-side restrictions**: Client-side restrictions (navbar.js, topbar.js) are for UX only
3. **Session security**: Role information is stored in PHP sessions, not client-side
4. **CSRF protection**: All authenticated requests should include CSRF token validation

## Future Enhancements

Potential improvements:
- Role hierarchy support (e.g., Admin inherits Supervisor permissions)
- Time-based role assignments
- Department-specific roles
- Audit logging for role/permission changes

