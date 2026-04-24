# Investigation and Root Cause Analysis (RCA) Front-End User Guide

## Table of Contents
1. [Overview](#overview)
2. [Getting Started](#getting-started)
3. [Investigation Lifecycle](#investigation-lifecycle)
4. [Root Cause Analysis (RCA)](#root-cause-analysis-rca)
5. [Actions and CAPA](#actions-and-capa)
6. [Investigation Closure](#investigation-closure)
7. [Integration with Event Records](#integration-with-event-records)
8. [Troubleshooting](#troubleshooting)

---

## Overview

The Investigation and Root Cause Analysis (RCA) system provides a structured approach to investigating incidents, non-conformances, deviations, complaints, and audit findings. The system enforces a rigorous closure process that ensures all necessary steps are completed before an investigation can be closed.

### Key Features

- **Structured Investigation Process**: Track investigations from initiation to closure
- **Multiple RCA Methods**: Support for 5 Whys and Fishbone Diagram analysis
- **Action Management**: Link corrective and preventive actions (CAPA) to investigations and RCA causes
- **Enforced Closure Logic**: Three-part validation ensures completeness before closure
- **Event Integration**: Seamless integration with operational events and incidents

---

## Getting Started

### Accessing the Investigation System

1. **From Event Center**: 
   - Navigate to the Event/Observation Center
   - Open any event/incident record
   - Click the **"Initiate Investigation"** button in the event detail modal
   - If an investigation already exists for the event, you will be redirected to it

2. **Direct Access**:
   - Navigate to `investigation_detail.html?id={investigation_id}` in your browser
   - Replace `{investigation_id}` with the actual investigation ID

### Investigation Detail Page Layout

The investigation detail page is organized into four main tabs:

1. **Details**: Basic investigation information and metadata
2. **Root Cause Analysis**: Manage RCA artefacts and perform analysis
3. **Actions (CAPA)**: View and create linked tasks/actions
4. **Closure**: Complete summaries and close the investigation

---

## Investigation Lifecycle

### Phase 1: Initiation

#### Creating an Investigation from an Event

1. Open the Event Center (`event_center.php`)
2. Click on any event card to view details
3. In the event detail modal, click **"Initiate Investigation"**
4. The system will:
   - Check if an investigation already exists for this event
   - If exists: Redirect you to the existing investigation
   - If not: Prompt you to create a new one

#### Investigation Creation Form

When creating a new investigation, you will be prompted for:

- **Investigation Type**: Select from:
  - Incident
  - Non-Conformance
  - Deviation
  - Complaint
  - Audit Finding

- **Investigation Lead**: Select the person responsible for leading the investigation

- **Trigger Reason** (Optional): Why was this investigation initiated?

- **Scope Description** (Optional): Describe the scope of the investigation

#### Investigation Details Tab

The Details tab allows you to:

- **View Linked Event**: See the event that triggered this investigation
- **Edit Investigation Type**: Change the type if needed
- **Assign/Change Lead**: Update the investigation lead
- **Update Trigger Reason**: Modify why the investigation was started
- **Edit Scope Description**: Update the investigation scope
- **Save Changes**: Click "Save Changes" to update the investigation

**Note**: The investigation status is automatically managed by the system (Open → In Progress → Closed).

---

## Root Cause Analysis (RCA)

### Overview

The RCA tab is where you perform structured root cause analysis using one or more RCA methods. The system supports two methods:

1. **5 Whys Analysis**: Sequential questioning to drill down to root causes
2. **Fishbone Diagram**: Categorical analysis across multiple dimensions

### Creating an RCA Artefact

1. Navigate to the **Root Cause Analysis** tab
2. Click **"Add New RCA"** button
3. Select the RCA method:
   - **5 Whys**: For sequential cause-and-effect analysis
   - **Fishbone**: For multi-dimensional categorical analysis
4. For Fishbone, select diagram type:
   - **Six P's**: Pre-configured categories (People, Process, Plant, Place, Product, Energy)
   - **Custom**: Create your own categories
5. Click **"Create RCA"**

### 5 Whys Analysis

#### Structure

The 5 Whys analysis consists of:

- **Problem Statement**: Initial description of the problem
- **Why Steps**: Sequential questions and answers
- **Root Cause Statement**: Final identified root cause

#### Using the 5 Whys Editor

1. **Enter Problem Statement**: 
   - Describe the problem clearly in the "Problem Statement" field
   - This should be a clear, concise statement of what went wrong

2. **Add Why Steps**:
   - Click **"Ask Why (Add Step)"** to add a new step
   - Each step represents one level of "Why?"
   - Enter the "Why" question (e.g., "Why did this happen?")
   - Enter the answer in the answer field
   - Continue adding steps until you reach the root cause

3. **Mark Key Causes**:
   - For each step that identifies a key cause, check the **"Is Key Cause"** checkbox
   - Key causes are those that directly contribute to the problem
   - Multiple steps can be marked as key causes

4. **Create Actions from Key Causes**:
   - For each key cause, a **"Create Action"** button will appear
   - Clicking this button opens the task creation modal
   - The task will be automatically linked to the RCA artefact
   - Complete the task details and save

5. **Enter Root Cause Statement**:
   - After completing all why steps, summarize the final root cause
   - This should be a clear statement of the underlying cause

6. **Mark as Completed**:
   - Click **"Mark as Completed"** when the 5 Whys analysis is finished
   - This updates the RCA status to "Completed"
   - Completed RCAs are required for investigation closure

#### Best Practices for 5 Whys

- Ask "Why?" at least 5 times, but continue until you reach a root cause
- Focus on process and system causes, not individual blame
- Each answer should lead logically to the next question
- Root causes should be actionable (something you can fix)

### Fishbone Diagram Analysis

#### Structure

The Fishbone diagram organizes causes into categories:

- **Categories**: Main "bones" of the fishbone (e.g., People, Process, Equipment)
- **Causes**: Specific causes within each category
- **Primary Causes**: Causes identified as primary contributors

#### Using the Fishbone Editor

1. **Category Setup**:
   - If using "Six P's", categories are automatically created
   - For custom diagrams, categories are created as you add causes

2. **Add Causes**:
   - Click **"Add Cause"** under any category
   - Enter the cause description
   - Optionally enter evidence reference (e.g., document ID, observation number)
   - Click OK to save

3. **Mark Primary Causes**:
   - For causes that are primary contributors, check **"Is Primary Cause"**
   - Primary causes are highlighted in yellow
   - Multiple causes can be marked as primary

4. **Create Actions from Primary Causes**:
   - For each primary cause, a **"Create Action"** button appears
   - Click to open the task creation modal
   - The task is automatically linked to the RCA artefact

5. **Complete the Analysis**:
   - Review all categories and causes
   - Ensure primary causes are identified
   - Mark the RCA as completed when finished

#### Fishbone Categories (Six P's)

- **People**: Human factors, training, competency
- **Process**: Procedures, workflows, methods
- **Plant**: Equipment, machinery, tools
- **Place**: Environment, location, conditions
- **Product**: Materials, inputs, outputs
- **Energy**: Power, resources, utilities

#### Best Practices for Fishbone

- Consider all categories, even if some have no causes
- Be specific in cause descriptions
- Link causes to evidence when available
- Focus on primary causes for action planning

### Managing RCA Artefacts

- **View All RCAs**: The RCA tab lists all RCA artefacts for the investigation
- **Edit RCA**: Click "Edit" on any RCA to open its editor
- **Status**: RCAs can be "In Progress" or "Completed"
- **Multiple RCAs**: You can create multiple RCA artefacts using different methods

---

## Actions and CAPA

### Overview

The Actions tab displays all tasks (CAPA) linked to the investigation. Tasks can be linked:

1. **Directly to Investigation**: General actions for the investigation
2. **From RCA Causes**: Actions created from key causes (5 Whys) or primary causes (Fishbone)

### Creating General Actions

1. Navigate to the **Actions (CAPA)** tab
2. Click **"Create General Action"**
3. The task creation modal/page opens
4. Complete the task details:
   - Task name
   - Description
   - Assignee
   - Due date
   - Priority
   - Status
5. Save the task
6. The task is automatically linked to the investigation

### Creating Actions from RCA Causes

#### From 5 Whys Key Causes

1. In the 5 Whys editor, mark a step as "Is Key Cause"
2. Click **"Create Action"** button that appears
3. Complete the task details
4. The task is automatically linked to the RCA artefact

#### From Fishbone Primary Causes

1. In the Fishbone editor, mark a cause as "Is Primary Cause"
2. Click **"Create Action"** button that appears
3. Complete the task details
4. The task is automatically linked to the RCA artefact

### Viewing Linked Tasks

The Actions tab displays:

- **Task Name**: Clickable link to task details
- **Status**: Current task status
- **Priority**: Task priority level
- **Due Date**: Task due date (if set)
- **Source**: Whether linked from Investigation or RCA

### Task Status Requirements

For investigation closure, **all linked tasks must be in one of these statuses**:
- Completed
- Archived
- Cancelled

Tasks in other statuses (Not Started, In Progress, On Hold) will prevent closure.

---

## Investigation Closure

### Closure Requirements

The system enforces a **three-part validation** before an investigation can be closed:

#### 1. Completed RCA Artefact

- **Requirement**: At least one RCA artefact must be marked as "Completed"
- **Check**: The system verifies that `rca_artefacts.status = 'Completed'` for at least one RCA
- **Action**: Complete at least one RCA analysis (5 Whys or Fishbone)

#### 2. All Tasks Closed

- **Requirement**: All tasks linked to the investigation or its RCAs must be closed
- **Check**: The system verifies that all linked tasks have status in ('Completed', 'Archived', 'Cancelled')
- **Action**: Complete, archive, or cancel all open tasks

#### 3. Summary Fields Completed

- **Requirement**: Both "Root Cause Summary" and "Lessons Learned" must be filled
- **Check**: The system verifies that both fields are NOT NULL and NOT EMPTY
- **Action**: Complete both text fields in the Closure tab

### Closure Process

1. **Navigate to Closure Tab**:
   - Click the **"Closure"** tab in the investigation detail page

2. **Review Validation Status**:
   - The page displays three validation checks
   - Green checkmarks indicate passed checks
   - Red X marks indicate failed checks
   - All checks must pass before closure

3. **Complete Summary Fields**:
   - **Root Cause Summary**: Provide a comprehensive summary of the root cause(s) identified
     - Should reference the RCA findings
     - Should be clear and actionable
     - This field is mandatory
   
   - **Lessons Learned**: Document key lessons and recommendations
     - What can be learned from this investigation?
     - How can similar issues be prevented?
     - What improvements are recommended?
     - This field is mandatory

4. **Save Summary & Lessons Learned**:
   - Click **"Save Summary & Lessons Learned"** to save your entries
   - The validation status will update

5. **Close Investigation**:
   - Once all three checks pass, the **"Close Investigation"** button becomes enabled
   - Click the button
   - Confirm the closure action
   - The investigation status changes to "Closed"
   - The `closed_at` timestamp is recorded

### Closure Validation Display

The Closure tab shows real-time validation status:

```
Closure Requirements
✓ At least one completed RCA artefact
✓ All linked tasks are closed
✓ Root cause summary and lessons learned completed
```

- **Green checkmark (✓)**: Requirement met
- **Red X (✗)**: Requirement not met

### After Closure

Once an investigation is closed:

- Status cannot be changed back to "Open" or "In Progress"
- The investigation is considered complete
- All linked tasks and RCAs remain accessible for reference
- The investigation can be used for reporting and analysis

---

## Integration with Event Records

### Event-to-Investigation Link

#### Creating Investigation from Event

1. **Access Event Center**:
   - Navigate to `event_center.php`
   - View events in Kanban, Calendar, or List view

2. **Open Event Details**:
   - Click on any event card
   - The event detail modal opens

3. **Initiate Investigation**:
   - Click the **"Initiate Investigation"** button in the modal footer
   - The system checks for existing investigations
   - If exists: Redirects to existing investigation
   - If not: Prompts for investigation creation

4. **Investigation Created**:
   - New investigation is linked to the event via `event_id`
   - Investigation detail page opens automatically
   - Event link is displayed in investigation header

#### Viewing Linked Investigation from Event

- The investigation detail page shows the linked event
- Click "View Event" to return to the event record
- The event description and type are displayed in the investigation

### Event Status Updates

When an investigation is initiated from an event:

- The event status can be updated to "Under Investigation"
- This is done manually in the event edit modal
- The investigation and event remain linked regardless of status

### Multiple Investigations per Event

- **Note**: The current implementation allows one investigation per event
- If you need multiple investigations, consider creating separate events or using a different approach

---

## Troubleshooting

### Common Issues

#### 1. "Initiate Investigation" Button Not Visible

**Problem**: The button doesn't appear in the event modal.

**Solution**: 
- Ensure you're viewing the event detail modal (not edit modal)
- Check that JavaScript is enabled
- Refresh the page and try again

#### 2. Cannot Create RCA

**Problem**: "Add New RCA" button doesn't work or shows error.

**Solution**:
- Ensure investigation is loaded (check investigation ID in URL)
- Check browser console for JavaScript errors
- Verify API endpoints are accessible
- Ensure you have proper permissions

#### 3. Closure Button Disabled

**Problem**: "Close Investigation" button remains disabled.

**Solution**:
- Check the Closure tab validation status
- Ensure at least one RCA is marked as "Completed"
- Verify all linked tasks are closed (Completed, Archived, or Cancelled)
- Complete both "Root Cause Summary" and "Lessons Learned" fields
- Click "Save Summary & Lessons Learned" after filling fields

#### 4. Tasks Not Linking

**Problem**: Tasks created from RCA causes aren't appearing in Actions tab.

**Solution**:
- Verify the task was created successfully
- Check that `entity_task_links` table includes 'RCA' as a valid sourcetype
- Refresh the Actions tab
- Check browser console for API errors

#### 5. 5 Whys Steps Not Saving

**Problem**: Changes to why steps aren't persisting.

**Solution**:
- Ensure you click outside the input field (blur event triggers save)
- Check browser console for API errors
- Verify the step_id is correct
- Try refreshing and re-entering data

#### 6. Fishbone Categories Not Appearing

**Problem**: Six P's categories don't show up in Fishbone editor.

**Solution**:
- Categories are created automatically on first load
- If not appearing, check API response in browser console
- Try refreshing the Fishbone editor
- Verify `rca_fishbone_categories` table exists

### API Endpoint Reference

#### Investigation Endpoints

- `GET /api/investigations/index.php` - List investigations
- `GET /api/investigations/index.php/{id}` - Get investigation details
- `POST /api/investigations/index.php` - Create investigation
- `PUT /api/investigations/index.php/{id}` - Update investigation
- `PUT /api/investigations/index.php/{id}?action=close` - Close investigation
- `GET /api/investigations/index.php/{id}/validation_status` - Check closure validation

#### RCA Endpoints

- `POST /api/investigations/index.php/{id}/rca/create` - Create RCA artefact
- `PUT /api/investigations/index.php/{id}/rca/complete/{rca_id}` - Mark RCA as completed
- `GET /api/investigations/index.php/{id}/rca/5whys/{rca_id}` - Get 5 Whys data
- `PUT /api/investigations/index.php/{id}/rca/5whys/{rca_id}` - Update 5 Whys
- `GET /api/investigations/index.php/{id}/rca/fishbone/{rca_id}` - Get Fishbone data

#### Step/Cause Endpoints

- `POST /api/rca/5whys_steps.php` - Create 5 Whys step
- `PUT /api/rca/5whys_steps.php` - Update 5 Whys step
- `POST /api/rca/fishbone_causes.php` - Create Fishbone cause
- `PUT /api/rca/fishbone_causes.php` - Update Fishbone cause
- `POST /api/rca/fishbone_categories.php` - Create Fishbone category

### Browser Compatibility

- **Recommended**: Chrome, Firefox, Edge (latest versions)
- **Minimum**: Chrome 90+, Firefox 88+, Edge 90+
- **Not Supported**: Internet Explorer

### Permissions

- Investigation creation: Requires event view access
- RCA management: Requires investigation edit access
- Closure: Requires investigation close permission
- Task creation: Requires task creation permission

---

## Best Practices

### Investigation Management

1. **Start Early**: Initiate investigations as soon as events occur
2. **Assign Appropriate Leads**: Choose leads with relevant expertise
3. **Document Thoroughly**: Complete all fields, especially scope and trigger reason
4. **Use Multiple RCAs**: Consider using both 5 Whys and Fishbone for complex issues
5. **Link Actions Promptly**: Create actions as soon as causes are identified

### RCA Analysis

1. **Be Systematic**: Follow the method structure (don't skip steps)
2. **Be Objective**: Focus on facts and processes, not blame
3. **Be Thorough**: Continue analysis until root causes are identified
4. **Document Evidence**: Link causes to evidence when available
5. **Review Before Completion**: Ensure analysis is complete before marking as done

### Action Management

1. **Create Actions Early**: Don't wait until closure to create actions
2. **Set Realistic Due Dates**: Ensure tasks can be completed
3. **Assign Appropriately**: Assign tasks to people with the right skills
4. **Track Progress**: Monitor task completion regularly
5. **Close Promptly**: Complete tasks as soon as they're done

### Closure Process

1. **Complete Summaries First**: Write summaries while analysis is fresh
2. **Review All RCAs**: Ensure all RCA findings are reflected in summaries
3. **Verify Tasks**: Double-check that all tasks are truly closed
4. **Get Approval**: Consider getting management approval before closure
5. **Archive After Closure**: Move closed investigations to archive for reporting

---

## Additional Resources

### Related Documentation

- `ENTITY_TASK_LINKING_IMPLEMENTATION.md` - Task linking system documentation
- `BACKEND_INTEGRATION_COMPLETE.md` - Backend architecture details
- Database schema documentation (DBStructureExport.json)

### Support

For technical issues or questions:
1. Check this guide first
2. Review browser console for errors
3. Check API responses in Network tab
4. Contact system administrator

---

## Version History

- **v1.0** (Current): Initial release with 5 Whys and Fishbone support, enforced closure logic, event integration

---

**Last Updated**: 2024
**Document Version**: 1.0

