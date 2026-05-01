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

echo [+] Adding changes...
%GIT_PATH% add .

echo [+] Committing changes...
:: Get current date and time for the commit message
for /f "tokens=2-4 delims=/ " %%a in ('date /t') do (set mydate=%%c-%%a-%%b)
for /f "tokens=1-2 delims=: " %%a in ('time /t') do (set mytime=%%a:%%b)

%GIT_PATH% commit -m "Sync: %mydate% %mytime%"

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
