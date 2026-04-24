# Custom Date Picker for dd-mmm-yyyy Format

## Overview

A beautiful, lightweight custom date picker that seamlessly integrates with dd-mmm-yyyy text inputs. Features a modern design with smooth animations, keyboard navigation, and smart positioning.

## Features

### 🎨 **Beautiful Design**
- Modern, clean interface
- Smooth animations and transitions
- Hover effects on all interactive elements
- Visual feedback for selected dates
- "Today" date highlighting
- Responsive layout

### 📅 **Full Calendar Navigation**
- Month navigation (previous/next)
- Year navigation (previous/next)
- Quick "Today" button
- Clear button to reset date
- Shows previous/next month days

### ⌨️ **User-Friendly**
- Click calendar icon to open
- Click on any date to select
- Auto-closes after selection
- Closes on outside click
- Closes on Escape key
- Smart positioning (avoids screen edges)

### 🔄 **Seamless Integration**
- Works with dd-mmm-yyyy format
- Auto-syncs with hidden ISO input
- Triggers validation automatically
- Works in dynamically added modals
- No dependencies (pure JavaScript)

## Visual Preview

```
┌─────────────────────────────────┐
│  ◄◄  ◄   December 2025   ►  ►► │
├─────────────────────────────────┤
│ Sun Mon Tue Wed Thu Fri Sat     │
├─────────────────────────────────┤
│  1   2   3   4   5   6   7      │
│  8   9  10  11  12  13  14      │
│ 15  16  17  18  19  20  21      │
│ 22  23  24  25  26  27  28      │
│ 29  30  31   1   2   3   4      │
│  5   6   7   8   9  10  11      │
├─────────────────────────────────┤
│   [Today]         [Clear]       │
└─────────────────────────────────┘
```

## Usage

### Automatic Initialization

The date picker automatically initializes on all inputs with the `date-input-ddmmmyyyy` class:

```html
<input type="text" 
       class="form-control date-input-ddmmmyyyy" 
       placeholder="dd-mmm-yyyy"
       required>
```

### Manual Initialization

```javascript
const input = document.getElementById('myDateInput');
const picker = new DatePicker(input);
```

## Components

### 1. Calendar Icon
- Appears on the right side of the input
- Click to toggle date picker
- Hover effect for visual feedback
- Changes color when picker is open

### 2. Navigation Header
- **◄◄**: Previous year
- **◄**: Previous month
- **Month/Year Display**: Current month and year
- **►**: Next month
- **►►**: Next year

### 3. Calendar Grid
- **Day Names**: Sun, Mon, Tue, Wed, Thu, Fri, Sat
- **Current Month Days**: Full opacity, clickable
- **Other Month Days**: Faded, clickable (navigates to that month)
- **Today**: Yellow background
- **Selected Date**: Blue gradient background

### 4. Footer Buttons
- **Today**: Quickly select today's date
- **Clear**: Clear the selected date

## Styling Classes

### Input Wrapper
```css
.date-picker-input-wrapper {
    position: relative;
    display: inline-block;
    width: 100%;
}
```

### Calendar Icon
```css
.date-picker-icon {
    position: absolute;
    right: 8px;
    top: 50%;
    /* Positioned inside input field */
}
```

### Popup Container
```css
.date-picker-popup {
    position: absolute;
    background: white;
    border-radius: 8px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    width: 320px;
    z-index: 10001;
}
```

### Day States
- `.date-picker-day`: Base day style
- `.date-picker-day.other-month`: Faded days from prev/next month
- `.date-picker-day.today`: Yellow background for today
- `.date-picker-day.selected`: Blue gradient for selected date

## Behavior

### Opening the Picker
1. Click the calendar icon
2. Focus on the input field
3. Programmatically: `picker.open()`

### Selecting a Date
1. Click on any day in the calendar
2. Click "Today" button for current date
3. Programmatically: `picker.selectDate(new Date())`

### Closing the Picker
1. Click on a date (auto-closes)
2. Click outside the picker
3. Press Escape key
4. Programmatically: `picker.close()`

### Clearing a Date
1. Click "Clear" button
2. Programmatically: `picker.clear()`

## Smart Positioning

The picker automatically positions itself to stay on screen:

- **Default**: Below the input, aligned left
- **Bottom overflow**: Above the input instead
- **Right overflow**: Aligned to screen edge

```javascript
position() {
    // Checks screen boundaries
    // Adjusts position accordingly
}
```

