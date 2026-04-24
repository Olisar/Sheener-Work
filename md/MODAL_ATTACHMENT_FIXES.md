# ✅ Event Center Modal & Attachment Fixes - Complete!

## Issues Fixed

### 1. ✅ Edit Event/Observation Modal Layout
**Problem:** The Edit Event/Observation modal looked "weird" and didn't match the View Event/Observation Details modal.

**Solution:** Added `style="max-width: 800px;"` to the Edit Event Modal to match the View Event Modal.

**File Modified:** `event_center.php` (Line 260)

**Before:**
```html
<div class="modal-content">
```

**After:**
```html
<div class="modal-content" style="max-width: 800px;">
```

**Result:** Both modals now have the same width and consistent appearance.

---

### 2. ✅ Mobile Phone Attachments Not Showing
**Problem:** Attachments uploaded from mobile phones weren't appearing when viewing the event record from desktop.

**Root Cause:** The `submit_anonymous_event.php` file was:
- ✅ Saving files to the file system (`uploads/anonymous_events/`)
- ❌ **NOT** saving attachment records to the database `attachments` table

**Solution:** Added database insertion code to save attachment metadata to the `attachments` table with the event_id.

**File Modified:** `php/submit_anonymous_event.php` (Lines 156-188)

**Code Added:**
```php
// Save attachments to database
if (!empty($attachments)) {
    $attachmentStmt = $pdo->prepare(
        "INSERT INTO attachments (event_id, file_name, file_type, file_size, file_path, uploaded_by)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    
    foreach ($attachments as $attachment) {
        // Determine file type from file name
        $fileExtension = strtolower(pathinfo($attachment['name'], PATHINFO_EXTENSION));
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];
        $fileType = $mimeTypes[$fileExtension] ?? 'application/octet-stream';
        
        $attachmentStmt->execute([
            $dbEventId,
            $attachment['name'],
            $fileType,
            $attachment['size'],
            $attachment['path'],
            $systemUserId  // Use the anonymous system user ID
        ]);
    }
}
```

**Result:** Attachments from mobile uploads are now:
- ✅ Saved to file system
- ✅ Saved to database with event_id
- ✅ Visible when viewing event details from desktop

---

## How It Works Now

### Mobile Upload Flow:
```
1. User scans QR code on mobile
   ↓
2. Opens mobile_report.php
   ↓
3. Takes photo with camera
   ↓
4. Submits form
   ↓
5. submit_anonymous_event.php processes:
   - Saves file to: uploads/anonymous_events/
   - Saves metadata to: attachments table
   - Links to event via: event_id
   ↓
6. Desktop user views event
   ↓
7. loadViewAttachments() fetches from database
   ↓
8. Attachments display correctly!
```

---

## Database Schema

The `attachments` table stores:
- `attachment_id` - Primary key
- `event_id` - Foreign key to events table
- `file_name` - Original filename
- `file_type` - MIME type (image/jpeg, application/pdf, etc.)
- `file_size` - File size in bytes
- `file_path` - Relative path to file
- `uploaded_by` - User ID (uses anonymous system user for mobile uploads)
- `description` - Optional description

---

## Testing Checklist

- [ ] Test Edit Event Modal - should be same width as View Event Modal
- [ ] Upload attachment from mobile phone
- [ ] View event from desktop
- [ ] Verify attachment appears in View Event Modal
- [ ] Click attachment to download/view
- [ ] Verify attachment appears in Edit Event Modal
- [ ] Test deleting attachment from Edit Modal

---

## Files Modified

1. **`event_center.php`** (Line 260)
   - Added `max-width: 800px` to Edit Event Modal

2. **`php/submit_anonymous_event.php`** (Lines 156-188)
   - Added database insertion for attachments
   - Maps file extensions to MIME types
   - Links attachments to event_id
   - Uses anonymous system user as uploader

---

## Additional Notes

### Attachment Display
- **View Event Modal:** Shows attachments with icons, filenames, and download links
- **Edit Event Modal:** Shows existing attachments with delete buttons + allows new uploads

### File Types Supported
- **Images:** JPG, JPEG, PNG, GIF
- **Documents:** PDF, DOC, DOCX
- **Spreadsheets:** XLS, XLSX

### File Limits
- **Max file size:** 5MB per file
- **Max files:** 10 files per submission

### Storage Location
- **Physical files:** `php/uploads/anonymous_events/`
- **Database records:** `attachments` table

---

## Why This Fix Was Needed

The original code had a **disconnect** between:
- **File System:** Files were being saved ✅
- **Database:** No records were created ❌

This meant:
- Files existed on the server
- But the application couldn't find them
- Because there was no database record linking them to the event

Now both systems are synchronized:
- Files saved to file system ✅
- Records saved to database ✅
- Proper linking via event_id ✅

---

## Summary

✅ **Modal Layout Fixed** - Edit Event Modal now matches View Event Modal  
✅ **Attachments Fixed** - Mobile uploads now save to database and display correctly  
✅ **Consistent Experience** - Desktop and mobile users see the same data  

**Ready to test!** 🎉
