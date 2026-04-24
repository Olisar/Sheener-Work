# How to Run Migration in phpMyAdmin

## Step-by-Step Instructions

### Step 1: Open phpMyAdmin
1. Go to `http://localhost/phpmyadmin` in your browser
2. Login with your MySQL credentials (usually `root` with no password for XAMPP)

### Step 2: Select Database
1. Click on **`sheener`** database in the left sidebar
2. Make sure it's highlighted/selected

### Step 3: Open SQL Tab
1. Click on the **"SQL"** tab at the top of the page
2. You'll see a large text area where you can paste SQL commands

### Step 4: Copy SQL Content
**IMPORTANT:** You need to copy the **CONTENTS** of the SQL file, NOT the file path!

1. Open the file: `sql/migrations/001_add_process_map_linkages_PHPMYADMIN.sql`
2. **Select ALL** the content (Ctrl+A)
3. **Copy** it (Ctrl+C)

**OR** use the file I created specifically for phpMyAdmin:
- File: `001_add_process_map_linkages_PHPMYADMIN.sql`
- This file has all the SQL statements ready to paste

### Step 5: Paste and Execute
1. **Paste** the SQL content into the SQL text area in phpMyAdmin (Ctrl+V)
2. Click the **"Go"** button at the bottom
3. Wait for execution to complete

### Step 6: Verify Success
You should see:
- ✅ "6 rows affected" or similar success messages
- ✅ No error messages
- ✅ Tables created successfully

### Step 7: Verify Tables Created
Run this query in the SQL tab:
```sql
SHOW TABLES LIKE 'process_map_%';
```

You should see all 6 new tables:
- process_map_event
- process_map_operational_event
- process_map_permit
- process_map_ofi
- process_map_task
- process_map_hira

---

## Common Mistakes to Avoid

### ❌ DON'T DO THIS:
```
sql/migrations/001_add_process_map_linkages.sql
```
This is a **file path**, not SQL code!

### ✅ DO THIS INSTEAD:
Copy the actual SQL statements from inside the file:
```sql
CREATE TABLE IF NOT EXISTS `process_map_event` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  ...
```

---

## Alternative: Import File Directly

If you prefer, you can import the file directly:

1. In phpMyAdmin, select `sheener` database
2. Click **"Import"** tab
3. Click **"Choose File"** button
4. Select `sql/migrations/001_add_process_map_linkages.sql`
5. Click **"Go"**

---

## Troubleshooting

### Error: "Table already exists"
- This is OK! The script uses `CREATE TABLE IF NOT EXISTS`
- The tables may have been created already
- Check with: `SHOW TABLES LIKE 'process_map_%';`

### Error: "Foreign key constraint fails"
- Verify that the referenced tables exist:
  - `process_map` table exists
  - `events` table exists
  - `tasks` table exists
  - etc.

### Error: "Access denied"
- Make sure you're logged in as a user with CREATE TABLE permissions
- Try logging in as `root` user

---

## Next Steps After Migration

1. **Run Test Script:**
   - Copy contents of `test_migration.sql`
   - Paste into phpMyAdmin SQL tab
   - Click "Go"

2. **Test API:**
   - Test link endpoint
   - Test get links endpoint
   - Test detail endpoint

3. **Test Frontend:**
   - Open process map diagram
   - Verify new sections appear in sidebar

---

## Quick Reference

**File to use:** `001_add_process_map_linkages_PHPMYADMIN.sql`  
**What to copy:** ALL the SQL statements (from START TRANSACTION to COMMIT)  
**Where to paste:** phpMyAdmin → SQL tab → text area  
**What to click:** "Go" button

