@echo off
REM ============================================================================
REM Run Migration Script
REM Description: Executes the 001_add_process_map_linkages.sql migration
REM Usage: run_migration.bat [database_name] [username] [password]
REM WARNING: This will modify your database. Make sure you have a backup!
REM ============================================================================

setlocal

REM Default values
set DB_NAME=sheener
set DB_USER=root
set DB_PASS=

REM Get parameters if provided
if not "%1"=="" set DB_NAME=%1
if not "%2"=="" set DB_USER=%2
if not "%3"=="" set DB_PASS=%3

echo ============================================================================
echo WARNING: This will modify your database!
echo Make sure you have created a backup before proceeding.
echo ============================================================================
echo Database: %DB_NAME%
echo User: %DB_USER%
echo.
pause

REM Check if password is provided
if "%DB_PASS%"=="" (
    echo Running migration (you will be prompted for MySQL password)...
    echo.
    mysql -u %DB_USER% -p %DB_NAME% < "%~dp0001_add_process_map_linkages.sql"
) else (
    mysql -u %DB_USER% -p%DB_PASS% %DB_NAME% < "%~dp0001_add_process_map_linkages.sql"
)

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ============================================================================
    echo Migration completed successfully!
    echo ============================================================================
    echo.
    echo Next steps:
    echo 1. Run the test script: run_test_migration.bat
    echo 2. Verify the tables were created
    echo 3. Test the API endpoints
    echo.
) else (
    echo.
    echo ============================================================================
    echo ERROR: Migration failed. Please check the error messages above.
    echo ============================================================================
    echo.
    echo If you need to rollback, restore from your backup.
    exit /b 1
)

endlocal

