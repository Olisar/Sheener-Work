# Modal Standardization Documentation

## Overview

All modals in the project have been standardized to ensure consistent positioning, z-index hierarchy, styling, and behavior. The View Event/Observation Details modal in `event_center.php` serves as the reference implementation.

## Files Created

### 1. `css/modal.css`
Centralized CSS file containing all modal styles:
- Standardized z-index hierarchy
- Consistent positioning above topbar
- Unified button styles
- Responsive design
- Accessibility features

### 2. `js/modal.js`
Centralized JavaScript utility for modal management:
- `ModalManager` class for programmatic control
- Convenience functions for backward compatibility
- Automatic ESC key and click-outside-to-close handling
- Focus management
- Body scroll prevention

## Z-Index Hierarchy

The standardized z-index hierarchy ensures modals always appear above the topbar:

- **Topbar**: `1100` (defined in `css/styles.css`)
- **Standard Modals**: `1200` (above topbar)
- **Nested Modals**: `1300` (above standard modals)
- **Overlay Modals**: `1400` (highest priority)

## Usage

### Basic HTML Structure

```html
<!-- Standard Modal -->
<div id="myModal" class="modal-overlay hidden">
    <div class="modal-content">
        <h3 class="modal-header">
            <div class="title-text">Modal Title</div>
            <div class="header-icons">
                <img src="img/close.svg" alt="Close" onclick="closeModal('myModal')" class="edit-icon">
            </div>
        </h3>
        <div class="modal-body">
            <!-- Modal content here -->
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('myModal')">Cancel</button>
            <button class="btn btn-primary" onclick="saveData()">Save</button>
        </div>
    </div>
</div>
```

### Nested Modal (Opened from another modal)

```html
<div id="nestedModal" class="modal-overlay modal-nested hidden">
    <!-- Same structure as standard modal -->
</div>
```

### High Priority Modal (Above nested modals)

```html
<div id="highPriorityModal" class="modal-overlay modal-overlay-high hidden">
    <!-- Same structure as standard modal -->
</div>
```

### Modal Size Variants

Add size classes to `.modal-content`:
- `modal-sm` - 400px max-width
- `modal-md` - 700px max-width (default)
- `modal-lg` - 900px max-width
- `modal-xl` - 1200px max-width

```html
<div class="modal-content modal-lg">
    <!-- Large modal content -->
</div>
```

### JavaScript Usage

#### Using ModalManager (Recommended)

```javascript
// Open a modal
modalManager.open('myModal');

// Close a modal
modalManager.close('myModal');

// Close all modals
modalManager.closeAll();

// Check if modal is open
if (modalManager.isOpen('myModal')) {
    // Do something
}
```

#### Using Convenience Functions

```javascript
// Open
openModal('myModal');

// Close
closeModal('myModal');

// Close all
closeAllModals();

// Check status
if (isModalOpen('myModal')) {
    // Do something
}
```

#### Setup Modal with Behaviors

```javascript
setupModal('myModal', {
    clickOutside: true,  // Close when clicking outside
    escKey: true,        // Close on ESC key
    onOpen: function(modal) {
        console.log('Modal opened');
    },
    onClose: function(modal) {
        console.log('Modal closed');
    }
});
```

### Form Fields Layout

Use the standardized field layout classes:

```html
<div class="modal-field-group">
    <div class="modal-field-row">
        <div class="modal-field">
            <label class="form-label">Field 1</label>
            <input type="text" class="form-control" />
        </div>
        <div class="modal-field">
            <label class="form-label">Field 2</label>
            <input type="text" class="form-control" />
        </div>
    </div>
</div>

<!-- Full-width field -->
<div class="modal-field-group">
    <div class="modal-field-row">
        <div class="modal-field modal-field-full">
            <label class="form-label">Full Width Field</label>
            <textarea class="form-control"></textarea>
        </div>
    </div>
</div>

<!-- 4-column layout -->
<div class="modal-field-group">
    <div class="modal-field-row modal-field-row-4">
        <div class="modal-field">...</div>
        <div class="modal-field">...</div>
        <div class="modal-field">...</div>
        <div class="modal-field">...</div>
    </div>
</div>
```

## Button Styles

Standardized button classes for modal footers:

- `.btn-secondary` - Gray cancel button
- `.btn-primary` - Blue primary action button
- `.btn-success` - Green save/success button
- `.btn-danger` - Red delete/danger button
- `.btn-warning` - Yellow warning button

All buttons have consistent:
- Padding: `10px 20px`
- Border radius: `5px`
- Font weight: `600`
- Hover effects with slight lift animation

## Updated Files

The following files have been updated to use the standardized modal system:

1. **`includes/header.php`** - Added modal.css and modal.js includes
2. **`event_center.php`** - Removed duplicate styles, uses centralized CSS
3. **`sop_list.php`** - Removed duplicate styles, uses centralized CSS
4. **`event_list.php`** - Removed duplicate styles, uses centralized CSS
5. **`js/permit_manager.js`** - Updated to use modal-nested and modal-overlay-high classes

## Migration Guide

### For Existing Modals

1. **Remove inline z-index styles** - The centralized CSS handles this
2. **Add `modal-overlay` class** - Required base class
3. **Add `hidden` class** - For initially hidden modals
4. **Use standardized structure** - Follow the HTML structure above
5. **Update JavaScript** - Use `modalManager` or convenience functions

### Before (Old Way)

```html
<div id="myModal" style="position: fixed; z-index: 1000; ...">
    <div style="background: white; padding: 20px; ...">
        <!-- Content -->
    </div>
</div>
```

### After (New Way)

```html
<div id="myModal" class="modal-overlay hidden">
    <div class="modal-content">
        <h3 class="modal-header">
            <div class="title-text">Title</div>
            <div class="header-icons">
                <img src="img/close.svg" onclick="closeModal('myModal')" class="edit-icon">
            </div>
        </h3>
        <div class="modal-body">
            <!-- Content -->
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('myModal')">Close</button>
        </div>
    </div>
</div>
```

## Best Practices

1. **Always use the centralized CSS** - Don't create duplicate modal styles
2. **Use appropriate z-index classes** - `modal-nested` for nested modals, `modal-overlay-high` for critical modals
3. **Follow the HTML structure** - Use the standardized header, body, footer structure
4. **Use ModalManager** - Prefer programmatic control over manual class toggling
5. **Test on different screen sizes** - The system is responsive, but test your content
6. **Maintain accessibility** - The system includes ARIA attributes and keyboard navigation

## Troubleshooting

### Modal appears behind topbar
- Ensure the modal has the `modal-overlay` class
- Check that `css/modal.css` is included in the page
- Verify no inline styles are overriding the z-index

### Modal doesn't close on ESC
- Ensure `js/modal.js` is loaded
- Check that the modal has an ID
- Verify the modal is using the `modal-overlay` class

### Modal content overflows
- Use `modal-body` class for scrollable content
- Check max-height settings in modal.css
- Ensure proper flex layout structure

## Future Enhancements

Potential improvements for the modal system:
- Animation customization options
- Modal size presets
- Drag-to-reposition functionality
- Modal stacking visualization
- Accessibility improvements (ARIA live regions)

## Support

For questions or issues with the modal system, refer to:
- `css/modal.css` - All styles and classes
- `js/modal.js` - All JavaScript functions
- This documentation file

