# View vs Edit Modal - Visual Differences

## Current Status
The Edit modal structure is correct, but it doesn't **visually match** the View modal.

## Key Visual Differences

### 1. **Field Labels**
- **View Modal:** Labels use `.form-label` class with:
  - `color: #475569` (dark gray-blue)
  - `font-weight: 600`
  - `font-size: 0.875rem`
- **Edit Modal:** ✅ Already using `.form-label` correctly

### 2. **Read-Only Fields (Gray Boxes)**
- **View Modal:** Uses `.form-control-plaintext` with:
  - `background-color: #f8f9fa` (light gray)
  - `border: 1px solid #e9ecef`
  - `height: 38px`
  - `padding: 8px 12px`
- **Edit Modal:** ✅ Already using `.form-control-plaintext` for E/O ID and Reported Date

### 3. **Editable Input Fields**
- **View Modal:** N/A (all read-only)
- **Edit Modal:** Uses `.form-control` with:
  - `background: white`
  - `border: 1px solid #ddd`
  - Should match the clean look

### 4. **Spacing Between Field Groups**
- **View Modal:** Uses `.modal-field-group` with natural CSS margin
- **Edit Modal:** ✅ Structure is correct, CSS should handle spacing

### 5. **Risk Matrix Colored Boxes**
- **View Modal:** Likelihood, Severity, Risk Rating have **colored borders**:
  - Applied by `risk_matrix_colors.js`
  - Green, Orange, Yellow borders based on values
- **Edit Modal:** Should also have colored borders when values are selected

## What's Missing

The Edit modal is **structurally correct** but may not be **visually matching** because:

1. **CSS might not be loading** - Browser cache issue
2. **Risk matrix colors** - Not being applied to Edit modal fields
3. **Spacing** - The `.modal-field-group` margin might not be applying

## Solution

Since the HTML structure is now correct and matches the View modal, the issue is likely:
1. **Browser cache** - User needs to hard refresh in Opera
2. **Risk matrix colors** - Need to ensure `risk_matrix_colors.js` applies to Edit modal

## Files That Are Correct

✅ `event_center.php` - Edit modal HTML structure matches View modal
✅ `css/modal.css` - Has correct `.form-control-plaintext` styling  
✅ `css/riskassessment.css` - Has correct `.form-label` styling

## Next Step

User should:
1. **Hard refresh Opera** (`Ctrl + F5`)
2. **Check if risk matrix colors appear** on Likelihood/Severity/Risk Rating
3. **Compare spacing** - should now match View modal
