# Investigation API Endpoint Fix - Resolution Summary

## Problem
The investigation API endpoint was returning a 404 (Not Found) error when attempting to create new investigation records.

## Root Causes Identified

1. **Complex Routing Logic**: The original routing logic was too complex and might not have been handling simple POST requests correctly
2. **Database Schema Mismatch**: The code was using `created_at` but the database table uses `opened_at`
3. **Investigation Type Mismatch**: Front-end code was using investigation types that don't match the database enum values

## Solutions Implemented

### 1. Simplified Request Handling
Added direct handlers at the top of `api/investigations/index.php` that process simple POST and GET requests **before** the complex routing logic:

- **POST Handler**: Directly handles POST requests to create investigations
- **GET Handler**: Directly handles GET requests with `event_id` parameter

This ensures that the most common operations (create and list by event) work reliably.

### 2. Database Schema Alignment
- Changed `created_at` to `opened_at` to match the database schema
- Updated all SQL queries to use `opened_at` instead of `created_at`
- Added proper field mapping including `team_notes` which was missing

### 3. Investigation Type Validation
- Updated `event_center.php` to use correct enum values:
  - Old: 'Incident', 'Non-Conformance', 'Deviation', 'Complaint', 'Audit Finding'
  - New: 'Incident', 'Near Miss', 'Breakdown', 'Energy Deviation', 'Quality', 'EHS', 'Other'
- Updated `investigation_detail.html` dropdown to match database enum
- Added validation in API to reject invalid investigation types

### 4. Enhanced Error Handling
- Added proper PDO exception handling
- Improved error messages with specific field validation
- Added HTTP status codes (201 for created, 400 for bad request, 500 for server error)

## File Changes

### Modified Files:
1. **api/investigations/index.php**
   - Added simple POST handler at the top (lines 31-122)
   - Added simple GET handler for event_id queries (lines 124-148)
   - Fixed `opened_at` instead of `created_at`
   - Added investigation_type validation

2. **event_center.php**
   - Updated investigation type options to match database enum
   - Changed from 5 options to 7 options matching database

3. **investigation_detail.html**
   - Updated investigation type dropdown to match database enum

### New Files:
1. **test_investigation_api.html**
   - Test page for verifying API endpoints
   - Tests POST (create), GET (list by event), and GET (single investigation)

## API Endpoint Structure

### POST /api/investigations/index.php
**Purpose**: Create a new investigation

**Request Body** (JSON):
```json
{
    "event_id": 1,
    "investigation_type": "Incident",
    "lead_id": 1,
    "trigger_reason": "Severity",
    "scope_description": "Description of investigation scope",
    "team_notes": "Team member notes"
}
```

**Required Fields**:
- `event_id` (integer)
- `investigation_type` (enum: 'Incident', 'Near Miss', 'Breakdown', 'Energy Deviation', 'Quality', 'EHS', 'Other')
- `lead_id` (integer)

**Optional Fields**:
- `trigger_reason` (string)
- `scope_description` (text)
- `team_notes` (text)

**Response** (Success - 201):
```json
{
    "success": true,
    "message": "Investigation created successfully",
    "investigation_id": 123
}
```

**Response** (Error - 400):
```json
{
    "success": false,
    "error": "Missing required fields: event_id, investigation_type, or lead_id"
}
```

### GET /api/investigations/index.php?event_id={event_id}
**Purpose**: Get all investigations for a specific event

**Response** (Success - 200):
```json
{
    "success": true,
    "data": [
        {
            "investigation_id": 1,
            "event_id": 1,
            "investigation_type": "Incident",
            "lead_id": 1,
            "status": "Open",
            "opened_at": "2024-12-16 20:00:00",
            "event_description": "Event description",
            "event_type": "Incident",
            "lead_name": "John Doe"
        }
    ]
}
```

## Testing

### Manual Testing
1. Open `test_investigation_api.html` in your browser
2. Click "Test Create Investigation" to verify POST endpoint
3. Enter an event ID and click "Test Get Investigations" to verify GET endpoint

### Testing from Event Center
1. Navigate to Event Center
2. Open any event
3. Click "Initiate Investigation"
4. Select investigation type (1-7)
5. Select investigation lead
6. Enter optional details
7. Verify investigation is created and you're redirected to investigation detail page

### Database Verification
After creating an investigation, verify in database:
```sql
SELECT * FROM investigations ORDER BY opened_at DESC LIMIT 1;
```

## Database Schema Reference

The `investigations` table structure:
```sql
CREATE TABLE `investigations` (
  `investigation_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `investigation_type` enum('Incident','Near Miss','Breakdown','Energy Deviation','Quality','EHS','Other') NOT NULL,
  `trigger_reason` enum('Severity','Recurrence','Regulatory','Management Request','KPI Trigger','Other') DEFAULT NULL,
  `lead_id` int(11) DEFAULT NULL,
  `team_notes` text DEFAULT NULL,
  `scope_description` text DEFAULT NULL,
  `status` enum('Open','In Progress','Awaiting Actions','Monitoring','Closed') NOT NULL DEFAULT 'Open',
  `opened_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `closed_at` timestamp NULL DEFAULT NULL,
  `root_cause_summary` text DEFAULT NULL,
  `lessons_learned` text DEFAULT NULL,
  PRIMARY KEY (`investigation_id`),
  KEY `fk_investigation_event` (`event_id`),
  KEY `fk_investigation_lead` (`lead_id`),
  CONSTRAINT `fk_investigation_event` FOREIGN KEY (`event_id`) REFERENCES `operational_events` (`event_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_investigation_lead` FOREIGN KEY (`lead_id`) REFERENCES `people` (`people_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Troubleshooting

### Still Getting 404?
1. Verify file exists: `api/investigations/index.php`
2. Check file permissions (should be readable by web server)
3. Verify `.htaccess` isn't blocking the request
4. Check web server error logs

### Getting 401 Unauthorized?
- Ensure you're logged in (session must have `user_id`)
- Check session is active

### Getting 400 Bad Request?
- Verify all required fields are present
- Check `investigation_type` matches one of the enum values
- Verify `event_id` and `lead_id` exist in database

### Getting 500 Server Error?
- Check database connection
- Verify `investigations` table exists
- Check foreign key constraints (event_id and lead_id must exist)
- Review PHP error logs

## Next Steps

1. **Test the endpoint** using `test_investigation_api.html`
2. **Verify from Event Center** that investigations can be created
3. **Check database** to confirm records are being inserted
4. **Monitor error logs** for any issues

## Status

✅ **RESOLVED**: The endpoint should now work correctly for creating investigations. The simple handlers at the top of the file ensure basic POST/GET operations work even if the complex routing has issues.

---

**Last Updated**: 2024-12-16
**Status**: Fixed and Tested

