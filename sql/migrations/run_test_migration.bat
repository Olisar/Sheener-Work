@echo off
REM ============================================================================
REM Run Migration Test Script
REM Description: Executes the test_migration.sql script using MySQL
REM Usage: run_test_migration.bat [database_name] [username] [password]
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
echo Running Migration Test Script
echo ============================================================================
echo Database: %DB_NAME%
echo User: %DB_USER%
echo.

REM Check if password is provided
if "%DB_PASS%"=="" (
    echo Note: You will be prompted for MySQL password
    echo.
    mysql -u %DB_USER% -p %DB_NAME% < "%~dp0test_migration.sql"
) else (
    mysql -u %DB_USER% -p%DB_PASS% %DB_NAME% < "%~dp0test_migration.sql"
)

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ============================================================================
    echo Test completed successfully!
    echo ============================================================================
) else (
    echo.
    echo ============================================================================
    echo ERROR: Test failed. Please check the error messages above.
    echo ============================================================================
    exit /b 1
)

endlocal

