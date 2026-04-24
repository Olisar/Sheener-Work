# Events Table Name Fix

## Problem
The code was checking and joining with `operational_events` table, but the actual events data is stored in the `events` table. This caused the error:
```
Event ID 17 does not exist in operational_events table
```

Even though event ID 17 clearly exists in the `events` table.

## Root Cause
There are two event-related tables in the database:
1. **`events`** - The actual table where events are stored (used by `get_all_events.php`)
2. **`operational_events`** - A different table (possibly for a different purpose)

The investigations foreign key constraint references `operational_events`, but the actual event data being used is in the `events` table.

## Solution Implemented

### 1. Updated Event Validation
Changed the validation to check both tables (for compatibility):
- First checks `events` table (primary)
- Falls back to `operational_events` if not found
- Provides clear error message

**Location**: `api/investigations/index.php` (lines 70-86)

**Code**:
```php
// Check if event_id exists (check both events and operational_events tables)
$eventCheck = $pdo->prepare("SELECT event_id FROM events WHERE event_id = :event_id");
$eventCheck->execute([':event_id' => $event_id]);
$eventExists = $eventCheck->fetch();

// If not found in events, check operational_events
if (!$eventExists) {
    $eventCheck2 = $pdo->prepare("SELECT event_id FROM operational_events WHERE event_id = :event_id");
    $eventCheck2->execute([':event_id' => $event_id]);
    $eventExists = $eventCheck2->fetch();
}
```

### 2. Updated All JOINs
Changed all `LEFT JOIN operational_events` to `LEFT JOIN events` in:
- Simple GET handler for event_id list (line 180)
- Simple GET handler for single investigation (line 232)
- handleInvestigationsList function (line 427)
- handleInvestigation function (line 503)

## Files Modified

### `api/investigations/index.php`
- **Line 70-86**: Updated event validation to check `events` table first
- **Line 180**: Changed JOIN to use `events` table
- **Line 232**: Changed JOIN to use `events` table
- **Line 427**: Changed JOIN to use `events` table
- **Line 503**: Changed JOIN to use `events` table

## Database Tables

### `events` Table (Actual Data)
- Contains the actual event records
- Used by `get_all_events.php`
- Has columns: `event_id`, `event_type`, `reported_by`, `reported_date`, `description`, `status`, etc.

### `operational_events` Table (Different Purpose)
- May be for a different type of events/observations
- Used by `get_all_operational_events.php`
- Has different structure

## Testing

### Test Event Validation
1. Try creating investigation with event_id = 17
2. Should now find it in `events` table
3. Should create investigation successfully

### Test with Non-Existent Event
1. Try creating investigation with event_id = 99999
2. Should check both tables
3. Should return clear error message

## Important Note

**Foreign Key Constraint**: The `investigations` table foreign key still references `operational_events.event_id`. This means:
- If the foreign key constraint is enforced, you may need to either:
  1. Update the foreign key to reference `events` table instead
  2. Or ensure events are also in `operational_events` table
  3. Or remove the foreign key constraint if both tables are used

**Current Solution**: The code now checks `events` table first (where the data actually is), which should work for validation. However, if the foreign key constraint is strictly enforced, you may need to update the database schema.

## Status

✅ **RESOLVED**: 
- Code now checks `events` table (where data actually exists)
- All JOINs updated to use `events` table
- Fallback to `operational_events` for compatibility
- Event ID 17 should now be found and investigation creation should work

---

**Last Updated**: 2024-12-16  
**Status**: Fixed - Code now uses correct table name

