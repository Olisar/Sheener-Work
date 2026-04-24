# Schema Consolidation Migration Checklist

## ✅ Step 1: Database Migration - COMPLETED
- [x] Migration script executed successfully
- [x] Verification script run - check results

## 📋 Step 2: Update Application Code

### Files That Need Updates:

#### **CRITICAL - User Authentication Files:**
1. ✅ `php/login.php` - Update `personalinformation` → `user_accounts`
2. ✅ `php/change_password.php` - Update `personalinformation` → `user_accounts`
3. ✅ `php/register.php` - Update `PersonalInformation` → `user_accounts`
4. ✅ `php/ss.php` - Update `PersonalInformation` → `user_accounts`
5. ✅ `php/delete_person.php` - Update `personalinformation` → `user_accounts`
6. ✅ `php/get_audit_logs.php` - Update `users` → `people` (if needed)
7. ⚠️ `encrypt.php` - Update `PersonalInformation` → `user_accounts`

#### **CRITICAL - Risk/Hazard Files:**
1. ⚠️ `php/get_task_questionnaire.php` - Update `hazards` table references
2. ⚠️ `php/update_assessment.php` - Update `risks` and `hazards` references
3. ⚠️ `php/import_hazards.php` - Update `hazards` table references
4. ⚠️ `php/update_task.php` - Update `hazards` table references
5. ⚠️ `php/get_assessment.php` - Update `risks` and `hazards` references

#### **Files Already Using Correct Tables:**
- ✅ `php/api_risk_register.php` - Already uses `risk_register`
- ✅ `api/risk/index.php` - Already uses `risk_register`
- ✅ `php/process_form.php` - Already uses `people` table

## 🔍 Step 3: Test Critical Functions

### Authentication Tests:
- [ ] User login
- [ ] Password change
- [ ] User registration
- [ ] User deletion

### Risk Management Tests:
- [ ] Task questionnaire loading
- [ ] Risk assessment creation/update
- [ ] Hazard import (if still used)
- [ ] Controls display

## 📝 Step 4: Update Documentation

- [ ] Update API documentation
- [ ] Update database schema documentation
- [ ] Update developer guides

## ⚠️ Important Notes:

1. **Hazards Table**: The `hazards` table was dropped. If your application still needs hazard data, ensure it's migrated to `risk_register` or `hira_hazard_links` first.

2. **Risks Table**: The `risks` table was dropped. All risk data should now come from `risk_register`.

3. **User Accounts**: The `personalinformation` table is now `user_accounts`. All queries need updating.

4. **Foreign Keys**: Foreign keys now point to `people` table instead of `users` table.

## 🚨 Rollback Plan

If issues occur, use:
```sql
source sql/schema_consolidation_rollback.sql;
```

**Note**: Rollback will recreate table structures but **data will be lost** for dropped tables.

