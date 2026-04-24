# Quick Start: Running Migrations 002-005

## Step 1: Open phpMyAdmin
1. Go to: `http://localhost/phpmyadmin`
2. Select database: **sheener**
3. Click **SQL** tab

## Step 2: Run Each Migration

### Migration 002: Enhance process_map Table
**File:** `002_enhance_process_map_table_SAFE.sql` (use this one - handles missing departments)

1. Open the file in a text editor
2. **Copy ALL content** (Ctrl+A, Ctrl+C)
3. Paste into phpMyAdmin SQL tab
4. Click **Go**
5. ✅ Should see: "MySQL returned an empty result set" (this is NORMAL)

### Migration 003: Add process_map_activity
**File:** `003_add_process_map_activity.sql`

1. Open the file
2. **Copy ALL content**
3. Paste into phpMyAdmin SQL tab
4. Click **Go**
5. ✅ Should see: "MySQL returned an empty result set"

### Migration 004: Create process_map_audit
**File:** `004_create_process_map_audit.sql`

1. Open the file
2. **Copy ALL content**
3. Paste into phpMyAdmin SQL tab
4. Click **Go**
5. ✅ Should see: "MySQL returned an empty result set"

### Migration 005: Add process_map_approval
**File:** `005_add_process_map_approval.sql`

1. Open the file
2. **Copy ALL content**
3. Paste into phpMyAdmin SQL tab
4. Click **Go**
5. ✅ Should see: "MySQL returned an empty result set"

## Step 3: Verify Everything Worked

Run this in phpMyAdmin SQL tab:

```sql
-- Check all new tables exist
SHOW TABLES LIKE 'process_map_%';

-- Check process_map has new fields
DESCRIBE process_map;

-- Count new fields (should be many)
SELECT COUNT(*) as field_count 
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
AND table_name = 'process_map';
```

**Expected Results:**
- Should see: `process_map_activity`, `process_map_audit`, `process_map_approval`
- `process_map` should show fields: status, owner_id, department_id, order, description, notes, created_at, updated_at, created_by, updated_by

## Troubleshooting

**Error: "Unknown column 'status'"**
- Field already exists? Check with: `DESCRIBE process_map;`
- If field exists, you can skip that ALTER statement

**Error: "Table 'departments' doesn't exist"**
- Use `002_enhance_process_map_table_SAFE.sql` instead
- This version handles missing departments table gracefully

**Error: "Table already exists"**
- Table was already created - that's OK, skip that migration

**Error: "Duplicate column name"**
- Column already exists - that's OK, the migration is idempotent

---

**Ready?** Start with Migration 002!
