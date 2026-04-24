# Step-by-Step Testing Guide
## Process Management System - Post-Implementation Testing

**Date:** 2024-12-19  
**Purpose:** Guide through migration execution and feature testing

---

## Step 1: Run Migrations 002-005 in phpMyAdmin

### 1.1 Open phpMyAdmin
1. Navigate to: `http://localhost/phpmyadmin`
2. Select database: `sheener`
3. Click on **SQL** tab

### 1.2 Run Migration 002: Enhance process_map Table
1. Open file: `sql/migrations/002_enhance_process_map_table.sql`
2. **Copy ALL content** from the file
3. Paste into phpMyAdmin SQL tab
4. Click **Go**
5. **Expected Result:** "MySQL returned an empty result set" (this is normal for ALTER TABLE)

### 1.3 Run Migration 003: Add process_map_activity
1. Open file: `sql/migrations/003_add_process_map_activity.sql`
2. **Copy ALL content** from the file
3. Paste into phpMyAdmin SQL tab
4. Click **Go**
5. **Expected Result:** "MySQL returned an empty result set"

### 1.4 Run Migration 004: Create process_map_audit
1. Open file: `sql/migrations/004_create_process_map_audit.sql`
2. **Copy ALL content** from the file
3. Paste into phpMyAdmin SQL tab
4. Click **Go**
5. **Expected Result:** "MySQL returned an empty result set"

### 1.5 Run Migration 005: Add process_map_approval
1. Open file: `sql/migrations/005_add_process_map_approval.sql`
2. **Copy ALL content** from the file
3. Paste into phpMyAdmin SQL tab
4. Click **Go**
5. **Expected Result:** "MySQL returned an empty result set"

### 1.6 Verify Migrations
Run this verification query in phpMyAdmin SQL tab:

```sql
-- Check new tables exist
SHOW TABLES LIKE 'process_map_%';

-- Check process_map has new fields
DESCRIBE process_map;

-- Check process_map_activity structure
DESCRIBE process_map_activity;

-- Check process_map_audit structure
DESCRIBE process_map_audit;

-- Check process_map_approval structure
DESCRIBE process_map_approval;
```

**Expected Results:**
- Should see all process_map_* tables
- process_map should show: status, owner_id, department_id, order, description, notes, created_at, updated_at, created_by, updated_by
- All new tables should exist with proper structure

---

## Step 2: Test API Endpoints with New Fields

### 2.1 Test Process Map Detail (with new fields)
Open browser console (F12) and run:

```javascript
fetch('php/api_process_map.php?action=detail&id=1')
    .then(r => r.json())
    .then(data => {
        console.log('✅ API Response:', data);
        console.log('Status:', data.data.status);
        console.log('Owner:', data.data.owner_first_name, data.data.owner_last_name);
        console.log('Department:', data.data.department_name);
        console.log('Order:', data.data.order);
        console.log('Activities Count:', data.data.activities_count);
    })
    .catch(err => console.error('❌ Error:', err));
```

**Expected:** Should return process details with new fields (may be null if not set yet)

### 2.2 Test Create with New Fields
```javascript
fetch('php/api_process_map.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        action: 'create',
        type: 'step',
        text: 'Test Step with New Fields',
        parent: 1,
        status: 'Active',
        description: 'This is a test step',
        order: 1
    })
})
.then(r => r.json())
.then(data => {
    console.log('✅ Created:', data);
    if (data.success) {
        console.log('New ID:', data.data.id);
    }
})
.catch(err => console.error('❌ Error:', err));
```

**Expected:** Should create new process node with new fields

### 2.3 Test Update with New Fields
```javascript
// Replace 1 with an actual process_map ID
fetch('php/api_process_map.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        action: 'update',
        id: 1,
        status: 'Active',
        description: 'Updated description',
        order: 2
    })
})
.then(r => r.json())
.then(data => {
    console.log('✅ Updated:', data);
})
.catch(err => console.error('❌ Error:', err));
```

**Expected:** Should update process node with new fields

