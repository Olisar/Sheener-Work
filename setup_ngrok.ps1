# SHEEner HTTPS Setup Script
# This script helps you set up ngrok for HTTPS access

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  SHEEner HTTPS Setup with ngrok" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check if ngrok is installed
$ngrokPath = Get-Command ngrok -ErrorAction SilentlyContinue

if ($ngrokPath) {
    Write-Host "ngrok is already installed at: $($ngrokPath.Source)" -ForegroundColor Green
    Write-Host ""
} else {
    Write-Host "ngrok is not installed" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "To install ngrok:" -ForegroundColor White
    Write-Host "1. Visit: https://ngrok.com/download" -ForegroundColor White
    Write-Host "2. Download ngrok for Windows" -ForegroundColor White
    Write-Host "3. Extract ngrok.exe to a folder" -ForegroundColor White
    Write-Host "4. Add that folder to your PATH, or run from that location" -ForegroundColor White
    Write-Host ""
    Write-Host "Quick Install Option:" -ForegroundColor Cyan
    Write-Host "You can also install via Chocolatey: choco install ngrok" -ForegroundColor White
    Write-Host ""
    
    $download = Read-Host "Would you like me to open the ngrok download page? (Y/N)"
    if ($download -eq "Y" -or $download -eq "y") {
        Start-Process "https://ngrok.com/download"
        Write-Host "Opening ngrok download page..." -ForegroundColor Green
    }
    
    Write-Host ""
    Write-Host "After installing ngrok, run this script again!" -ForegroundColor Yellow
    Write-Host ""
    Read-Host "Press Enter to exit"
    exit
}

# Check if ngrok is authenticated
Write-Host "Checking ngrok authentication..." -ForegroundColor Cyan
$configPath = Join-Path $env:USERPROFILE ".ngrok2\ngrok.yml"

if (Test-Path $configPath) {
    $config = Get-Content $configPath -Raw
    if ($config -match "authtoken:") {
        Write-Host "ngrok is authenticated" -ForegroundColor Green
    } else {
        Write-Host "ngrok is not authenticated" -ForegroundColor Yellow
        Write-Host ""
        Write-Host "To authenticate ngrok:" -ForegroundColor White
        Write-Host "1. Sign up at: https://dashboard.ngrok.com/signup" -ForegroundColor White
        Write-Host "2. Get your authtoken from: https://dashboard.ngrok.com/get-started/your-authtoken" -ForegroundColor White
        Write-Host "3. Run: ngrok config add-authtoken YOUR_TOKEN_HERE" -ForegroundColor White
        Write-Host ""
        
        $openDashboard = Read-Host "Would you like me to open the ngrok dashboard? (Y/N)"
        if ($openDashboard -eq "Y" -or $openDashboard -eq "y") {
            Start-Process "https://dashboard.ngrok.com/get-started/your-authtoken"
            Write-Host "Opening ngrok dashboard..." -ForegroundColor Green
        }
        
        Write-Host ""
        $token = Read-Host "Enter your ngrok authtoken (or press Enter to skip)"
        if ($token) {
            Write-Host "Configuring ngrok..." -ForegroundColor Cyan
            & ngrok config add-authtoken $token
            Write-Host "ngrok authenticated!" -ForegroundColor Green
        } else {
            Write-Host "Skipping authentication. You'll need to do this manually." -ForegroundColor Yellow
            Write-Host ""
            Read-Host "Press Enter to exit"
            exit
        }
    }
} else {
    Write-Host "ngrok config not found" -ForegroundColor Yellow
    Write-Host "Please authenticate ngrok first." -ForegroundColor White
    Write-Host ""
    
    $openDashboard = Read-Host "Would you like me to open the ngrok dashboard? (Y/N)"
    if ($openDashboard -eq "Y" -or $openDashboard -eq "y") {
        Start-Process "https://dashboard.ngrok.com/get-started/your-authtoken"
        Write-Host "Opening ngrok dashboard..." -ForegroundColor Green
    }
    
    Write-Host ""
    $token = Read-Host "Enter your ngrok authtoken (or press Enter to skip)"
    if ($token) {
        Write-Host "Configuring ngrok..." -ForegroundColor Cyan
        & ngrok config add-authtoken $token
        Write-Host "ngrok authenticated!" -ForegroundColor Green
    } else {
        Write-Host "Skipping authentication. You'll need to do this manually." -ForegroundColor Yellow
        Write-Host ""
        Read-Host "Press Enter to exit"
        exit
    }
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Starting ngrok tunnel..." -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check if Apache is running
$apacheRunning = Get-Process -Name "httpd" -ErrorAction SilentlyContinue

if ($apacheRunning) {
    Write-Host "Apache is running" -ForegroundColor Green
} else {
    Write-Host "Apache is not running" -ForegroundColor Red
    Write-Host "Please start Apache in XAMPP Control Panel first!" -ForegroundColor Yellow
    Write-Host ""
    Read-Host "Press Enter to exit"
    exit
}

Write-Host ""
Write-Host "Starting ngrok tunnel on port 80..." -ForegroundColor Cyan
Write-Host ""
Write-Host "IMPORTANT:" -ForegroundColor Yellow
Write-Host "- ngrok will display your HTTPS URL" -ForegroundColor White
Write-Host "- Copy the HTTPS URL (e.g., https://abc123.ngrok-free.app)" -ForegroundColor White
Write-Host "- Your mobile form will be at: YOUR_URL/sheener/mobile_report.php" -ForegroundColor White
Write-Host "- Keep this window open while using the app" -ForegroundColor White
Write-Host "- Press Ctrl+C to stop ngrok" -ForegroundColor White
Write-Host ""
Write-Host "After ngrok starts, update the QR generator with your HTTPS URL!" -ForegroundColor Cyan
Write-Host ""

Read-Host "Press Enter to start ngrok"

# Start ngrok
& ngrok http 80
