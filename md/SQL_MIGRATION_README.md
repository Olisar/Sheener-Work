# Process Map Linkages - Migration Scripts

## Overview

This directory contains SQL migration scripts for adding process-entity linkage support to the process_map structure.

## Files

- **`001_add_process_map_linkages.sql`** - Main migration script (creates 6 new junction tables)
- **`test_migration.sql`** - Test script to verify migration
- **`run_migration.bat`** - Windows batch script to run migration
- **`run_test_migration.bat`** - Windows batch script to run tests

## Quick Start

### Option 1: Using Batch Scripts (Windows)

1. **Create Database Backup First!**
   ```bash
   mysqldump -u root -p sheener > backup_before_migration.sql
   ```

2. **Run Migration:**
   ```bash
   sql\migrations\run_migration.bat
   ```

3. **Run Tests:**
   ```bash
   sql\migrations\run_test_migration.bat
   ```

### Option 2: Using MySQL Command Line

1. **Create Database Backup:**
   ```bash
   mysqldump -u root -p sheener > backup_before_migration.sql
   ```

2. **Run Migration:**
   ```bash
   mysql -u root -p sheener < sql/migrations/001_add_process_map_linkages.sql
   ```

3. **Run Tests:**
   ```bash
   mysql -u root -p sheener < sql/migrations/test_migration.sql
   ```

### Option 3: Using phpMyAdmin

1. **Create Backup:**
   - Open phpMyAdmin
   - Select `sheener` database
   - Click "Export" → "Quick" → "Go"

2. **Run Migration:**
   - Click "SQL" tab
   - Copy contents of `001_add_process_map_linkages.sql`
   - Paste and click "Go"

3. **Run Tests:**
   - Click "SQL" tab
   - Copy contents of `test_migration.sql`
   - Paste and click "Go"

## What Gets Created

The migration creates 6 new junction tables:

1. `process_map_event` - Links events to process_map
2. `process_map_operational_event` - Links operational_events to process_map
3. `process_map_permit` - Links permits to process_map
4. `process_map_ofi` - Links OFI details to process_map
5. `process_map_task` - Links tasks to process_map
6. `process_map_hira` - Links HIRA assessments to process_map

## Verification

After running the migration, verify tables were created:

```sql
SHOW TABLES LIKE 'process_map_%';
```

You should see all 6 new tables plus existing ones.

## Rollback

If you need to rollback the migration:

```sql
DROP TABLE IF EXISTS `process_map_hira`;
DROP TABLE IF EXISTS `process_map_task`;
DROP TABLE IF EXISTS `process_map_ofi`;
DROP TABLE IF EXISTS `process_map_permit`;
DROP TABLE IF EXISTS `process_map_operational_event`;
DROP TABLE IF EXISTS `process_map_event`;
```

**Note:** This will delete all links. Restore from backup if you need to preserve data.

## Troubleshooting

### Error: "Table already exists"
- The migration uses `CREATE TABLE IF NOT EXISTS`, so this shouldn't happen
- If it does, the tables may have been created manually
- Check if tables exist: `SHOW TABLES LIKE 'process_map_%';`

### Error: "Foreign key constraint fails"
- Verify referenced tables exist (events, tasks, permits, etc.)
- Check that referenced tables have data if you're testing with real IDs

### Error: "Access denied"
- Verify MySQL user has CREATE TABLE permissions
- Try running with root user or appropriate admin user

## Support

For issues or questions:
1. Check `docs/migration_testing_guide.md` for detailed instructions
2. Review error messages carefully
3. Check MySQL error log
4. Contact database administrator

## Next Steps

After successful migration:
1. Test API endpoints (see `docs/process_map_linkages_quick_reference.md`)
2. Test frontend integration
3. Train users (see `docs/user_training_guide.md`)
4. Monitor system performance

