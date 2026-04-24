# Process Map Linkages - Quick Reference Guide

## Entity Types Supported

| Entity Type | Junction Table | Reference Table | Status |
|------------|----------------|-----------------|--------|
| **Event** | `process_map_event` | `events` | ✅ NEW |
| **Operational Event** | `process_map_operational_event` | `operational_events` | ✅ NEW |
| **Permit** | `process_map_permit` | `permits` | ✅ NEW |
| **OFI** | `process_map_ofi` | `ofi_details` | ✅ NEW |
| **Task** | `process_map_task` | `tasks` | ✅ NEW |
| **HIRA** | `process_map_hira` | `hira_register` | ✅ NEW |
| **Risk** | `process_map_risk` | `risks` | ✅ EXISTS |
| **Document** | `process_map_document` | `documents` | ✅ EXISTS |
| **People** | `process_map_people` | `people` | ✅ EXISTS |
| **Equipment** | `process_map_equipment` | `equipment` | ✅ EXISTS |
| **Material** | `process_map_material` | `materials` | ✅ EXISTS |
| **Energy** | `process_map_energy` | `energy` | ✅ EXISTS |
| **Area** | `process_map_area` | `areas` | ✅ EXISTS |

## API Usage Examples

### Link a Task to a Process Node
```javascript
fetch('php/api_process_map.php?action=link', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        process_map_id: 1,
        entity_type: 'task',
        entity_id: 5,
        linked_by: 10,
        notes: 'Task required for this process step'
    })
});
```

### Unlink a Task
```javascript
fetch('php/api_process_map.php?action=unlink', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        process_map_id: 1,
        entity_type: 'task',
        entity_id: 5
    })
});
```

### Get All Linked Tasks
```javascript
fetch('php/api_process_map.php?action=get_links&id=1&entity_type=task')
    .then(r => r.json())
    .then(data => console.log(data));
```

### Get Process Node Detail (includes all linkage counts)
```javascript
fetch('php/api_process_map.php?action=detail&id=1')
    .then(r => r.json())
    .then(data => {
        console.log('Tasks:', data.data.tasks_count);
        console.log('Events:', data.data.events_count);
        console.log('Permits:', data.data.permits_count);
        // ... etc
    });
```

## SQL Examples

### Link a Task (Direct SQL)
```sql
INSERT INTO process_map_task (process_map_id, task_id, linked_by, notes)
VALUES (1, 5, 10, 'Task required for this process step');
```

### Get All Links for a Process Node
```sql
SELECT 
    t.task_id,
    t.task_name,
    t.status,
    pmt.linked_date,
    pmt.notes
FROM process_map_task pmt
INNER JOIN tasks t ON pmt.task_id = t.task_id
WHERE pmt.process_map_id = 1;
```

### Count Links by Type
```sql
SELECT 
    'Events' as type, COUNT(*) as count FROM process_map_event WHERE process_map_id = 1
UNION ALL
SELECT 'Tasks', COUNT(*) FROM process_map_task WHERE process_map_id = 1
UNION ALL
SELECT 'Permits', COUNT(*) FROM process_map_permit WHERE process_map_id = 1
UNION ALL
SELECT 'OFIs', COUNT(*) FROM process_map_ofi WHERE process_map_id = 1;
```

## Common Patterns

### Check if Entity is Linked
```sql
SELECT COUNT(*) > 0 as is_linked
FROM process_map_task
WHERE process_map_id = 1 AND task_id = 5;
```

### Get Process Nodes with Specific Entity
```sql
SELECT DISTINCT pm.id, pm.text, pm.type
FROM process_map pm
INNER JOIN process_map_task pmt ON pm.id = pmt.process_map_id
WHERE pmt.task_id = 5;
```

### Bulk Link Multiple Tasks
```sql
INSERT INTO process_map_task (process_map_id, task_id, linked_by)
VALUES 
    (1, 5, 10),
    (1, 6, 10),
    (1, 7, 10);
```

## Entity Type Values for API

When using the `entity_type` parameter, use these exact values:

- `event` - Links to `events` table
- `operational_event` - Links to `operational_events` table
- `permit` - Links to `permits` table
- `ofi` - Links to `ofi_details` table
- `task` - Links to `tasks` table
- `hira` - Links to `hira_register` table
- `risk` - Links to `risks` table
- `document` - Links to `documents` table
- `people` - Links to `people` table
- `equipment` - Links to `equipment` table
- `material` - Links to `materials` table
- `energy` - Links to `energy` table
- `area` - Links to `areas` table

---

**Quick Tip:** All junction tables follow the pattern `process_map_{entity_type}` where entity_type matches the API parameter value.

