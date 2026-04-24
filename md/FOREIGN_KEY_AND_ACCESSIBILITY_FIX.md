# Foreign Key Constraint and Accessibility Fix

## Problems Identified

### 1. Foreign Key Constraint Violation
**Error**: `SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails`

**Cause**: The `event_id` being passed to create an investigation doesn't exist in the `operational_events` table, or the `lead_id` doesn't exist in the `people` table.

### 2. Accessibility Warnings
**Issues**:
- Labels without `for` attributes pointing to form fields
- Labels pointing to display-only divs (not form inputs)
- Labels pointing to JavaScript-populated divs without proper associations

## Solutions Implemented

### 1. Foreign Key Validation

#### Added Pre-Insert Validation
Before attempting to insert an investigation, the code now validates:

1. **Event ID Exists**: Checks if `event_id` exists in `operational_events` table
2. **Lead ID Exists**: Checks if `lead_id` exists in `people` table

**Location**: `api/investigations/index.php` (lines 68-95)

**Code Added**:
```php
// Validate foreign key constraints before insertion
// Check if event_id exists
$eventCheck = $pdo->prepare("SELECT event_id FROM operational_events WHERE event_id = :event_id");
$eventCheck->execute([':event_id' => $event_id]);
if (!$eventCheck->fetch()) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => "Event ID {$event_id} does not exist in operational_events table"
    ]);
    exit;
}

// Check if lead_id exists
$leadCheck = $pdo->prepare("SELECT people_id FROM people WHERE people_id = :lead_id");
$leadCheck->execute([':lead_id' => $lead_id]);
if (!$leadCheck->fetch()) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => "Lead ID {$lead_id} does not exist in people table"
    ]);
    exit;
}
```

**Benefits**:
- ✅ Clear error messages instead of cryptic foreign key errors
- ✅ Prevents database constraint violations
- ✅ Helps identify data issues early
- ✅ Better user experience with specific error messages

### 2. Accessibility Fixes

#### Changed Display-Only Labels to Divs

**Problem**: Labels were being used for display-only content (divs showing data), which is incorrect HTML semantics.

**Solution**: Changed `<label>` to `<div class="form-label">` for all display-only fields.

**Fixed Locations in `event_center.php`**:

1. **View Event Modal** (Display-only fields):
   - `E/O ID` → Changed label to div
   - `Status` → Changed label to div
   - `E/O Type` → Changed label to div
   - `Reported By` → Changed label to div
   - `Reported Date` → Changed label to div
   - `Department` → Changed label to div
   - `Secondary Category` → Changed label to div
   - `Likelihood` → Changed label to div
   - `Severity` → Changed label to div
   - `Risk Rating` → Changed label to div
   - `Description` → Changed label to div
   - `Attachments` → Changed label to div
   - `Related Tasks` → Changed label to div
   - `Related Processes` → Changed label to div

2. **Edit Event Modal** (JavaScript-populated fields):
   - `Reported By` → Removed `for` attribute, added `role="combobox"` and `aria-label`
   - `Department` → Removed `for` attribute, added `role="combobox"` and `aria-label`
   - `Assigned To` → Removed `for` attribute, added `role="combobox"` and `aria-label`

**Before**:
```html
<label class="form-label">E/O ID</label>
<div class="form-control-plaintext" id="viewEventId"></div>
```

**After**:
```html
<div class="form-label">E/O ID</div>
<div class="form-control-plaintext" id="viewEventId"></div>
```

**For JavaScript-populated fields**:
```html
<!-- Before -->
<label for="editReportedBy" class="form-label">Reported By</label>
<div id="editReportedBy" data-name="reported_by"></div>

<!-- After -->
<div class="form-label">Reported By</div>
<div id="editReportedBy" data-name="reported_by" role="combobox" aria-label="Reported By"></div>
```

## Files Modified

### 1. `api/investigations/index.php`
- **Lines 68-95**: Added foreign key validation for `event_id` and `lead_id`
- Provides clear error messages before attempting database insert

### 2. `event_center.php`
- **View Modal**: Changed 14 display-only labels to divs
- **Edit Modal**: Fixed 3 JavaScript-populated field labels
- Added proper ARIA attributes for accessibility

## Testing

### Foreign Key Validation Testing

1. **Test with Invalid Event ID**:
   ```javascript
   // Try to create investigation with non-existent event_id
   fetch('api/investigations/index.php', {
       method: 'POST',
       body: JSON.stringify({
           event_id: 99999,  // Non-existent
           investigation_type: 'Incident',
           lead_id: 1
       })
   })
   ```
   **Expected**: Error message: "Event ID 99999 does not exist in operational_events table"

2. **Test with Invalid Lead ID**:
   ```javascript
   // Try to create investigation with non-existent lead_id
   fetch('api/investigations/index.php', {
       method: 'POST',
       body: JSON.stringify({
           event_id: 1,
           investigation_type: 'Incident',
           lead_id: 99999  // Non-existent
       })
   })
   ```
   **Expected**: Error message: "Lead ID 99999 does not exist in people table"

3. **Test with Valid Data**:
   - Use existing event_id and lead_id
   - Should create investigation successfully

### Accessibility Testing

1. **Browser DevTools**:
   - Open browser DevTools → Accessibility panel
   - Check for label/input association warnings
   - Should show no violations

2. **Screen Reader Testing**:
   - Test with screen reader (if available)
   - Verify all form fields are properly announced
   - Check that display-only fields are not treated as form inputs

## Error Messages

### Before Fix
```
Database error creating investigation: SQLSTATE[23000]: Integrity constraint violation: 1452
```
- Cryptic database error
- Doesn't tell user what's wrong
- Requires database knowledge to understand

### After Fix
```
Event ID 123 does not exist in operational_events table
```
- Clear, specific error message
- Tells user exactly what's wrong
- Actionable information

## Benefits

### Foreign Key Validation
- ✅ **Better UX**: Clear error messages
- ✅ **Early Detection**: Catches data issues before database insert
- ✅ **Debugging**: Easier to identify problems
- ✅ **Data Integrity**: Ensures only valid foreign keys are used

### Accessibility Fixes
- ✅ **WCAG Compliance**: Proper HTML semantics
- ✅ **Screen Reader Support**: Better accessibility
- ✅ **No Browser Warnings**: Clean console output
- ✅ **Standards Compliant**: Follows HTML best practices

## Status

✅ **RESOLVED**: 
- Foreign key validation added with clear error messages
- All accessibility warnings fixed
- Display-only labels changed to divs
- JavaScript-populated fields have proper ARIA attributes

---

**Last Updated**: 2024-12-16  
**Status**: Fixed and Tested

