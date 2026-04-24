# Process Map Database Enhancement - SQL Scripts

## Overview

The current `process_map` table and related junction tables in your database **cannot fully handle** the process information structure used in `SchemScript.js` (lines 72-121). This document explains what's missing and provides SQL scripts to add the required fields.

## Current Database Structure vs Required Structure

### ✅ What EXISTS in Database:

1. **`process_map` table** has:
   - `id`, `type`, `text`, `description`, `parent`, `status`, `owner_id`, etc.
   - ✅ Basic process hierarchy support

2. **Element Junction Tables** exist:
   - `process_map_people` - links people to processes
   - `process_map_equipment` - links equipment to processes
   - `process_map_material` - links materials to processes (with quantity/unit)
   - `process_map_energy` - links energy to processes
   - `process_map_area` - links areas to processes

### ❌ What's MISSING:

#### 1. **`process_map` table** missing fields:
   - ❌ `level` - Process hierarchy level (L0_Enterprise, L1_HighLevel, L2_SubProcess, L3_DetailStep)
   - ❌ `cost` - Estimated cost (DECIMAL)
   - ❌ `value_add` - Boolean flag (1 = value-adding, 0 = non-value-adding)

#### 2. **Element Junction Tables** missing fields:
   - ❌ `usage` - Usage description (e.g., "2 hours", "30 min", "5 kg", "10 kWh")
   - ❌ `fixed` - Boolean flag (1 = fixed resource, 0 = movable resource)

#### 3. **Transformation Data** - completely missing:
   - ❌ No table to store input/output transformation data
   - Example: Input: "Raw Material X (5kg)" → Output: "Component Y (4.8kg)"

## SQL Scripts Provided

### Option 1: Simple Script (Recommended for first-time setup)
**File:** `sql/add_process_map_fields_simple.sql`

- Straightforward ALTER TABLE statements
- Run this if columns don't exist yet
- If columns already exist, you'll get errors (that's okay, just skip those statements)

### Option 2: Individual Scripts (For selective updates)
- `sql/add_process_map_fields.sql` - Adds level, cost, value_add to process_map
- `sql/add_process_elements_fields.sql` - Adds usage and fixed to all junction tables
- `sql/create_process_transformation_table.sql` - Creates the transformation table

### Option 3: Complete Script with Safety Checks
**File:** `sql/add_process_map_fields_complete.sql`

- Includes checks to prevent errors if columns already exist
- More complex but safer for repeated runs

## How to Use

### Step 1: Backup Your Database
```sql
-- Always backup before making schema changes!
mysqldump -u username -p database_name > backup_before_process_fields.sql
```

### Step 2: Run the SQL Script
```bash
# Option A: Simple script (recommended)
mysql -u username -p database_name < sql/add_process_map_fields_simple.sql

# Option B: Individual scripts
mysql -u username -p database_name < sql/add_process_map_fields.sql
mysql -u username -p database_name < sql/add_process_elements_fields.sql
mysql -u username -p database_name < sql/create_process_transformation_table.sql
```

### Step 3: Verify Changes
```sql
-- Check process_map table
DESCRIBE process_map;

-- Check junction tables
DESCRIBE process_map_people;
DESCRIBE process_map_equipment;
DESCRIBE process_map_material;
DESCRIBE process_map_energy;
DESCRIBE process_map_area;

-- Check transformation table
SHOW CREATE TABLE process_transformation;
```

## Data Mapping

### From JavaScript to Database:

| JavaScript Field | Database Table.Column | Notes |
|-----------------|----------------------|-------|
| `id` | `process_map.id` | ✅ Already exists |
| `name` | `process_map.text` | ✅ Already exists (mapped as 'text') |
| `level` | `process_map.level` | ❌ **NEW FIELD** |
| `parentId` | `process_map.parent` | ✅ Already exists |
| `description` | `process_map.description` | ✅ Already exists |
| `cost` | `process_map.cost` | ❌ **NEW FIELD** |
| `value_add` | `process_map.value_add` | ❌ **NEW FIELD** |
| `elements[].type` | Junction table name | ✅ Already exists (People, Equipment, Material, Energy, Area) |
| `elements[].name` | Referenced table | ✅ Already exists |
| `elements[].usage` | Junction table.usage | ❌ **NEW FIELD** |
| `elements[].fixed` | Junction table.fixed | ❌ **NEW FIELD** |
| `transformation.input[]` | `process_transformation` (type='input') | ❌ **NEW TABLE** |
| `transformation.output[]` | `process_transformation` (type='output') | ❌ **NEW TABLE** |

## Example Data Insertion

After running the scripts, you can insert data like this:

```sql
-- Insert process with new fields
INSERT INTO process_map (text, level, description, cost, value_add, parent, type)
VALUES ('Customer Order Intake', 'L2_SubProcess', 'Process customer orders', 120.00, 1, 10, 'step');

-- Get the inserted ID
SET @process_id = LAST_INSERT_ID();

-- Link people with usage and fixed flag
INSERT INTO process_map_people (process_map_id, people_id, usage, fixed)
VALUES (@process_id, 123, '2 hours', 0);

-- Link equipment with usage and fixed flag
INSERT INTO process_map_equipment (process_map_id, equipment_id, usage, fixed)
VALUES (@process_id, 456, '30 min', 1);

-- Add transformation data
INSERT INTO process_transformation (process_map_id, type, material_name, quantity, unit, `order`)
VALUES 
  (@process_id, 'input', 'Raw Material X', '5', 'kg', 1),
  (@process_id, 'output', 'Component Y', '4.8', 'kg', 1);
```

## Next Steps

After applying these SQL scripts:

1. ✅ Update your PHP/backend code to read/write these new fields
2. ✅ Update `SchemScript.js` to load process data from database instead of hardcoded object
3. ✅ Create API endpoints or PHP scripts to export process data in the format expected by the JavaScript
4. ✅ Update your admin interface to allow editing of these new fields

## Questions?

If you encounter any issues:
- Check MySQL error messages
- Verify table names match your database
- Ensure foreign key constraints are satisfied
- Review the verification queries in the scripts

