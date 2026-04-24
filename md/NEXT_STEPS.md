# Next Steps - Process Map Linkage Integration

## Current Status: Migration Ready ✅

You've completed the implementation. Here's what to do next:

---

## Step 1: Verify Migration Success ✅

### Check if Tables Were Created

In phpMyAdmin, run this query:
```sql
SHOW TABLES LIKE 'process_map_%';
```

**Expected Result:** You should see these 6 new tables:
- ✅ `process_map_event`
- ✅ `process_map_operational_event`
- ✅ `process_map_permit`
- ✅ `process_map_ofi`
- ✅ `process_map_task`
- ✅ `process_map_hira`

Plus existing tables like `process_map_document`, `process_map_risk`, etc.

### Run Test Script

In phpMyAdmin SQL tab, copy and paste the contents of:
- `sql/migrations/test_migration.sql`

**Expected Result:** All tests should show "PASS"

---

## Step 2: Test API Endpoints ✅

### Test 1: Link a Task

Open browser console or use a tool like Postman:

```javascript
// Test linking a task to a process node
fetch('php/api_process_map.php?action=link', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        process_map_id: 1,  // Replace with actual process_map ID
        entity_type: 'task',
        entity_id: 1,       // Replace with actual task ID
        linked_by: 1,       // Replace with actual user ID
        notes: 'Test link'
    })
})
.then(r => r.json())
.then(data => console.log('Link Result:', data));
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Entity linked successfully",
  "data": {"id": 1}
}
```

### Test 2: Get Linked Entities

```javascript
// Get all tasks linked to a process node
fetch('php/api_process_map.php?action=get_links&id=1&entity_type=task')
    .then(r => r.json())
    .then(data => console.log('Linked Tasks:', data));
```

### Test 3: Get Process Detail (with new counts)

```javascript
// Get process node detail with all linkage counts
fetch('php/api_process_map.php?action=detail&id=1')
    .then(r => r.json())
    .then(data => {
        console.log('Tasks Count:', data.data.tasks_count);
        console.log('Events Count:', data.data.events_count);
        console.log('Permits Count:', data.data.permits_count);
        console.log('OFI Count:', data.data.ofi_count);
        console.log('HIRA Count:', data.data.hira_count);
    });
```

**Expected:** Should include all new count fields

### Test 4: Unlink Entity

```javascript
// Unlink a task
fetch('php/api_process_map.php?action=unlink', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        process_map_id: 1,
        entity_type: 'task',
        entity_id: 1
    })
})
.then(r => r.json())
.then(data => console.log('Unlink Result:', data));
```

---

## Step 3: Test Frontend Integration ✅

### Test the Process Map Diagram

1. **Open Process Map:**
   - Navigate to `process_map_diagram.html` in your browser
   - URL: `http://localhost/sheener/process_map_diagram.html`

2. **Select a Process Node:**
   - Click on any process node in the diagram
   - The sidebar should open on the right

3. **Verify New Sections:**
   Check that sidebar shows:
   - ✅ **Tasks** section with count and "Link Task" button
   - ✅ **Events & Incidents** section with counts
   - ✅ **Permits** section with count
   - ✅ **Opportunities for Improvement** section
   - ✅ **Risk Assessments** section (Risks + HIRA)

4. **Test Link Functionality:**
   - Click "Link Task" button
   - Modal should open showing available tasks
   - Select a task and click "Link Selected"
   - Task should appear in sidebar

5. **Test All Views:**
   - Switch between Tree, Org Chart, and Flow views
   - Verify sidebar works in all views
   - Check that zoom resets when switching views

---

## Step 4: Create Sample Data (Optional) ✅

If you want to test with real data, create some test links:

### In phpMyAdmin SQL Tab:

```sql
-- Get some IDs first (adjust based on your data)
SET @process_id = (SELECT id FROM process_map LIMIT 1);
SET @task_id = (SELECT task_id FROM tasks LIMIT 1);
SET @event_id = (SELECT event_id FROM events LIMIT 1);

-- Link a task
INSERT INTO process_map_task (process_map_id, task_id, notes)
VALUES (@process_id, @task_id, 'Test link for demonstration');

-- Link an event
INSERT INTO process_map_event (process_map_id, event_id, notes)
VALUES (@process_id, @event_id, 'Test event link');

-- Verify links
SELECT * FROM process_map_task WHERE process_map_id = @process_id;
SELECT * FROM process_map_event WHERE process_map_id = @process_id;
```

