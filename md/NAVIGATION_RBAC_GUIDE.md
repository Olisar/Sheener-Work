# Navigation & Topbar RBAC Guide

## Overview

**Answer: ONE navbar.js and ONE topbar.js file** - Both are now role-based and data-driven.

This guide explains the role-based navigation system that dynamically configures navbar and topbar based on user roles and permissions.

## Architecture

### Single Source of Truth
- **`php/get_navigation_config.php`**: Server-side API that returns navigation configuration based on user roles
- **`js/navbar.js`**: Single navbar file that fetches and renders based on configuration
- **`js/topbar.js`**: Single topbar file that fetches and renders based on configuration

### Why One File Each?

1. **Maintainability**: Single file to update when adding new navigation items
2. **Consistency**: All users use the same codebase, reducing bugs
3. **Scalability**: Easy to add new roles or modify access without code duplication
4. **Security**: Server-side validation ensures proper access control
5. **Performance**: Configuration is cached and fetched once per page load

## How It Works

### 1. Server-Side Configuration (`php/get_navigation_config.php`)

The API defines:
- **Navigation Items**: Each item specifies required roles and optional permissions
- **Topbar Functions**: Each function specifies required roles and optional permissions
- **Allowed Pages**: List of pages user can navigate to

```php
// Example navigation item definition
[
    'page' => 'analytics.php',
    'label' => 'Analytics',
    'roles' => ['Admin', 'Supervisor'],
    'permission' => 'view_analytics',  // Optional
    'category' => 'Main'
]
```

### 2. Client-Side Rendering (`js/navbar.js` & `js/topbar.js`)

1. Fetches configuration from API on page load
2. Filters items based on user's roles and permissions
3. Renders only allowed items
4. Falls back to basic navigation if API fails

## Role-Based Possibilities

### Current Role Structure

Based on your RBAC system, users can have multiple roles:
- **Admin**: Full system access
- **Approver**: Can approve changes
- **User**: Standard user access
- **Supervisor**: Management access
- **Permit**: Special permit-only user (people_id = 32)

### Navigation Item Configuration

Each navigation item can be configured with:

1. **Required Roles**: Array of role names that can access the item
   ```php
   'roles' => ['Admin', 'Supervisor']
   ```

2. **Required Permission**: Optional permission check (finer-grained control)
   ```php
   'permission' => 'view_analytics'
   ```

3. **Category**: For grouping items (future enhancement)
   ```php
   'category' => 'Main'
   ```

### Examples of Role-Based Navigation

#### Example 1: Admin-Only Features
```php
[
    'page' => 'encrypt.php',
    'label' => 'Encryption',
    'roles' => ['Admin'],
    'permission' => 'system_admin'
]
```

#### Example 2: Multi-Role Access
```php
[
    'page' => 'event_list.php',
    'label' => 'Events',
    'roles' => ['Admin', 'Approver', 'User', 'Supervisor'],
    'permission' => null
]
```

#### Example 3: Permission-Based Access
```php
[
    'page' => 'waste_management_dashboard.html',
    'label' => 'Waste Management',
    'roles' => ['Admin', 'Supervisor'],
    'permission' => 'manage_waste'
]
```

## Adding New Navigation Items

### Step 1: Add to Configuration API

Edit `php/get_navigation_config.php` and add to `$allNavItems` array:

```php
[
    'page' => 'new_feature.php',
    'label' => 'New Feature',
    'roles' => ['Admin', 'Supervisor'],
    'permission' => 'access_new_feature',  // Optional
    'category' => 'Main'
]
```

### Step 2: Add Permission (if needed)

If using permission-based access, ensure the permission exists in the database:

```sql
INSERT INTO permissions (PermissionName) VALUES ('access_new_feature');
INSERT INTO rolepermissions (RoleID, PermissionID) 
SELECT r.RoleID, p.PermissionID 
FROM roles r, permissions p 
WHERE r.RoleName = 'Admin' AND p.PermissionName = 'access_new_feature';
```

### Step 3: Test

The navigation will automatically appear for users with the required roles/permissions.

## Adding New Topbar Functions

### Step 1: Add to Configuration API

Edit `php/get_navigation_config.php` and add to `$allTopbarFunctions` array:

```php
'newFunction' => [
    'roles' => ['Admin'],
    'permission' => 'system_admin',
    'action' => 'new_function.php'
]
```

### Step 2: Add Handler in topbar.js

Add the handler function and wire it up in `attachTopbarEvents()`:

```javascript
if (topbarFunctions.newFunction) {
    iconMap["NewFunction"] = () => {
        window.location.href = topbarFunctions.newFunction.action;
    };
}
```

## User Experience Possibilities

### 1. Role-Based Menu Items

Users only see navigation items they have access to:
- **Admin**: Sees all items
- **User**: Sees only user-level items
- **Permit User**: Sees only permit management items

### 2. Multiple Roles Support

If a user has multiple roles (e.g., Admin + Supervisor), they see items from all their roles:
- Union of all accessible items
- Most permissive access

### 3. Permission-Based Access

Finer-grained control using permissions:
- User has role but lacks specific permission → Item hidden
- Allows for complex access scenarios

### 4. Dynamic Updates

Navigation updates automatically when:
- User roles change
- Permissions are modified
- New navigation items are added

## Best Practices

### 1. Server-Side Validation

**Always validate on server-side** - Client-side restrictions are for UX only:
```php
// In your PHP pages
require_once 'php/rbac_helper.php';
requireRole('Admin');
// or
requirePermission('view_analytics');
```

### 2. Role Hierarchy (Future Enhancement)

Consider implementing role hierarchy:
- Admin inherits all permissions
- Supervisor inherits User permissions
- Reduces configuration complexity

### 3. Caching

Consider caching navigation configuration:
- Reduces database queries
- Improves performance
- Cache invalidation on role/permission changes

### 4. Audit Logging

Log navigation access for security:
- Track which users access which pages
- Monitor for unauthorized access attempts

## Migration from Old System

### Before (Hardcoded)
```javascript
if (isPermitUser) {
    // Show permit items
} else {
    // Show all items
}
```

### After (Role-Based)
```javascript
// Automatically filtered based on user roles
config.navbarItems.forEach(item => {
    // Render item
});
```

## Troubleshooting

### Navigation Not Showing

1. **Check user roles**: Ensure user has roles assigned in `people_roles` table
2. **Check permissions**: If item requires permission, ensure it's assigned
3. **Check API response**: Open browser console and check `get_navigation_config.php` response
4. **Check fallback**: System falls back to basic navigation if API fails

### Items Showing When They Shouldn't

1. **Server-side validation**: Ensure pages have proper `requireRole()` or `requirePermission()` checks
2. **Permission assignment**: Verify permissions are correctly assigned to roles
3. **Role assignment**: Verify user roles are correct

## Future Enhancements

### Possible Improvements

1. **Role Hierarchy**: Implement role inheritance
2. **Time-Based Access**: Restrict access based on time of day
3. **Department-Based**: Show items based on user's department
4. **Custom Navigation**: Allow users to customize their navigation (favorites)
5. **Navigation Analytics**: Track which items are used most
6. **Mobile Optimization**: Different navigation for mobile devices

## Summary

- **ONE navbar.js** - Role-based, dynamically configured
- **ONE topbar.js** - Role-based, dynamically configured
- **Server-driven** - Configuration comes from database/roles
- **Scalable** - Easy to add new roles, permissions, or items
- **Secure** - Server-side validation ensures proper access
- **Maintainable** - Single source of truth for navigation

This approach provides maximum flexibility while maintaining simplicity and security.

