# Schema Consolidation Migration Guide

## Overview

This migration consolidates user/login data and unifies risk/hazard registers to simplify the database schema and eliminate redundancy.

## Changes Summary

### Part 1: User/Login Consolidation

1. **Drops `users` table** - Eliminates duplicate user data
2. **Renames `personalinformation` to `user_accounts`** - Better naming clarity
3. **Redirects foreign keys** - All user references now point to `people` table:
   - `permit_responsibles.person_id` → `people.people_id`
   - `permit_energies.isolation_verified_by` → `people.people_id`

### Part 2: Risk/Hazard Unification

1. **Redirects `controls` table** - Now references `risk_register` instead of `risks`
2. **Redirects `process_map_risk` table** - Now references `risk_register` instead of `risks`
3. **Drops redundant tables**:
   - `risks` table (superseded by `risk_register`)
   - `hazards` table (hazard data managed through `risk_register` and `hira_hazard_links`)

## Files

- **`schema_consolidation_migration.sql`** - Main migration script (with conditional FK dropping)
- **`schema_consolidation_migration_simple.sql`** - Simplified version for older MySQL
- **`schema_consolidation_migration_idempotent.sql`** - **RECOMMENDED** - Idempotent version (safe to run multiple times)
- **`schema_consolidation_rollback.sql`** - Rollback script (use if needed)
- **`schema_consolidation_verify.sql`** - Verification queries

## Prerequisites

⚠️ **IMPORTANT**: Backup your database before running this migration!

```sql
-- Create backup
mysqldump -u username -p database_name > backup_before_consolidation.sql
```

## Execution Steps

### Step 1: Review the Migration Script

Open `schema_consolidation_migration.sql` and review all changes.

### Step 2: Run Migration

**RECOMMENDED**: Use the idempotent version which is safe to run multiple times:

```bash
# Using MySQL command line
mysql -u username -p database_name < sql/schema_consolidation_migration_idempotent.sql

# Or using phpMyAdmin
# 1. Select your database
# 2. Go to SQL tab
# 3. Copy and paste the contents of schema_consolidation_migration_idempotent.sql
# 4. Execute
```

**Alternative**: Use the standard migration script:
```bash
mysql -u username -p database_name < sql/schema_consolidation_migration.sql
```

**Note**: 
- The idempotent version checks if changes are already applied before attempting them
- The standard version uses a transaction. Review the changes before committing:
  - The script ends with `-- COMMIT;` commented out
  - Uncomment `COMMIT;` when ready to apply changes
  - Use `ROLLBACK;` if you need to undo

### Step 3: Verify Changes

Run the verification script:

```bash
mysql -u username -p database_name < sql/schema_consolidation_verify.sql
```

All checks should show "✓ PASS" status.

### Step 4: Update Application Code

After successful migration, update your application code:

#### PHP Code Updates

1. **Update table references**:
   ```php
   // OLD
   $stmt = $pdo->prepare("SELECT * FROM personalinformation WHERE PersonID = ?");
   $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
   
   // NEW
   $stmt = $pdo->prepare("SELECT * FROM user_accounts WHERE PersonID = ?");
   $stmt = $pdo->prepare("SELECT * FROM people WHERE people_id = ?");
   ```

2. **Update foreign key lookups**:
   ```php
   // OLD
   $stmt = $pdo->prepare("SELECT * FROM permit_responsibles WHERE person_id IN (SELECT people_id FROM users)");
   
   // NEW
   $stmt = $pdo->prepare("SELECT * FROM permit_responsibles WHERE person_id IN (SELECT people_id FROM people)");
   ```

3. **Update risk references**:
   ```php
   // OLD
   $stmt = $pdo->prepare("SELECT * FROM controls WHERE risk_id IN (SELECT risk_id FROM risks)");
   $stmt = $pdo->prepare("SELECT * FROM process_map_risk WHERE risk_id IN (SELECT risk_id FROM risks)");
   
   // NEW
   $stmt = $pdo->prepare("SELECT * FROM controls WHERE risk_id IN (SELECT risk_id FROM risk_register)");
   $stmt = $pdo->prepare("SELECT * FROM process_map_risk WHERE risk_id IN (SELECT risk_id FROM risk_register)");
   ```

## Rollback Procedure

If you need to rollback the migration:

1. **Restore from backup** (recommended):
   ```bash
   mysql -u username -p database_name < backup_before_consolidation.sql
   ```

2. **Or use rollback script** (note: data in dropped tables will be lost):
   ```bash
   mysql -u username -p database_name < sql/schema_consolidation_rollback.sql
   ```

## Impact Assessment

### Tables Affected

- **Dropped**: `users`, `risks`, `hazards`
- **Renamed**: `personalinformation` → `user_accounts`
- **Modified**: `permit_responsibles`, `permit_energies`, `controls`, `process_map_risk`

### Data Impact

- ✅ **No data loss** for user accounts (renamed table preserves all data)
- ⚠️ **Data loss** for `users` table (if it contained unique data not in `personalinformation`)
- ⚠️ **Data loss** for `risks` and `hazards` tables (ensure data is migrated to `risk_register` first if needed)

### Application Impact

- All queries referencing `users` table need updating
- All queries referencing `personalinformation` need updating to `user_accounts`
- All queries referencing `risks` or `hazards` need updating to `risk_register`
- Foreign key relationships now point to `people` table instead of `users`

## Troubleshooting

### Error: "Table 'users' doesn't exist"
- This is expected after migration. Update your code to use `people` table instead.

### Error: "Table 'personalinformation' doesn't exist"
- The table was renamed to `user_accounts`. Update your code accordingly.

### Error: "Foreign key constraint fails"
- Ensure all referenced data exists in the target tables (`people`, `risk_register`)
- Check for orphaned records before migration

### Error: "Cannot drop table 'risks' - foreign key constraint"
- Ensure all foreign keys pointing to `risks` have been redirected first
- Check `controls` and `process_map_risk` tables

## Support

If you encounter issues:
1. Check the verification script output
2. Review foreign key constraints: `SHOW CREATE TABLE table_name;`
3. Check for orphaned records in affected tables
4. Restore from backup if needed