---

## Step 5: User Training ✅

### Schedule Training Sessions

1. **Identify Key Users:**
   - Process managers
   - Quality assurance staff
   - Safety officers
   - System administrators

2. **Use Training Materials:**
   - `docs/user_training_guide.md` - Complete user guide
   - `docs/process_map_linkages_quick_reference.md` - Quick reference

3. **Training Topics:**
   - How to view linked entities
   - How to link entities to processes
   - How to unlink entities
   - Best practices
   - Common use cases

---

## Step 6: Monitor & Evaluate ✅

### Track Usage Metrics

Create a simple tracking query:

```sql
-- Count total links created
SELECT 
    'Total Links' as metric,
    (SELECT COUNT(*) FROM process_map_event) +
    (SELECT COUNT(*) FROM process_map_operational_event) +
    (SELECT COUNT(*) FROM process_map_permit) +
    (SELECT COUNT(*) FROM process_map_ofi) +
    (SELECT COUNT(*) FROM process_map_task) +
    (SELECT COUNT(*) FROM process_map_hira) as value;

-- Links by type
SELECT 'Events' as type, COUNT(*) as count FROM process_map_event
UNION ALL
SELECT 'Operational Events', COUNT(*) FROM process_map_operational_event
UNION ALL
SELECT 'Permits', COUNT(*) FROM process_map_permit
UNION ALL
SELECT 'OFIs', COUNT(*) FROM process_map_ofi
UNION ALL
SELECT 'Tasks', COUNT(*) FROM process_map_task
UNION ALL
SELECT 'HIRA', COUNT(*) FROM process_map_hira;
```

### Use Evaluation Checklist

Complete the evaluation checklist:
- `docs/outcome_evaluation_checklist.md`

Track:
- User adoption rate
- Number of links created
- Performance metrics
- User feedback

---

## Step 7: Troubleshooting (If Needed) ✅

### Common Issues

**Issue: API returns empty arrays**
- **Check:** Verify junction tables exist
- **Fix:** Re-run migration if needed

**Issue: Frontend doesn't show new sections**
- **Check:** Browser cache (clear cache or hard refresh: Ctrl+F5)
- **Check:** API returns new fields in detail endpoint
- **Fix:** Verify `php/api_process_map.php` is updated

**Issue: Link button doesn't work**
- **Check:** Browser console for JavaScript errors
- **Check:** API endpoint is accessible
- **Fix:** Verify `js/process_map.js` is updated

**Issue: Foreign key constraint errors**
- **Check:** Referenced tables exist and have data
- **Fix:** Ensure `process_map`, `tasks`, `events`, etc. tables exist

---

## Step 8: Production Deployment (When Ready) ✅

### Pre-Deployment Checklist

- [ ] All tests passed on development
- [ ] User acceptance testing complete
- [ ] Performance acceptable
- [ ] Documentation reviewed
- [ ] Training materials ready
- [ ] Backup created
- [ ] Rollback plan ready

### Deployment Steps

1. **Create Production Backup:**
   ```bash
   mysqldump -u root -p sheener > backup_prod_$(date +%Y%m%d_%H%M%S).sql
   ```

2. **Run Migration:**
   - Use same steps as development
   - Monitor for errors
   - Verify tables created

3. **Test in Production:**
   - Test API endpoints
   - Test frontend
   - Verify no errors

4. **Monitor:**
   - Check error logs
   - Monitor performance
   - Collect user feedback

---

## Quick Action Items

### Today:
- [ ] Verify migration successful
- [ ] Test API endpoints
- [ ] Test frontend display

### This Week:
- [ ] Create sample test data
- [ ] Schedule user training
- [ ] Monitor initial usage

### This Month:
- [ ] Complete evaluation checklist
- [ ] Gather user feedback
- [ ] Plan improvements

---

## Support Resources

- **Documentation:** All in `docs/` folder
- **API Reference:** `docs/process_map_linkages_quick_reference.md`
- **User Guide:** `docs/user_training_guide.md`
- **Testing Guide:** `docs/migration_testing_guide.md`
- **Evaluation:** `docs/outcome_evaluation_checklist.md`

---

## Success Indicators

You'll know it's working when:

✅ Tables exist in database  
✅ API endpoints return data  
✅ Frontend shows new sections  
✅ Users can link entities  
✅ Links appear in sidebar  
✅ No errors in console/logs  

---

**Ready to proceed?** Start with Step 1 (Verify Migration) and work through each step systematically.

