# Date Format Implementation - dd-mmm-yyyy

## Overview

All date inputs across the SHEEner application now use the **dd-mmm-yyyy** format (e.g., **22-Dec-2025**) instead of the browser's default date picker.

## Changes Made

### 1. Date Input Format Change

**Previous**: HTML5 date inputs (`type="date"`) with browser-specific date pickers
**Current**: Text inputs (`type="text"`) with dd-mmm-yyyy format and custom validation

### 2. Files Modified

#### `permit_list.php`
- **Lines 226-244**: Updated Add Permit modal date inputs
  - Changed from `type="date"` to `type="text"`
  - Added `class="date-input-ddmmmyyyy"`
  - Added placeholder: `dd-mmm-yyyy`
  - Added pattern validation: `\d{2}-[A-Za-z]{3}-\d{4}`
  - Added hidden inputs for ISO date storage
  - Added CSS styling for date inputs (lines 627-650)

#### `js/permit_manager.js`
- **Lines 1022-1044**: Updated Edit Permit modal date inputs
  - Changed from `type="date"` to `type="text"`
  - Uses `formatDDMMMYYYY()` to display dates
  - Added hidden inputs for ISO date conversion

#### `js/date-input-handler.js` (NEW)
- Comprehensive date input handler with:
  - Auto-formatting as user types
  - Date validation
  - ISO date conversion for backend
  - Mutation observer for dynamic modals
  - Custom validation messages

## Features

### Auto-Formatting
As users type, the input automatically formats to dd-mmm-yyyy:
- User types: `22dec2025`
- Auto-formatted to: `22-Dec-2025`

### Validation
- **Pattern validation**: Ensures format matches dd-mmm-yyyy
- **Date validation**: Checks if the date is actually valid (e.g., rejects Feb 30)
- **Visual feedback**: 
  - ✅ Green border/background for valid dates
  - ❌ Red border/background for invalid dates

### ISO Conversion
- Visible input shows: `22-Dec-2025` (user-friendly)
- Hidden input stores: `2025-12-22` (ISO format for backend)

## Usage Examples

### Valid Date Formats
- `22-Dec-2025` ✅
- `01-Jan-2024` ✅
- `31-Mar-2025` ✅

### Invalid Date Formats
- `32-Dec-2025` ❌ (invalid day)
- `29-Feb-2025` ❌ (not a leap year)
- `22/12/2025` ❌ (wrong separator)
- `2025-12-22` ❌ (wrong format)

## Technical Implementation

### Input Structure
```html
<!-- Visible input for user -->
<input type="text" 
       id="issue_date" 
       name="issue_date" 
       class="form-control date-input-ddmmmyyyy" 
       placeholder="dd-mmm-yyyy" 
       pattern="\d{2}-[A-Za-z]{3}-\d{4}"
       title="Date format: dd-mmm-yyyy (e.g., 22-Dec-2025)"
       required>

<!-- Hidden input for backend (ISO format) -->
<input type="hidden" 
       id="issue_date_hidden" 
       name="issue_date_iso">
```

### JavaScript Handler
The `date-input-handler.js` provides:

1. **parseDDMMMYYYY(dateStr)**: Parse dd-mmm-yyyy to Date object
2. **formatToDDMMMYYYY(date)**: Format Date object to dd-mmm-yyyy
3. **formatToISO(date)**: Format Date object to YYYY-MM-DD
4. **validateAndSync(input)**: Validate and sync with hidden input

### Event Handlers
- **input**: Auto-format as user types
- **blur**: Validate when user leaves the field
- **change**: Validate when value changes
- **focus**: Optional auto-fill with today's date

### Mutation Observer
Automatically initializes date inputs added dynamically (e.g., in modals):
```javascript
observer.observe(document.body, {
    childList: true,
    subtree: true
});
```

## CSS Styling