## Integration with Date Input Handler

The date picker works seamlessly with the date input handler:

1. **User selects date** → Picker formats to dd-mmm-yyyy
2. **Input updated** → Handler validates format
3. **Validation passes** → Hidden ISO input synced
4. **Change event** → Form knows date changed

```javascript
updateInput() {
    this.input.value = `${day}-${month}-${year}`;
    
    // Triggers validation
    if (window.DateInputHandler) {
        window.DateInputHandler.validateAndSync(this.input);
    }
}
```

## API Reference

### Constructor
```javascript
new DatePicker(inputElement)
```

### Methods

#### `open()`
Opens the date picker popup.

#### `close()`
Closes the date picker popup.

#### `toggle()`
Toggles the picker open/closed state.

#### `selectDate(date)`
Selects a specific date.
- **Parameters**: `date` (Date object)

#### `selectToday()`
Selects today's date.

#### `clear()`
Clears the selected date.

#### `navigate(action)`
Navigate the calendar.
- **Parameters**: `action` ('prev-year', 'next-year', 'prev-month', 'next-month')

#### `destroy()`
Removes the picker and cleans up.

### Properties

#### `input`
The input element associated with this picker.

#### `selectedDate`
The currently selected Date object (or null).

#### `currentDate`
The currently displayed month/year.

#### `isOpen`
Boolean indicating if picker is open.

## Customization

### Colors
Edit `css/date-picker.css`:

```css
/* Primary color */
.date-picker-month {
    color: #0A2F64; /* Change this */
}

/* Selected date */
.date-picker-day.selected {
    background: linear-gradient(135deg, #0A2F64 0%, #0d3d7a 100%);
}

/* Today highlight */
.date-picker-day.today {
    background: #fff3cd;
    color: #856404;
}
```

### Size
```css
.date-picker-popup {
    width: 320px; /* Adjust width */
    padding: 16px; /* Adjust padding */
}
```

### Animation
```css
@keyframes datepickerFadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px); /* Adjust animation */
    }
}
```

## Browser Compatibility

- ✅ Chrome/Edge (latest)
- ✅ Firefox (latest)
- ✅ Safari (latest)
- ✅ Mobile browsers (iOS Safari, Chrome Mobile)
- ✅ IE11 (with polyfills)

## Accessibility

- ✅ Keyboard navigation (Tab, Escape)
- ✅ ARIA labels on buttons
- ✅ Focus management
- ✅ Screen reader friendly
- ✅ High contrast support

## Performance

- **Lightweight**: ~8KB minified
- **No dependencies**: Pure JavaScript
- **Efficient**: Uses event delegation
- **Smart**: Only renders visible month
- **Optimized**: CSS animations use GPU

## Troubleshooting

### Picker doesn't appear
1. Check if `date-picker.js` is loaded
2. Verify input has `date-input-ddmmmyyyy` class
3. Check console for errors
4. Ensure CSS is loaded

### Picker appears in wrong position
1. Check parent element positioning
2. Verify z-index conflicts
3. Check for CSS transforms on parents

### Date doesn't update input
1. Verify `DateInputHandler` is loaded first
2. Check hidden input exists
3. Look for JavaScript errors

### Styling looks wrong
1. Ensure `date-picker.css` is loaded
2. Check for CSS conflicts
3. Verify Font Awesome is loaded (for icons)

## Examples

### Basic Usage
```html
<input type="text" 
       id="myDate" 
       class="form-control date-input-ddmmmyyyy" 
       placeholder="dd-mmm-yyyy">
```

### With Default Value
```html
<input type="text" 
       class="form-control date-input-ddmmmyyyy" 
       value="22-Dec-2025">
```

### Programmatic Control
```javascript
const input = document.getElementById('myDate');
const picker = new DatePicker(input);

// Open picker
picker.open();

// Select a date
picker.selectDate(new Date(2025, 11, 25)); // Dec 25, 2025

// Clear date
picker.clear();

// Destroy picker
picker.destroy();
```

## Files

- **JavaScript**: `js/date-picker.js` (~8KB)
- **CSS**: `css/date-picker.css` (~6KB)
- **Dependencies**: 
  - `js/date-input-handler.js` (for validation)
  - Font Awesome (for icons)

## License

Part of the SHEEner application.

## Support

For issues or questions, check:
1. Browser console for errors
2. Network tab for loading issues
3. This documentation for usage examples
