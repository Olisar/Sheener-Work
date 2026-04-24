# Next Steps After Schema Consolidation Migration

## ✅ Completed Steps

1. ✅ Database migration executed successfully
2. ✅ Verification script run
3. ✅ Critical PHP files updated:
   - `php/login.php` - Updated to use `user_accounts`
   - `php/change_password.php` - Updated to use `user_accounts`
   - `php/register.php` - Updated to use `user_accounts`
   - `php/delete_person.php` - Updated to use `user_accounts`
   - `php/ss.php` - Updated to use `user_accounts`
   - `php/get_audit_logs.php` - Updated to use `user_accounts`

## 🔄 Remaining Tasks

### 1. Update Risk/Hazard Related Files

These files reference the dropped `risks` and `hazards` tables and need to be updated to use `risk_register`:

- ⚠️ `php/get_task_questionnaire.php` - Line 35: References `hazards` table
- ⚠️ `php/update_assessment.php` - Lines 85, 125: References `risks` and `hazards` tables
- ⚠️ `php/import_hazards.php` - Multiple references to `hazards` table
- ⚠️ `php/update_task.php` - Line 119: References `hazards` table
- ⚠️ `php/get_assessment.php` - Lines 30-54: References `risks` and `hazards` tables

**Note**: Before updating these files, ensure that:
- All hazard data has been migrated to `risk_register` or `hira_hazard_links`
- The relationship between tasks and risks is properly established
- Any existing functionality that depends on the old `hazards` → `risks` structure is redesigned

### 2. Update Other Files

- ⚠️ `encrypt.php` - Line 107-109: References `PersonalInformation` table

### 3. Test Critical Functions

#### Authentication Tests:
- [ ] Test user login
- [ ] Test password change
- [ ] Test user registration
- [ ] Test user deletion
- [ ] Test audit log retrieval

#### Risk Management Tests:
- [ ] Test task questionnaire loading (if still using hazards)
- [ ] Test risk assessment creation/update (if still using risks)
- [ ] Test controls display (should work with risk_register)

### 4. Database Verification

Run the verification script again to confirm all changes:
```sql
source sql/schema_consolidation_verify.sql;
```

### 5. Check for Additional References

Search your codebase for any remaining references:
```bash
# Search for old table names
grep -r "personalinformation" php/
grep -r "PersonalInformation" php/
grep -r "FROM users" php/
grep -r "JOIN users" php/
grep -r "FROM risks" php/
grep -r "FROM hazards" php/
```

## ⚠️ Important Notes

1. **Hazards Table**: The `hazards` table was dropped. If your application still needs hazard data:
   - Check if data was migrated to `risk_register`
   - Check if `hira_hazard_links` table contains the needed relationships
   - Update queries to use the new structure

2. **Risks Table**: The `risks` table was dropped. All risk data should now come from `risk_register`.

3. **User Accounts**: The `personalinformation` table is now `user_accounts`. All queries have been updated in critical files.

4. **Foreign Keys**: Foreign keys now point to `people` table instead of `users` table.

## 🚨 If Issues Occur

1. Check error logs for specific table references
2. Run verification script to see what's missing
3. If needed, use rollback script (note: data will be lost for dropped tables):
   ```sql
   source sql/schema_consolidation_rollback.sql;
   ```

## 📝 Documentation Updates Needed

- [ ] Update API documentation
- [ ] Update database schema documentation  
- [ ] Update developer guides
- [ ] Update user guides (if table names are mentioned)

