# FINAL EDIT MODAL REQUIREMENTS

Based on the View modal screenshot, the Edit modal needs:

## Layout Requirements

### Row 1: E/O ID | Status
- **Left column:** E/O ID (read-only, gray box, full width of left column)
- **Right column:** Status (dropdown, full width of right column)
- **Grid:** 2 equal columns (1fr 1fr)

### Row 2: E/O Type | Reported By  
- **Left column:** E/O Type (dropdown, full width)
- **Right column:** Reported By (searchable dropdown, full width)
- **Grid:** 2 equal columns (1fr 1fr)

### Row 3: Reported Date | Department
- **Left column:** Reported Date (read-only, gray box, full width)
- **Right column:** Department (searchable dropdown, full width)
- **Grid:** 2 equal columns (1fr 1fr)

### Row 4: Secondary Category | Likelihood | Severity | Risk Rating
- **4 EQUAL columns** - NOT 1 wide + 3 narrow
- **Grid:** 4 equal columns (1fr 1fr 1fr 1fr)
- All fields same width

### Row 5: Description
- **Full width:** Textarea

### Row 6: Attachments
- **Full width:** File upload section

### Row 7: Related Tasks
- **Full width:** Tasks section

### Row 8: Related Processes
- **Full width:** Processes section

## Visual Requirements

1. **Spacing:** 20px between each row
2. **Labels:** Dark gray (#475569), above fields
3. **Gray boxes:** #f8f9fa background for read-only fields
4. **Colored borders:** Green/orange borders on risk fields
5. **Equal widths:** Row 4 must have 4 EQUAL columns

## Current Issue

The Edit modal currently shows all fields in Row 1 on the same line, which is wrong.
Need to ensure each `.modal-field-group` creates a NEW ROW with proper spacing.
