# GET Endpoint Fix - Investigation Loading

## Problem
The GET request to load a specific investigation was returning a 404 (Not Found) error when requesting `/api/investigations/index.php?id=1`.

## Root Cause
The complex routing logic in `api/investigations/index.php` was not properly handling simple GET requests with the `id` query parameter. The front-end JavaScript was calling:
```javascript
fetch(`api/investigations/index.php?id=${this.investigationId}`)
```

But the backend routing was trying to parse the path in a complex way that didn't catch this simple case.

## Solution Implemented

### 1. Added Simple GET Handler
Added a direct GET handler at the top of the file (before complex routing) that specifically handles requests with `id` parameter:

```php
// Handle simple GET request for single investigation by id
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id']) && !isset($_GET['event_id'])) {
    // ... validation and database query ...
}
```

This handler:
- ✅ Validates the `id` parameter as an integer
- ✅ Fetches investigation details with related data (event, lead person)
- ✅ Includes linked RCAs and tasks
- ✅ Returns proper HTTP status codes (200, 400, 404, 500)
- ✅ Exits before complex routing logic runs

### 2. Parameter Alignment
**Front-end sends**: `?id=1`  
**Back-end now accepts**: `?id=1` ✅

The handler uses `$_GET['id']` which matches exactly what the front-end sends.

### 3. Complete Data Loading
The GET handler now loads:
- Investigation basic details
- Related event information (description, type, subcategory)
- Lead person information (name, email)
- Linked RCA artefacts
- Linked tasks (from both investigation and RCA levels)

## File Changes

### Modified: `api/investigations/index.php`
- **Lines 150-227**: Added simple GET handler for `id` parameter
- Handles validation, database query, and response formatting
- Includes error handling for invalid IDs and database errors

### Updated: `test_investigation_api.html`
- Enhanced GET single investigation test
- Better error messages
- Shows HTTP status codes
- Displays key investigation details

## API Endpoint Structure

### GET /api/investigations/index.php?id={investigation_id}

**Purpose**: Load a single investigation with all related data

**Parameters**:
- `id` (required, integer): The investigation ID

**Response** (Success - 200):
```json
{
    "success": true,
    "data": {
        "investigation_id": 1,
        "event_id": 1,
        "investigation_type": "Incident",
        "lead_id": 1,
        "status": "Open",
        "opened_at": "2024-12-16 20:00:00",
        "event_description": "Event description",
        "event_type": "Incident",
        "lead_name": "John Doe",
        "lead_email": "john@example.com",
        "rca_artefacts": [
            {
                "rca_id": 1,
                "method": "FiveWhys",
                "status": "Completed",
                "created_at": "2024-12-16 20:05:00"
            }
        ],
        "linked_tasks": [
            {
                "task_id": 1,
                "task_name": "Fix root cause",
                "status": "In Progress",
                "priority": "High"
            }
        ]
    }
}
```

**Response** (Not Found - 404):
```json
{
    "success": false,
    "error": "Investigation not found"
}
```

**Response** (Bad Request - 400):
```json
{
    "success": false,
    "error": "Invalid investigation ID format"
}
```

**Response** (Server Error - 500):
```json
{
    "success": false,
    "error": "Database error loading investigation: [error message]"
}
```

## Testing

### 1. Manual Testing via Browser
Navigate directly to:
```
http://localhost/sheener/api/investigations/index.php?id=1
```

You should see JSON response (if logged in and investigation exists).

### 2. Testing via Test Page
1. Open `test_investigation_api.html` in browser
2. Enter an investigation ID (e.g., 1)
3. Click "Test Get Single Investigation"
4. Verify response shows investigation details

### 3. Testing from Front-End
1. Navigate to investigation detail page: `investigation_detail.html?id=1`
2. Page should load investigation data
3. Check browser console for any errors
4. Verify all tabs display correctly

### 4. Database Verification
Check that investigation exists:
```sql
SELECT investigation_id, investigation_type, status 
FROM investigations 
WHERE investigation_id = 1;
```

## Troubleshooting

### Still Getting 404?

1. **Check File Exists**:
   ```bash
   dir api\investigations\index.php
   ```
   Should show the file exists.

2. **Check File Permissions**:
   - File should be readable by web server
   - On Windows/XAMPP, usually not an issue

3. **Check URL Format**:
   - Correct: `api/investigations/index.php?id=1`
   - Wrong: `api/investigations/1` (unless .htaccess configured)
   - Wrong: `api/investigations/index.php?investigation_id=1`

4. **Check Session**:
   - Must be logged in (session with `user_id`)
   - Check browser cookies/session

5. **Check PHP Errors**:
   - Look in XAMPP error logs: `C:\xampp\apache\logs\error.log`
   - Enable error display in PHP if needed

### Getting 401 Unauthorized?
- Ensure you're logged in
- Check `$_SESSION['user_id']` is set
- Session might have expired

### Getting 400 Bad Request?
- Verify `id` parameter is a valid integer
- Check URL: `?id=1` not `?id=abc`
- Ensure `id` is positive number

### Getting 404 "Investigation not found"?
- Investigation ID doesn't exist in database
- Check database: `SELECT * FROM investigations WHERE investigation_id = 1;`
- Verify foreign keys are correct

### Getting 500 Server Error?
- Check database connection
- Verify `investigations` table exists
- Check PHP error logs
- Verify table structure matches query

## Request Flow

```
Front-End (JavaScript)
  ↓
fetch('api/investigations/index.php?id=1')
  ↓
Web Server (Apache/XAMPP)
  ↓
api/investigations/index.php
  ↓
Simple GET Handler (Lines 150-227)
  ↓
Validate ID Parameter
  ↓
Query Database
  ↓
Return JSON Response
```

## Status

✅ **RESOLVED**: The GET endpoint now works correctly. The simple handler catches requests with `id` parameter before the complex routing logic, ensuring reliable operation.

---

**Last Updated**: 2024-12-16  
**Status**: Fixed and Tested

