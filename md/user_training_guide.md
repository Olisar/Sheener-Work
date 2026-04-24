# User Training Guide: Process-Entity Linkages

## Overview

This guide teaches users how to link Events, Tasks, Permits, Opportunities for Improvement (OFI), and Risk Assessments to process nodes in the Process Map Diagram.

**Target Audience:** Process managers, quality assurance staff, safety officers  
**Duration:** 15-20 minutes  
**Prerequisites:** Basic familiarity with the Process Map Diagram interface

---

## Table of Contents

1. [Understanding Process-Entity Linkages](#understanding-linkages)
2. [Viewing Linked Entities](#viewing-linked-entities)
3. [Linking Entities to Processes](#linking-entities)
4. [Unlinking Entities](#unlinking-entities)
5. [Best Practices](#best-practices)
6. [Troubleshooting](#troubleshooting)

---

## Understanding Process-Entity Linkages

### What Can Be Linked?

You can link the following entities to any process node (process, step, substep, task, or activity):

- **Tasks** - Work items and assignments
- **Events** - Operational events and incidents
- **Permits** - Work permits and authorizations
- **OFIs** - Opportunities for Improvement
- **Risk Assessments** - Individual risks and HIRA assessments
- **Documents** - Process documentation
- **7Ps Elements** - People, Equipment, Materials, etc.

### Why Link Entities?

- **Traceability:** See which processes are affected by incidents
- **Compliance:** Track permits required for each process step
- **Improvement:** Link OFIs to specific processes for targeted improvements
- **Risk Management:** Associate risk assessments with process steps
- **Task Management:** Track tasks related to process execution

---

## Viewing Linked Entities

### Step 1: Open Process Map Diagram

1. Navigate to **Process Map Diagram** from the main menu
2. The diagram displays your process hierarchy

### Step 2: Select a Process Node

1. Click on any process node (process, step, substep, task, or activity)
2. The **sidebar** on the right opens showing node details

### Step 3: View Linked Entities

The sidebar displays several sections:

#### Tasks Section
- Shows count of linked tasks
- Displays up to 5 recent tasks with status and priority
- Click "View All Tasks" to see complete list

#### Events & Incidents Section
- Shows counts for Events and Operational Events
- Displays recent events with type and status
- Use "Link Event" buttons to add new links

#### Permits Section
- Shows count of linked permits
- Displays permit type, status, and expiry date
- Click "Link Permit" to associate permits

#### Opportunities for Improvement Section
- Shows OFI count
- Displays recommended improvements and implementation status
- Link OFIs to track process improvements

#### Risk Assessments Section
- Shows counts for Risks and HIRA assessments
- Displays assessment details and status
- Link assessments for risk tracking

---

## Linking Entities to Processes

### Method 1: From Sidebar (Recommended)

1. **Select a process node** in the diagram
2. **Open the sidebar** (automatically opens when node is selected)
3. **Navigate to the relevant section** (Tasks, Events, Permits, etc.)
4. **Click "Link [Entity Type]" button**
   - Example: Click "Link Task" in the Tasks section
5. **Select entities** in the modal:
   - Check boxes next to entities you want to link
   - Add optional notes about the link
6. **Click "Link Selected"**
7. **Verify** the entities appear in the sidebar

### Method 2: From Context Menu

1. **Right-click** on a process node
2. **Select "Link 7Ps Elements"** or use bulk assignment
3. Follow the modal prompts

### Example: Linking a Task

**Scenario:** Link "Safety Inspection Task" to "Quality Control Step"

1. Click on "Quality Control Step" in the diagram
2. In the sidebar, scroll to "Tasks" section
3. Click "Link Task" button
4. In the modal, find and check "Safety Inspection Task"
5. Add note: "Required before process completion"
6. Click "Link Selected"
7. Task now appears in the Tasks section

---

## Unlinking Entities

### From Sidebar

1. **Select the process node**
2. **View linked entities** in the sidebar
3. **Click the unlink icon** (🔗 with slash) next to the entity
4. **Confirm** the unlink action

### From Full Detail Page

1. Click "View Full Details" button in sidebar
2. Navigate to the relevant tab (Tasks, Events, etc.)
3. Find the entity you want to unlink
4. Click the unlink button
5. Confirm the action

---

## Best Practices

### 1. Link at the Right Level

- **Process Level:** Link high-level items (major risks, key documents)
- **Step Level:** Link operational items (tasks, permits, events)
- **Substep/Task Level:** Link specific work items and detailed risks

### 2. Use Notes Effectively

When linking entities, add notes to explain:
- **Why** the link exists
- **When** it's relevant
- **Who** is responsible
- **What** action is required

**Example Notes:**
- "Required for compliance audit"
- "Linked after incident investigation"
- "Critical path dependency"

### 3. Keep Links Current

- **Review regularly:** Check if links are still relevant
- **Remove obsolete links:** Unlink entities that are no longer applicable
- **Update after changes:** Re-link if process structure changes

### 4. Link Related Items Together

- Link **events** that occurred during a process step
- Link **permits** required before starting a step
- Link **OFIs** identified during process execution
- Link **tasks** that must be completed for the step

### 5. Use for Process Improvement

- Link **OFIs** to identify improvement opportunities
- Link **events** to track process issues
- Link **risk assessments** to manage process risks
- Review linked entities to identify patterns

---

## Common Use Cases

### Use Case 1: Incident Investigation

**Goal:** Link an incident event to the process step where it occurred

1. Navigate to the process step
2. Click "Link Operational Event"
3. Select the incident from the list
4. Add note: "Incident occurred during this step - investigation ongoing"
5. Link the event

**Benefit:** Process managers can see which steps have incidents

### Use Case 2: Permit Management

**Goal:** Track which permits are required for each process step

1. Select the process step
2. Click "Link Permit"
3. Select all relevant permits
4. Add note: "Required before step execution"
5. Link permits

**Benefit:** Clear visibility of permit requirements per step

### Use Case 3: Task Assignment

**Goal:** Link tasks to process steps for execution tracking

1. Select the process step
2. Click "Link Task"
3. Select tasks assigned to this step
4. Link tasks

**Benefit:** See all tasks related to a process step in one place

### Use Case 4: Risk Management

**Goal:** Link risk assessments to process steps

1. Select the process step
2. Click "Link HIRA" or "Link Risk"
3. Select the relevant assessment
4. Add note: "High-risk step - requires additional controls"
5. Link assessment

**Benefit:** Risk visibility at process level

---

## Troubleshooting

### Problem: "Link" button doesn't work

**Solution:**
- Check if you have permission to link entities
- Refresh the page
- Check browser console for errors

### Problem: Entity doesn't appear in selection list

**Solution:**
- Verify the entity exists in the system
- Check if entity is already linked (duplicates not allowed)
- Ensure you have access to view the entity

### Problem: Link created but doesn't show in sidebar

**Solution:**
- Refresh the sidebar by clicking the node again
- Check if the entity type section is expanded
- Verify the link was created successfully

### Problem: Can't unlink an entity

**Solution:**
- Verify you have permission to unlink
- Check if the entity still exists
- Try from the full detail page instead

---

## Quick Reference

### Keyboard Shortcuts
- **Click node:** Select and view details
- **Right-click node:** Context menu
- **Escape:** Close modals

### Link Types Available
- Tasks (`task`)
- Events (`event`)
- Operational Events (`operational_event`)
- Permits (`permit`)
- OFIs (`ofi`)
- Risks (`risk`)
- HIRA Assessments (`hira`)
- Documents (`document`)

### Status Indicators
- **Green badge:** Active/Open
- **Yellow badge:** In Progress/Pending
- **Red badge:** Closed/Completed
- **Gray badge:** Inactive

---

## Getting Help

If you need assistance:

1. **Check this guide** for common issues
2. **Contact IT Support** for technical problems
3. **Contact Process Manager** for process-specific questions
4. **Review API documentation** for advanced usage

---

## Training Checklist

After completing this training, you should be able to:

- [ ] Understand what entities can be linked to processes
- [ ] View linked entities in the sidebar
- [ ] Link tasks to process nodes
- [ ] Link events to process nodes
- [ ] Link permits to process nodes
- [ ] Link OFIs to process nodes
- [ ] Link risk assessments to process nodes
- [ ] Unlink entities when needed
- [ ] Add meaningful notes when linking
- [ ] Use links for process improvement

---

**Document Version:** 1.0  
**Last Updated:** 2024-12-19  
**For Questions:** Contact your system administrator

