# Navigation Files Summary

## Answer: How Many Files?

**ONE `navbar.js` and ONE `topbar.js` file** - Both are now role-based and dynamically configured.

## Why One File Each?

### Best Practice Approach

1. **Single Source of Truth**: All navigation configuration is in `php/get_navigation_config.php`
2. **Data-Driven**: Navigation items are determined by user roles and permissions from the database
3. **Scalable**: Easy to add new roles, permissions, or navigation items without code changes
4. **Maintainable**: One file to update instead of multiple files per role
5. **Secure**: Server-side validation ensures proper access control

### Previous Approach (Not Recommended)

❌ **Multiple Files Approach**:
- `navbar_admin.js`
- `navbar_user.js`
- `navbar_permit.js`
- `topbar_admin.js`
- `topbar_user.js`
- etc.

**Problems**:
- Code duplication
- Hard to maintain
- Inconsistent behavior
- Difficult to add new roles
- Security issues (client-side only)

### Current Approach (Recommended)

✅ **Single File with Role-Based Configuration**:
- `js/navbar.js` - Fetches configuration and renders based on roles
- `js/topbar.js` - Fetches configuration and renders based on roles
- `php/get_navigation_config.php` - Server-side configuration API

**Benefits**:
- Single codebase
- Easy to maintain
- Consistent behavior
- Easy to add new roles
- Server-side security

## How It Works

### Architecture Flow

```
User Login
    ↓
Session Created (with roles)
    ↓
Page Loads
    ↓
navbar.js / topbar.js fetch configuration
    ↓
get_navigation_config.php checks user roles
    ↓
Returns filtered navigation items
    ↓
JavaScript renders only allowed items
```

### Example: Different Users See Different Navigation

**Admin User**:
- Sees: Dashboard, Analytics, Change Control, Waste Management, Risk Register, etc.
- Topbar: Encrypt, Backup, Clear Cache, Topic, Profile, Planner, Home, Logout

**Regular User**:
- Sees: Dashboard, Process Map, Task Center, Event, Training, etc.
- Topbar: Topic, Profile, Planner, Home, Logout (no admin functions)

**Permit User** (user_id = 32):
- Sees: Permit Dashboard, Permit List, View Permits, Create Permit
- Topbar: Profile, Home, Logout (minimal functions)

## Configuration System

### Navigation Items Configuration

Located in `php/get_navigation_config.php`:

```php
$allNavItems = [
    [
        'page' => 'analytics.php',
        'label' => 'Analytics',
        'roles' => ['Admin', 'Supervisor'],      // Required roles
        'permission' => 'view_analytics',        // Optional permission
        'category' => 'Main'
    ],
    // ... more items
];
```

### Topbar Functions Configuration

```php
$allTopbarFunctions = [
    'encrypt' => [
        'roles' => ['Admin'],
        'permission' => 'system_admin',
        'action' => 'encrypt.php'
    ],
    // ... more functions
];
```

## Adding New Roles

### Step 1: Add Role to Database

```sql
INSERT INTO roles (RoleName, Description) 
VALUES ('Manager', 'Department Manager');
```

### Step 2: Update Navigation Configuration

Edit `php/get_navigation_config.php` and add 'Manager' to relevant items:

```php
[
    'page' => 'reports.php',
    'label' => 'Reports',
    'roles' => ['Admin', 'Supervisor', 'Manager'],  // Added Manager
    'permission' => null,
    'category' => 'Main'
]
```

### Step 3: Assign Role to Users

```sql
INSERT INTO people_roles (PersonID, RoleID) 
VALUES (123, (SELECT RoleID FROM roles WHERE RoleName = 'Manager'));
```

**That's it!** No JavaScript changes needed.

## Possibilities for Users

### 1. Multiple Roles Per User

A user can have multiple roles simultaneously:
- User with roles: `['Admin', 'Supervisor']`
- Sees navigation items from **both** roles
- Gets most permissive access

### 2. Permission-Based Access

Finer-grained control using permissions:
- Role: `Supervisor`
- Permission: `view_analytics` (required)
- Item only shows if user has **both** role and permission

### 3. Dynamic Updates

Navigation automatically updates when:
- User roles change in database
- Permissions are modified
- New navigation items are added to configuration

### 4. Customizable Access Levels

Easy to create different access levels:
- **View-Only**: Role with read permissions only
- **Editor**: Role with edit permissions
- **Admin**: Role with all permissions

## File Structure

```
js/
  ├── navbar.js          ← ONE file (role-based)
  └── topbar.js          ← ONE file (role-based)

php/
  └── get_navigation_config.php  ← Configuration API

docs/
  ├── NAVIGATION_RBAC_GUIDE.md
  └── NAVIGATION_FILES_SUMMARY.md (this file)
```

## Migration Notes

### What Changed

1. **Before**: Hardcoded `if (isPermitUser)` checks
2. **After**: Dynamic role-based configuration from database

### Backward Compatibility

- Fallback navigation if API fails
- Still supports permit user (user_id = 32) check
- Graceful degradation

## Testing Checklist

- [ ] Admin user sees all navigation items
- [ ] Regular user sees appropriate items
- [ ] Permit user sees only permit items
- [ ] User with multiple roles sees union of items
- [ ] Permission-based items work correctly
- [ ] Topbar functions show/hide correctly
- [ ] Fallback navigation works if API fails

## Summary

**Answer**: Create **ONE navbar.js and ONE topbar.js** file.

**Approach**: Role-based, data-driven configuration from server.

**Benefits**:
- ✅ Scalable
- ✅ Maintainable
- ✅ Secure
- ✅ Flexible
- ✅ Easy to extend

This is the industry best practice for role-based navigation systems.

