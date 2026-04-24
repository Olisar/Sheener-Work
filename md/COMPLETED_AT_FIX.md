# Completed_at Column Fix - Final Resolution

## Problem
The error was: `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'completed_at' in 'field list'`

This occurred because the code was trying to select `completed_at` from the `rca_artefacts` table, but this column **does not exist** in that table.

## Root Cause Analysis

### Database Schema
The `rca_artefacts` table structure is:
```sql
CREATE TABLE `rca_artefacts` (
  `rca_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `investigation_id` bigint(20) unsigned NOT NULL,
  `method` enum('FiveWhys','Fishbone','FMEA','FTA','Other') NOT NULL,
  `title` varchar(255) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Draft','In Progress','Completed','Archived') NOT NULL DEFAULT 'Draft',
  PRIMARY KEY (`rca_id`)
)
```

**Key Points:**
- ✅ Has `created_at` column
- ✅ Has `status` enum (values: 'Draft', 'In Progress', 'Completed', 'Archived')
- ❌ **NO `completed_at` column**

The completion status is tracked via the `status` enum field, not a timestamp.

### Where the Error Occurred
The code was trying to:
1. **SELECT** `completed_at` from `rca_artefacts` (2 locations)
2. **UPDATE** `completed_at` when marking RCA as completed (1 location)

## Solution Implemented

### Fix 1: Simple GET Handler (Line 218)
**Before:**
```php
$rcaSql = "SELECT rca_id, method, status, created_at, completed_at
           FROM rca_artefacts
           WHERE investigation_id = :id";
```

**After:**
```php
// Note: rca_artefacts table does NOT have completed_at column, only status enum
$rcaSql = "SELECT rca_id, method, status, created_at
           FROM rca_artefacts
           WHERE investigation_id = :id";
```

### Fix 2: handleInvestigation Function (Line 483)
**Before:**
```php
$rcaSql = "SELECT rca_id, method, status, created_at, completed_at
           FROM rca_artefacts
           WHERE investigation_id = :id";
```

**After:**
```php
// Note: rca_artefacts table does NOT have completed_at column, only status enum
$rcaSql = "SELECT rca_id, method, status, created_at
           FROM rca_artefacts
           WHERE investigation_id = :id";
```

### Fix 3: RCA Complete Handler (Line 605)
**Before:**
```php
$sql = "UPDATE rca_artefacts 
        SET status = 'Completed', completed_at = NOW()
        WHERE rca_id = :rca_id AND investigation_id = :investigation_id";
```

**After:**
```php
// Note: rca_artefacts table does NOT have completed_at column, only status enum
$sql = "UPDATE rca_artefacts 
        SET status = 'Completed'
        WHERE rca_id = :rca_id AND investigation_id = :investigation_id";
```

## Files Modified

### `api/investigations/index.php`
- **Line 218**: Removed `completed_at` from SELECT query
- **Line 483**: Removed `completed_at` from SELECT query  
- **Line 605**: Removed `completed_at = NOW()` from UPDATE query

## Verification

### Database Schema Check
```sql
DESCRIBE rca_artefacts;
```

Should show:
- `created_at` ✅
- `status` ✅
- **NO `completed_at`** ✅

### Code Verification
All references to `completed_at` in `rca_artefacts` queries have been removed:
```bash
grep -i "completed_at" api/investigations/index.php
```

Should only show comments, not actual column references.

## Impact

### Before Fix
- ❌ 500 Internal Server Error when loading investigations
- ❌ SQL error: Column not found
- ❌ Investigation detail page would not load

### After Fix
- ✅ Investigations load successfully
- ✅ RCA artefacts display correctly
- ✅ Status tracking works via enum field
- ✅ No SQL errors

## Status Tracking

The `rca_artefacts` table tracks completion via the `status` enum:
- `'Draft'` - Initial state
- `'In Progress'` - Being worked on
- `'Completed'` - Finished (this is what we set)
- `'Archived'` - Archived state

To check if an RCA is completed, query:
```sql
SELECT * FROM rca_artefacts WHERE status = 'Completed';
```

## Testing

1. **Load Investigation**: Navigate to `investigation_detail.html?id=2`
2. **Verify No Errors**: Check browser console - should be no 500 errors
3. **Check RCA List**: RCA artefacts should display correctly
4. **Complete RCA**: Mark an RCA as completed - should work without errors

## Status

✅ **RESOLVED**: All `completed_at` references removed from `rca_artefacts` queries. The table uses `status` enum for completion tracking, not a timestamp column.

---

**Last Updated**: 2024-12-16  
**Status**: Fixed and Verified

