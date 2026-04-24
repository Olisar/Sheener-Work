# Entity-Task Linking Implementation Summary

## Overview
This document summarizes the implementation of the entity-task linking system that allows communications, meetings, training sessions, observations, and events/findings to be linked to tasks for comprehensive audit trailing.

## Backend Implementation

### Database Table
The system uses the `entity_task_links` table with the following structure:
- `id` (primary key)
- `sourcetype` (enum: Communication, Meeting, TrainingSession, ObservationReport, EventFinding)
- `sourceid` (bigint - references the source entity)
- `taskid` (int - FK to tasks.task_id)
- `createdby` (int - FK to people.people_id)
- `createdat` (timestamp)

### PHP Endpoints Created

1. **php/get_entity_task_links.php**
   - GET endpoint to retrieve links
   - Parameters:
     - `sourcetype` + `sourceid`: Get all tasks linked to a source
     - `taskid`: Get all sources linked to a task
   - Returns: Array of links with task and source details

2. **php/create_entity_task_link.php**
   - POST endpoint to create a new link
   - Parameters: `sourcetype`, `sourceid`, `taskid`, `createdby`
   - Validates: Task exists, link doesn't already exist
   - Logs to auditlog

3. **php/delete_entity_task_link.php**
   - POST/DELETE endpoint to remove a link
   - Parameters: `link_id` OR (`sourcetype`, `sourceid`, `taskid`)
   - Logs to auditlog

## Frontend Implementation

### Event Pages (event_list.php)

**Added Features:**
1. **Linked Tasks Section** in view modal
   - Displays all tasks linked to the event
   - Shows task name, description, status, creator, and creation date
   - Clickable links to task details
   - Unlink button for each task

2. **Link Task Button**
   - Opens modal to select and link a task
   - Dropdown of available tasks
   - Creates link via API

3. **Link Task Modal**
   - Simple dropdown interface
   - Loads all available tasks
   - Creates link on confirmation

**Functions Added:**
- `loadLinkedTasks(sourcetype, sourceid)` - Loads and displays linked tasks
- `deleteTaskLink(linkId, sourcetype, sourceid)` - Removes a link
- `openLinkTaskModal(sourcetype, sourceid)` - Opens linking modal
- `createTaskLink()` - Creates new link via API

### Task Pages (task_center.html, js/task_manager.js)

**Added Features:**
1. **Source References Section** in view modal
   - Displays all sources linked to the task
   - Shows source type, details, creator, and creation date
   - Clickable links to source entities (events, training, etc.)
   - Unlink button for each source

**Functions Added:**
- `loadTaskSourceReferences(taskId)` - Loads and displays source references
- `deleteTaskSourceLink(linkId, taskId)` - Removes a link

## Usage Examples

### Linking a Task to an Event
1. Open event view modal from event_list.php
2. Click "Link Task" button
3. Select task from dropdown
4. Click "Link Task" to confirm
5. Task appears in "Related Tasks" section

### Viewing Source References for a Task
1. Open task view modal from task_center.html
2. Scroll to "Source References" section
3. View all events, training sessions, etc. linked to this task
4. Click links to navigate to source entities

## Extending to Other Source Types

The system is designed to be easily extensible. To add linking for other source types:

### For Training Pages:
1. Add "Related Tasks" section to training assignment view
2. Add "Link Task" button
3. Use the same functions with `sourcetype = 'TrainingSession'` and `sourceid = assignment_id`

### For Observation Reports:
1. Add similar UI components
2. Use `sourcetype = 'ObservationReport'`
3. Follow the same pattern as events

### For Communications/Meetings:
1. Create view modals if they don't exist
2. Add linking UI components
3. Use appropriate sourcetype values

## API Usage

### Get Links for a Source
```javascript
fetch(`php/get_entity_task_links.php?sourcetype=EventFinding&sourceid=123`)
  .then(r => r.json())
  .then(data => {
    // data.data contains array of links
  });
```

### Get Links for a Task
```javascript
fetch(`php/get_entity_task_links.php?taskid=456`)
  .then(r => r.json())
  .then(data => {
    // data.data contains array of links
  });
```

### Create a Link
```javascript
fetch('php/create_entity_task_link.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    sourcetype: 'EventFinding',
    sourceid: 123,
    taskid: 456,
    createdby: 1
  })
});
```

### Delete a Link
```javascript
fetch('php/delete_entity_task_link.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ link_id: 789 })
});
```

## Benefits

1. **Bidirectional Linking**: Navigate from events to tasks and from tasks to events
2. **Audit Trail**: All link creation/deletion is logged in auditlog
3. **Flexible**: Works with any source type defined in the enum
4. **User-Friendly**: Simple UI with clear visual indicators
5. **Extensible**: Easy to add new source types

## Notes

- The system assumes the `entity_task_links` table exists in the database
- Default `createdby` is set to 1 (system user) if not provided - adjust based on session management
- All links are validated before creation (task exists, no duplicates)
- Source details are fetched dynamically based on sourcetype

