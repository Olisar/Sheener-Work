# 🚀 PHP 8.x + MySQL 8 Migration Guide

**Project:** SHEEner EHS Management System  
**Status:** Implementation Ready  
**Target Environment:** PHP 8.1 / 8.2 / 8.3 & MySQL 8.0  

---

## 🛠️ Overview of Changes

The SHEEner platform has been reviewed and optimized for modern PHP 8 and MySQL 8 environments. Key changes focus on **strict typing enforcement**, **reserved word protection**, and **null safety**.

### 1. PHP 8 Strict Mode Compatibility
In PHP 8.1+, passing `null` to internal functions like `strlen()`, `str_replace()`, or `json_decode()` results in a deprecation warning or a fatal error depending on the context.

- **Refactoring Applied:** All data fetched from `$_POST`, `$_GET`, or `php://input` is now validated or cast to an empty string `(string)` before being processed by internal functions.
- **Example:**
  ```php
  // Old (PHP 7.4)
  $len = strlen($_POST['data']); // Fails if 'data' is missing
  
  // New (PHP 8.1+)
  $data = $_POST['data'] ?? '';
  $len = strlen($data); // Always safe
  ```

### 2. MySQL 8 Reserved Words
MySQL 8 introduced several new reserved words (e.g., `SYSTEM`, `GROUPS`, `ADMIN`).

- **Query Protection:** All SQL queries have been reviewed to ensure that column and table names are wrapped in backticks (`` ` ``).
- **Audit Logic:** Enhanced the `auditlog` handling to ensure compatibility with MySQL 8's newer logging features.

### 3. PDO Optimization
The project already utilizes **PDO** for database interaction, which is highly stable in PHP 8.
- **Connection Charset:** Enforced `utf8mb4` in `php/database.php` to match MySQL 8's default collation.
- **Error Modes:** Enabled `PDO::ERRMODE_EXCEPTION` by default to catch structural issues early in dev/staging.

---

## 📋 Pre-Migration Checklist

Before switching your server to PHP 8.1+:

1.  [ ] **Backup Database**: Perform a full SQL dump of the `sheener` database.
2.  [ ] **Update Composer**: If you use any external packages, run `composer update` to pull PHP 8 compatible versions.
3.  [ ] **Clear Caches**: Ensure the browser and server caches are cleared after the code update.

---

## 🔧 Technical Details for Admins

### Database Connection Updates
If your MySQL 8 server uses the new default authentication plugin (`caching_sha2_password`), ensure your PHP `mysqlnd` extension is up to date, or reset the user password:
```sql
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'your_password';
```

### Reporting
The system's PDF generation (TCPDF) is compatible with PHP 8.1. If you encounter errors, ensure the `mbstring` extension is enabled in your `php.ini`.

---

**Maintained By:** SHEEner Technical Team  
**Last Updated:** April 2026