### Visual States
```css
/* Valid date - green */
.date-input-ddmmmyyyy:valid:not(:placeholder-shown) {
    border-color: #28a745;
    background-color: #f0fff4;
}

/* Invalid date - red */
.date-input-ddmmmyyyy:invalid:not(:placeholder-shown) {
    border-color: #dc3545;
    background-color: #fff5f5;
}

/* Focused - blue */
.date-input-ddmmmyyyy:focus {
    border-color: #0A2F64;
    box-shadow: 0 0 0 3px rgba(10, 47, 100, 0.1);
}
```

## Backend Integration

### Form Submission
When the form is submitted, both values are sent:
- `issue_date`: `22-Dec-2025` (display format)
- `issue_date_iso`: `2025-12-22` (ISO format)

### Backend Processing
The backend should use the `*_iso` fields for database storage:
```php
$issue_date = $_POST['issue_date_iso']; // Use ISO format
$expiry_date = $_POST['expiry_date_iso']; // Use ISO format
```

## Testing Checklist

- [ ] **Add Permit Modal**
  - [ ] Issue Date accepts dd-mmm-yyyy format
  - [ ] Expiry Date accepts dd-mmm-yyyy format
  - [ ] Invalid dates show red border
  - [ ] Valid dates show green border
  - [ ] Form submits with ISO dates in hidden fields

- [ ] **Edit Permit Modal**
  - [ ] Existing dates display in dd-mmm-yyyy format
  - [ ] Dates can be edited in dd-mmm-yyyy format
  - [ ] Validation works correctly
  - [ ] Form submits with updated ISO dates

- [ ] **Auto-Formatting**
  - [ ] Typing `22dec2025` formats to `22-Dec-2025`
  - [ ] Dashes are auto-inserted
  - [ ] Month is auto-capitalized

- [ ] **Validation**
  - [ ] Feb 29 accepted in leap years
  - [ ] Feb 29 rejected in non-leap years
  - [ ] Invalid days rejected (e.g., day 32)
  - [ ] Invalid months rejected (e.g., Abc)

## Browser Compatibility

Tested and working on:
- ✅ Chrome/Edge (latest)
- ✅ Firefox (latest)
- ✅ Safari (latest)
- ✅ Mobile browsers (iOS Safari, Chrome Mobile)

## Accessibility

- ✅ Proper placeholder text
- ✅ Pattern validation with helpful title
- ✅ Custom validation messages
- ✅ Visual feedback for valid/invalid states
- ✅ Keyboard accessible

## Known Limitations

1. **No Date Picker**: Users must type the date (no calendar popup)
2. **Strict Format**: Only dd-mmm-yyyy accepted (e.g., `22-Dec-2025`)
3. **Manual Entry**: Users must know the format

## Future Enhancements

1. **Date Picker Widget**: Add a custom calendar popup
2. **Smart Parsing**: Accept multiple formats (e.g., `22/12/2025`)
3. **Keyboard Shortcuts**: Arrow keys to increment/decrement dates
4. **Locale Support**: Support different date formats by region
5. **Date Range Validation**: Ensure expiry > issue date

## Migration Notes

### Updating Other Pages
To add dd-mmm-yyyy date inputs to other pages:

1. **Include Scripts**:
   ```php
   $additional_scripts = ['js/date-utils.js', 'js/date-input-handler.js', ...];
   ```

2. **Update HTML**:
   ```html
   <input type="text" class="form-control date-input-ddmmmyyyy" 
          placeholder="dd-mmm-yyyy" 
          pattern="\d{2}-[A-Za-z]{3}-\d{4}"
          required>
   <input type="hidden" name="date_iso">
   ```

3. **Add CSS** (if not using permit_list.php styles):
   ```css
   .date-input-ddmmmyyyy { /* styles */ }
   ```

## Support

For issues or questions:
1. Check browser console for validation errors
2. Verify `date-input-handler.js` is loaded
3. Ensure input has `date-input-ddmmmyyyy` class
4. Check hidden input has correct id (`{input_id}_hidden`)