### 2.4 Test Activities Link
```javascript
// First, get available activities
fetch('php/api_process_map.php?action=get_entities_for_linking&entity_type=activity')
    .then(r => r.json())
    .then(data => {
        console.log('Available Activities:', data);
        if (data.data && data.data.length > 0) {
            // Link first activity to process_map ID 1
            return fetch('php/api_process_map.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'link',
                    id: 1,
                    entity_type: 'activity',
                    entity_id: data.data[0].activity_id
                })
            });
        }
    })
    .then(r => r && r.json())
    .then(data => {
        if (data) console.log('✅ Linked Activity:', data);
    })
    .catch(err => console.error('❌ Error:', err));
```

**Expected:** Should link activity to process node

### 2.5 Test Reorder Endpoint
```javascript
// Reorder children of parent ID 1
fetch('php/api_process_map.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        action: 'reorder',
        id: 1,
        node_orders: [3, 1, 2] // Example: reorder to 3, 1, 2
    })
})
.then(r => r.json())
.then(data => {
    console.log('✅ Reordered:', data);
})
.catch(err => console.error('❌ Error:', err));
```

**Expected:** Should update order of nodes

### 2.6 Test Recursive Subtree Query
```javascript
fetch('php/api_process_map.php?action=get_subtree&id=1')
    .then(r => r.json())
    .then(data => {
        console.log('✅ Subtree:', data);
        console.log('Nodes in subtree:', data.data.length);
    })
    .catch(err => console.error('❌ Error:', err));
```

**Expected:** Should return all nodes in subtree

---

## Step 3: Test Frontend Features

### 3.1 Open Process Map Diagram
1. Navigate to: `http://localhost/sheener/process_map_diagram.html`
2. Verify page loads without errors
3. Check browser console for any errors (F12)

### 3.2 Test Activities Display
1. Click on any process node
2. Check sidebar for "Activities" section
3. Verify it shows activity count
4. Click "Link Activity" button
5. Verify modal opens with activity list

### 3.3 Test PTW Status Indicators
1. Click on a process node that has permits linked
2. Check sidebar "Permits to Work" section
3. Verify:
   - Status badges are color-coded
   - Active permit summary shows
   - Expiry warnings appear for expiring permits
4. Status colors:
   - Green = Active/Issued
   - Red = Expired/Revoked
   - Yellow = Suspended
   - Blue = Closed
   - Gray = Pending

### 3.4 Test Drag-and-Drop Reordering
1. In Tree view, find a parent node with multiple children
2. Drag a child node and drop it on another sibling
3. Verify:
   - Visual feedback during drag
   - Node order changes after drop
   - Order persists after page refresh
4. Check browser console for any errors

### 3.5 Test Status Badges
1. Click on a process node
2. Check header for status badge
3. Verify badge color matches status:
   - Green = Active
   - Gray = Inactive
   - Orange = Draft
   - Dark Gray = Archived

### 3.6 Test Enhanced Node Details
1. Click on any process node
2. Verify sidebar shows:
   - Status badge
   - Owner name (if set)
   - Department name (if set)
   - Creation date
   - Last update date
   - Description
   - Clickable parent link

---

## Step 4: Create Sample Data for Testing

### 4.1 Create Test Process with All New Fields
Run in phpMyAdmin SQL tab:

```sql
-- Create test process with all new fields
INSERT INTO process_map (
    type, text, parent, status, description, notes, 
    owner_id, department_id, `order`, created_by
) VALUES (
    'process', 
    'Test Process - Full Features', 
    NULL, 
    'Active', 
    'This is a test process to verify all new features work correctly.',
    'Test notes field',
    1, -- Replace with actual people_id
    NULL, -- Replace with actual department_id if exists
    1,
    1 -- Replace with actual people_id
);

-- Get the ID of the created process
SET @test_process_id = LAST_INSERT_ID();

-- Create test steps
INSERT INTO process_map (type, text, parent, status, `order`, created_by) VALUES
('step', 'Test Step 1', @test_process_id, 'Active', 1, 1),
('step', 'Test Step 2', @test_process_id, 'Active', 2, 1),
('step', 'Test Step 3', @test_process_id, 'Active', 3, 1);

-- Create test substeps
INSERT INTO process_map (type, text, parent, status, `order`, created_by) VALUES
('substep', 'Test Substep 1.1', (SELECT id FROM process_map WHERE text = 'Test Step 1' LIMIT 1), 'Active', 1, 1),
('substep', 'Test Substep 1.2', (SELECT id FROM process_map WHERE text = 'Test Step 1' LIMIT 1), 'Active', 2, 1);

SELECT @test_process_id as test_process_id;
```

