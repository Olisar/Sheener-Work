# Foreign Key Constraint Fix - Complete Solution

## Problem
The `investigations` table has a foreign key constraint that references `operational_events.event_id`, but the actual events are stored in the `events` table. This causes:
```
SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: 
a foreign key constraint fails (`sheener`.`investigations`, CONSTRAINT `fk_investigation_event` 
FOREIGN KEY (`event_id`) REFERENCES `operational_events` (`event_id`) ON DELETE CASCADE)
```

## Root Cause
- **Events table**: Where events are actually stored (`events`)
- **Foreign key constraint**: Points to `operational_events` table
- **Mismatch**: Events exist in `events` but FK requires them in `operational_events`

## Solutions

### Solution 1: Fix Foreign Key Constraint (Recommended - Permanent Fix)

**Run the SQL migration script** to update the foreign key to point to the correct table:

**File**: `database_migrations/fix_investigations_foreign_key.sql`

```sql
-- Step 1: Drop the existing foreign key constraint
ALTER TABLE `investigations` 
DROP FOREIGN KEY `fk_investigation_event`;

-- Step 2: Add new foreign key constraint pointing to events table
ALTER TABLE `investigations` 
ADD CONSTRAINT `fk_investigation_event` 
FOREIGN KEY (`event_id`) 
REFERENCES `events` (`event_id`) 
ON DELETE CASCADE;
```

**How to Run**:
1. Open phpMyAdmin or MySQL command line
2. Select the `sheener` database
3. Run the SQL script from `database_migrations/fix_investigations_foreign_key.sql`
4. Verify the change worked

**Benefits**:
- ✅ Permanent fix
- ✅ No code workarounds needed
- ✅ Proper database integrity
- ✅ Better performance (no sync operations)

### Solution 2: Code Workaround (Temporary - Already Implemented)

The code now automatically copies events from `events` to `operational_events` if needed. This is a workaround that:
- Checks if foreign key points to `operational_events`
- If event doesn't exist there, copies it from `events` table
- Handles field mapping between the two tables

**Location**: `api/investigations/index.php` (lines 84-140)

**How It Works**:
1. Validates event exists in `events` table
2. Checks foreign key constraint target
3. If FK points to `operational_events`, checks if event exists there
4. If not, copies event with proper field mapping:
   - Maps event_type enums
   - Maps status values
   - Handles NULL department_id (uses default 1)
   - Maps timestamps

**Limitations**:
- ⚠️ Temporary workaround
- ⚠️ Requires both tables to exist
- ⚠️ Adds overhead (extra queries and inserts)
- ⚠️ May fail if department_id is NULL and no default department exists

## Table Structure Differences

### `events` Table
- `event_type`: enum('OFI','Adverse Event','Defects','NonCompliance')
- `status`: enum('Open','Under Investigation','Assessed','Change Control Requested','Change Control Logged','Monitoring','Closed')
- `department_id`: int(11) DEFAULT NULL (optional)
- `reported_date`: timestamp

### `operational_events` Table
- `event_type`: enum('Incident','Finding','OFI','Observation')
- `status`: enum('Open','In Progress','Closed')
- `department_id`: int(11) NOT NULL (required)
- `created_at`: timestamp

### Field Mapping

**Event Type Mapping**:
- `events.OFI` → `operational_events.OFI`
- `events.Adverse Event` → `operational_events.Incident`
- `events.Defects` → `operational_events.Finding`
- `events.NonCompliance` → `operational_events.Finding`

**Status Mapping**:
- `events.Open` → `operational_events.Open`
- `events.Under Investigation` → `operational_events.In Progress`
- `events.Closed` → `operational_events.Closed`
- All others → `operational_events.Open`

## Recommended Action

**Run the SQL migration** (`database_migrations/fix_investigations_foreign_key.sql`) to permanently fix the foreign key constraint. This is the cleanest solution.

The code workaround will continue to work as a fallback, but fixing the constraint is the proper solution.

## Testing After Fix

### Test 1: Create Investigation
1. Open event center
2. Click on event ID 17
3. Click "Initiate Investigation"
4. Should create successfully without errors

### Test 2: Verify Foreign Key
```sql
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'sheener'
  AND TABLE_NAME = 'investigations'
  AND CONSTRAINT_NAME = 'fk_investigation_event';
```

Should show `REFERENCED_TABLE_NAME = 'events'` (not 'operational_events')

## Status

✅ **Code Workaround Implemented**: Automatically syncs events if needed
⚠️ **Database Fix Required**: Run SQL migration for permanent solution

---

**Last Updated**: 2024-12-16  
**Status**: Workaround active, migration script ready

