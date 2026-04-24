# Process Branches Implementation Guide

## Overview

This implementation adds support for **multi-process flow branches** to the process map system. Branches allow you to group related processes together, creating focused views of specific manufacturing flows (e.g., "MDI Manufacturing", "DPI Manufacturing", etc.).

## Database Schema

### New Tables

#### 1. `process_branches`
Stores branch definitions:
- `id` - Primary key
- `name` - Branch name (e.g., "MDI Manufacturing")
- `description` - Branch description
- `color` - Hex color for visualization
- `icon` - FontAwesome icon class
- `is_active` - Active status
- `created_at`, `updated_at` - Timestamps
- `created_by` - User who created the branch

#### 2. `process_branch_items`
Junction table linking processes to branches:
- `id` - Primary key
- `branch_id` - Foreign key to `process_branches`
- `process_map_id` - Foreign key to `process_map`
- `order` - Order within branch
- `is_root` - Whether this is a root node in the branch
- `added_at`, `added_by` - Audit fields

#### 3. Enhanced `process_map`
Added field:
- `primary_branch_id` - Optional field to mark primary branch association

## Installation

### Step 1: Run Migration
```sql
-- Run the migration script
SOURCE sql/migrations/003_add_process_branches.sql;
```

### Step 2: Create MDI Manufacturing Branch
```sql
-- Run the branch creation script
SOURCE sql/create_mdi_manufacturing_branch.sql;
```

## Usage

### Creating a New Branch

1. **Insert into `process_branches`:**
```sql
INSERT INTO `process_branches` (`name`, `description`, `color`, `icon`, `is_active`)
VALUES (
    'Your Branch Name',
    'Branch description',
    '#3498db',
    'fas fa-icon-name',
    1
);
SET @branch_id = LAST_INSERT_ID();
```

2. **Link processes to branch:**
```sql
INSERT INTO `process_branch_items` (`branch_id`, `process_map_id`, `order`, `is_root`)
VALUES 
    (@branch_id, process_id_1, 1, 1),
    (@branch_id, process_id_2, 2, 0),
    -- ... more processes
```

### Filtering by Branch in JavaScript

The process map now supports branch filtering:

```javascript
// Select a branch
processMap.selectedBranchId = branchId;
await processMap.loadProcesses();
processMap.renderDiagram();
```

### API Endpoints

#### List All Branches
```
GET php/api_process_map.php?action=list_branches
```

#### Get Branch Details
```
GET php/api_process_map.php?action=get_branch&branch_id=1
```

#### Get Processes in Branch
```
GET php/api_process_map.php?action=get_branch_processes&branch_id=1
```

#### Filter Process List by Branch
```
GET php/api_process_map.php?action=list&branch_id=1
```

## MDI Manufacturing Branch

The MDI Manufacturing branch includes:

1. **Raw Material Management** (Process ID: 8)
   - Incoming material quality control
   - Storage of raw materials (API, Excipients, Propellants, Packaging)

2. **API Processing** (Process ID: 17)
   - API micronization
   - Crystallinity and surface morphology testing

3. **Formulation Preparation** (Process ID: 22)
   - For MDIs: Preparation of drug suspension/solution, Mixing with propellant

4. **Device Components Manufacturing** (Process ID: 29)
   - Injection molding of plastic components
   - Metal components fabrication
   - Assembly of device parts

5. **Filling and Assembly** (Process ID: 33)
   - For MDIs: Canister filling, Valve crimping, Actuator attachment

## UI Integration

### Process Map Page
- Branch selector dropdown in the toolbar
- Filter processes by selected branch
- Visual indicators for branch membership

### Process Detail Page
- Display branch badges showing which branches a process belongs to
- Color-coded branch indicators

## Best Practices

1. **Root Nodes**: Mark root processes in a branch with `is_root = 1`
2. **Ordering**: Use the `order` field to control sequence within branches
3. **Primary Branch**: Set `primary_branch_id` for processes that primarily belong to one branch
4. **Multiple Branches**: Processes can belong to multiple branches via the junction table

## Verification

To verify the MDI branch was created correctly:

```sql
SELECT 
    b.name AS branch_name,
    pm.id,
    pm.type,
    pm.text,
    pbi.order AS branch_order,
    pbi.is_root
FROM process_branch_items pbi
JOIN process_branches b ON pbi.branch_id = b.id
JOIN process_map pm ON pbi.process_map_id = pm.id
WHERE b.name = 'MDI Manufacturing'
ORDER BY pbi.order, pm.id;
```

## Future Enhancements

- Branch templates for common manufacturing flows
- Branch cloning functionality
- Branch comparison views
- Branch-specific reporting
- Branch permissions and access control

