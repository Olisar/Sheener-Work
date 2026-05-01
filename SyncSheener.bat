@echo off
setlocal
:: =====================================================================
:: Sheener Project GitHub Sync Script
:: =====================================================================

:: Set the project directory
set PROJECT_DIR=d:\xampp\htdocs\sheener

:: Set the Git path (using full path to bypass environment issues)
set GIT_PATH="C:\Program Files\Git\mingw64\bin\git.exe"

:: Navigate to project directory
cd /d "%PROJECT_DIR%"

echo.
echo ==========================================================
echo   SHEENER - GITHUB SYNC
echo ==========================================================
echo.

:: Check if git is available at the specified path
if not exist %GIT_PATH% (
    echo [ERROR] Git was not found at %GIT_PATH%
    echo Please verify your Git installation.
    echo.
    pause
    exit /b 1
)

:: ==========================================================
:: DATABASE EXPORT
:: ==========================================================
echo [+] Exporting database...
set MYSQLDUMP_PATH="d:\xampp\mysql\bin\mysqldump.exe"
set DB_USER=root
set DB_NAME=sheener
set SQL_OUTPUT_FILE=sql\sheener_backup.sql

if not exist d:\xampp\mysql\bin\mysqldump.exe (
    echo [WARNING] mysqldump not found at %MYSQLDUMP_PATH%
    echo Skipping database backup.
) else (
    %MYSQLDUMP_PATH% -u%DB_USER% %DB_NAME% > %SQL_OUTPUT_FILE%
    if %errorlevel% equ 0 (
        echo [SUCCESS] Database exported to %SQL_OUTPUT_FILE%
    ) else (
        echo [ERROR] Database export failed!
    )
)
echo.

echo [+] Adding changes...
%GIT_PATH% add .

echo [+] Committing changes...
:: Get current date and time for the commit message using PowerShell for robustness
for /f "tokens=*" %%i in ('powershell -NoProfile -Command "Get-Date -Format 'yyyy-MM-dd HH:mm'"') do set TIMESTAMP=%%i

%GIT_PATH% commit -m "Sync: %TIMESTAMP%"

echo [+] Pushing to GitHub (origin main)...
%GIT_PATH% push origin main

if %errorlevel% equ 0 (
    echo.
    echo [SUCCESS] Project successfully synced with GitHub!
) else (
    echo.
    echo [FAILED] Push failed. Check your internet connection or GitHub permissions.
)

echo.
pause
