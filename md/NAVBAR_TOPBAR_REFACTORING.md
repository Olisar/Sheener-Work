# Navbar/Topbar Refactoring - Best Practices Implementation

## Overview
This document describes the refactoring of the navigation system to follow best practices for role-based access control (RBAC) and maintainability.

## Key Principles Implemented

### 1. Single Source of Truth
- **One `navbar.js`** component for the whole app (located in `js/navbar.js`)
- **One `topbar.js`** component for the whole app (located in `js/topbar.js`)
- All navigation configuration driven from `php/get_navigation_config.php`

### 2. Role and Permission-Based Access
- **No hardcoded person checks** (removed all `user_id === 32` checks)
- Access control based on:
  - **Roles**: Primary mechanism (Admin, Permit, Supervisor, etc.)
  - **Permissions**: Fine-grained control (view_analytics, manage_waste, etc.)
  - **Attributes**: Additional criteria when needed (department, company, IsActive, site)

### 3. Central Menu Configuration
All menu items are defined in `php/get_navigation_config.php` with the following structure:

```php
[
    'page' => 'page.php',
    'label' => 'Display Name',
    'roles' => ['Admin', 'User'],           // Required: array of role names (OR logic)
    'permission' => 'optional_permission',  // Optional: specific permission required
    'attributes' => [                       // Optional: attribute-based conditions
        'department' => 'EHS',             // Single value or array for multiple
        'IsActive' => 1
    ],
    'category' => 'Main'
]
```

## Changes Made

### Backend Changes

#### 1. `php/rbac_helper.php`
- **Refactored `isPermitUser()`**: Changed from hardcoded `user_id === 32` to role-based check using `userHasRole('Permit')`
- **Added `userMeetsAttributes()`**: New function to support attribute-based access control (ABAC) for department, company, IsActive, site, etc.

#### 2. `php/get_navigation_config.php`
- **Removed hardcoded permit user checks**: All filtering now uses pure role/permission logic
- **Added attribute support**: Menu items can now specify attribute requirements
- **Improved filtering logic**: Cleaner, more maintainable code that checks roles → permissions → attributes in sequence
- **Removed `isPermitUser` flag**: No longer needed - determined from roles

### Frontend Changes

#### 1. `js/navbar.js`
- **Removed all hardcoded person checks**: No more `user_id === 32` or `isPermitUser` checks
- **Simplified fallback**: Minimal fallback that doesn't make assumptions about user roles
- **Clean navigation function**: Uses only configuration from backend

#### 2. `js/topbar.js`
- **Removed all hardcoded person checks**: No more role-based logic in fallback
- **Simplified fallback**: Shows only essential functions (Home, Close, Logout, Clock)
- **Configuration-driven**: All functionality driven from backend config

## Access Control Flow

1. **User logs in** → Roles and permissions loaded into session
2. **Page loads** → Frontend requests navigation config from `php/get_navigation_config.php`
3. **Backend filters**:
   - Check if user has **any** of the required roles (OR logic)
   - Check if user has required **permission** (if specified)
   - Check if user meets **attribute requirements** (if specified)
4. **Frontend renders** → Only shows menu items that passed all checks
5. **Navigation** → Client-side validation (cosmetic) + Server-side enforcement (security)

## Attribute-Based Access Control (ABAC) Examples

### Example 1: Department-Specific Menu
```php
[
    'page' => 'risk_assessments_admin.php',
    'label' => 'Risk Assessments Admin',
    'roles' => ['Admin', 'Supervisor'],
    'permission' => null,
    'attributes' => ['department' => 'EHS'],  // Only EHS department
    'category' => 'Main'
]
```

### Example 2: Active Users Only
```php
[
    'page' => 'permit_form.php',
    'label' => 'Create Permit',
    'roles' => ['Permit'],
    'permission' => null,
    'attributes' => ['IsActive' => 1],  // Only active users
    'category' => 'Permit Management'
]
```

### Example 3: Multiple Departments
```php
[
    'page' => 'special_report.php',
    'label' => 'Special Report',
    'roles' => ['Admin'],
    'permission' => null,
    'attributes' => ['department' => ['EHS', 'Quality', 'Operations']],  // Multiple departments
    'category' => 'Reports'
]
```

## Benefits

1. **Maintainability**: Single configuration file instead of multiple navbar/topbar files per role
2. **Scalability**: Easy to add new roles, permissions, or menu items
3. **Security**: All access control logic centralized in backend
4. **Flexibility**: Support for complex access rules via attributes
5. **Consistency**: Same access rules applied everywhere

## Migration Notes

### Removed Hardcoded Checks
- ❌ `user_id === 32` checks
- ❌ `isPermitUser` flag in sessionStorage
- ❌ Hardcoded permit user lists in frontend
- ❌ Role-specific navbar/topbar files

### New Approach
- ✅ Role-based checks via `userHasRole()`
- ✅ Permission-based checks via `userHasPermission()`
- ✅ Attribute-based checks via `userMeetsAttributes()`
- ✅ Single configuration-driven navbar/topbar

## Testing Checklist

- [ ] Users with Permit role see permit management menu
- [ ] Users with Admin role see admin functions
- [ ] Users without required permissions don't see restricted items
- [ ] Attribute-based filtering works (e.g., department-specific items)
- [ ] Fallback navigation works when config fails to load
- [ ] Server-side access control enforced on all pages

## Future Enhancements

1. **Permission Management UI**: Admin interface to manage role-permission mappings
2. **Attribute Expansion**: Support for more attributes (site, project, etc.)
3. **Dynamic Menu Ordering**: Configurable menu item ordering
4. **Menu Item Icons**: Support for icons in menu configuration
5. **Nested Menus**: Support for submenus and menu hierarchies