### 4.2 Link Test Activities
```sql
-- First, check if activities exist
SELECT activity_id, activity_name, status FROM activities LIMIT 5;

-- If activities exist, link one to test process
-- Replace @test_process_id with actual ID from step 4.1
-- Replace @activity_id with actual activity_id
INSERT INTO process_map_activity (process_map_id, activity_id, linked_by)
VALUES (@test_process_id, @activity_id, 1);
```

### 4.3 Link Test Permits (for PTW indicators)
```sql
-- Check if permits exist
SELECT permit_id, permit_type, status, expiry_date FROM permits LIMIT 5;

-- If permits exist, link one to test process
-- Replace @test_process_id with actual ID
-- Replace @permit_id with actual permit_id
INSERT INTO process_map_permit (process_map_id, permit_id, linked_by)
VALUES (@test_process_id, @permit_id, 1);
```

### 4.4 Link Test Tasks
```sql
-- Check if tasks exist
SELECT task_id, task_name, status FROM tasks LIMIT 5;

-- If tasks exist, link one to test process
INSERT INTO process_map_task (process_map_id, task_id, linked_by)
VALUES (@test_process_id, @task_id, 1);
```

### 4.5 Verify Sample Data
```sql
-- Check created process
SELECT * FROM process_map WHERE text LIKE 'Test Process%';

-- Check linked activities
SELECT pm.id, pm.text, a.activity_name, a.status
FROM process_map pm
INNER JOIN process_map_activity pma ON pm.id = pma.process_map_id
INNER JOIN activities a ON pma.activity_id = a.activity_id
WHERE pm.text LIKE 'Test Process%';

-- Check linked permits
SELECT pm.id, pm.text, p.permit_type, p.status, p.expiry_date
FROM process_map pm
INNER JOIN process_map_permit pmp ON pm.id = pmp.process_map_id
INNER JOIN permits p ON pmp.permit_id = p.permit_id
WHERE pm.text LIKE 'Test Process%';

-- Check audit trail
SELECT * FROM process_map_audit 
WHERE process_map_id = @test_process_id
ORDER BY changed_at DESC;
```

---

## Step 5: Verification Checklist

### Schema Verification
- [ ] Migration 002 executed successfully
- [ ] Migration 003 executed successfully
- [ ] Migration 004 executed successfully
- [ ] Migration 005 executed successfully
- [ ] All new tables exist
- [ ] process_map has all new fields
- [ ] All foreign keys are valid

### API Verification
- [ ] Detail endpoint returns new fields
- [ ] Create endpoint accepts new fields
- [ ] Update endpoint updates new fields
- [ ] Activities link/unlink works
- [ ] Reorder endpoint works
- [ ] Subtree query works
- [ ] Audit trail is logged

### Frontend Verification
- [ ] Activities section displays
- [ ] PTW status indicators show
- [ ] Drag-and-drop reordering works
- [ ] Status badges display correctly
- [ ] Enhanced node details show
- [ ] No console errors

### Sample Data Verification
- [ ] Test process created
- [ ] Activities linked
- [ ] Permits linked
- [ ] Tasks linked
- [ ] Audit trail recorded

---

## Troubleshooting

### Migration Errors
- **Error: "Unknown column"** - Field might already exist, check with DESCRIBE
- **Error: "Table already exists"** - Table was already created, skip that migration
- **Error: "Foreign key constraint"** - Check if referenced tables/columns exist

### API Errors
- **404 Not Found** - Check file path and web server is running
- **500 Internal Server Error** - Check PHP error logs
- **Empty response** - Check browser console for CORS or network errors

### Frontend Errors
- **Blank page** - Check browser console for JavaScript errors
- **Features not working** - Clear browser cache (Ctrl+F5)
- **API calls failing** - Check network tab in browser dev tools

---

## Success Criteria

✅ All migrations run without errors  
✅ All API endpoints return expected data  
✅ Frontend displays all new features  
✅ Sample data can be created and linked  
✅ No console errors in browser  
✅ Audit trail records changes  

---

**Ready to begin?** Start with Step 1.1!

