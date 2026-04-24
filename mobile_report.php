<?php /* File: sheener/mobile_report.php */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SHEEner - Quick Report</title>
    
    <!-- PWA Meta Tags -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0A2F64">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Sheener">
    
    <link rel="icon" href="img/favicon/faviconAY.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #0A2F64 0%, #1a4d8f 100%);
            min-height: 100vh;
            padding: 0;
            overflow-x: hidden;
        }
        
        /* Header */
        .header {
            background: #0A2F64;
            backdrop-filter: blur(10px);
            padding: 15px 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .logo {
            height: 35px;
            filter: brightness(0) invert(1); /* Make logo white for dark background */
        }
        
        .header-title {
            font-size: 1.2rem;
            color: #ffffff;
            font-weight: 700;
        }
        
        /* Main Container */
        .container {
            padding: 20px;
            max-width: 600px;
            margin: 0 auto;
        }
        
        /* Welcome Card */
        .welcome-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .welcome-icon {
            font-size: 3rem;
            color: #e74c3c;
            margin-bottom: 15px;
        }
        
        .welcome-title {
            font-size: 1.5rem;
            color: #0A2F64;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .welcome-text {
            color: #666;
            line-height: 1.6;
            font-size: 0.95rem;
        }
        
        /* Form Card */
        .form-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        /* Form Groups */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        .required::after {
            content: " *";
            color: #e74c3c;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 14px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s;
            background: white;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3685fe;
            box-shadow: 0 0 0 4px rgba(54, 133, 254, 0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        /* GPS Status */
        .gps-status {
            padding: 12px;
            border-radius: 10px;
            font-size: 0.9rem;
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .gps-status.acquiring {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .gps-status.success {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .gps-status.error {
            background: #ffebee;
            color: #c62828;
        }
        
        /* Camera Button */
        .camera-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #3685fe 0%, #276ac8 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 15px;
        }
        
        .camera-btn:active {
            transform: scale(0.98);
        }
        
        /* File Preview */
        .file-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        
        .file-item {
            position: relative;
            width: 80px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #e0e0e0;
        }
        
        .file-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .file-item .remove-btn {
            position: absolute;
            top: 4px;
            right: 4px;
            background: rgba(231, 76, 60, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            font-size: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Submit Button */
        .submit-btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
            margin-top: 10px;
        }
        
        .submit-btn:active {
            transform: scale(0.98);
        }
        
        .submit-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            box-shadow: none;
        }
        
        /* Message Box */
        .message-box {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: none;
            animation: slideDown 0.3s ease;
        }
        
        .message-box.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message-box.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .loading-overlay.show {
            display: flex;
        }
        
        .loading-content {
            background: white;
            padding: 30px;
            border-radius: 16px;
            text-align: center;
            max-width: 80%;
        }
        
        .spinner {
            font-size: 3rem;
            color: #3685fe;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .loading-text {
            margin-top: 15px;
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            padding: 20px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.85rem;
        }
        
        /* Hidden file input */
        #photo_input {
            display: none;
        }
        
        /* Install Prompt Banner */
        .install-prompt {
            background: linear-gradient(135deg, #0A2F64 0%, #1a4d8f 100%);
            color: white;
            padding: 15px 20px;
            margin: 15px 20px;
            border-radius: 12px;
            display: none;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            animation: slideDown 0.3s ease;
        }
        
        .install-prompt.show {
            display: flex;
        }
        
        .install-prompt-content {
            flex: 1;
        }
        
        .install-prompt-title {
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 5px;
        }
        
        .install-prompt-text {
            font-size: 0.85rem;
            opacity: 0.9;
            line-height: 1.4;
        }
        
        .install-prompt-buttons {
            display: flex;
            gap: 10px;
            margin-left: 15px;
        }
        
        .install-btn {
            background: white;
            color: #0A2F64;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .install-btn:active {
            transform: scale(0.95);
        }
        
        .dismiss-install {
            background: transparent;
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .dismiss-install:active {
            background: rgba(255, 255, 255, 0.1);
        }
        
        /* Status Bar */
        .status-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 12px 20px;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 200;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .status-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
        }
        
        .status-indicator.online {
            color: #27ae60;
        }
        
        .status-indicator.offline {
            color: #e74c3c;
        }
        
        .status-indicator.syncing {
            color: #3685fe;
        }
        
        .pending-count {
            background: #e74c3c;
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            min-width: 24px;
            text-align: center;
        }
        
        .pending-count.hidden {
            display: none;
        }
        
        /* Adjust container padding for status bar */
        .container {
            padding-bottom: 80px;
        }
        
        /* Hide install prompt on small screens if needed */
        @media (max-width: 400px) {
            .install-prompt {
                flex-direction: column;
                text-align: center;
            }
            
            .install-prompt-buttons {
                margin-left: 0;
                margin-top: 10px;
                width: 100%;
            }
            
            .install-btn, .dismiss-install {
                flex: 1;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <img src="img/Amneal_Logo_new.svg" alt="Logo" class="logo">
            <div class="header-title">Quick Report</div>
        </div>
    </div>
    
    <!-- Main Container -->
    <div class="container">
        <!-- Install Prompt Banner -->
        <div id="installPrompt" class="install-prompt">
            <div class="install-prompt-content">
                <div class="install-prompt-title">
                    <i class="fas fa-download" style="margin-right: 8px;"></i>Install App for Offline Use
                </div>
                <div class="install-prompt-text">
                    Install this app to use it anytime, even without WiFi. Events are saved on your phone and sync automatically when connected.
                </div>
            </div>
            <div class="install-prompt-buttons">
                <button id="installButton" class="install-btn" type="button">Install</button>
                <button class="dismiss-install" type="button" id="dismissInstallBtn">×</button>
            </div>
        </div>
        
        <!-- Welcome Card -->
        <div class="welcome-card">
            <div class="welcome-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h1 class="welcome-title">Report an Issue</h1>
            <p class="welcome-text">
                Report safety incidents, near misses, or observations quickly and easily. 
                No login required.
            </p>
            <div style="background: #e8f5e9; padding: 12px; border-radius: 8px; margin-top: 15px; border-left: 4px solid #4CAF50;">
                <p style="margin: 0; font-size: 0.9rem; color: #2e7d32; line-height: 1.5;">
                    <i class="fas fa-wifi" style="margin-right: 6px;"></i><strong>Works Offline:</strong> Install this app to use it anytime, even without WiFi. Events are saved on your phone and sync automatically when you reconnect.
                </p>
            </div>
        </div>
        
        <!-- Message Box -->
        <div id="messageBox" class="message-box"></div>
        
        <!-- Form Card -->
        <div class="form-card">
            <form id="reportForm" enctype="multipart/form-data">
                <!-- Your Name -->
                <div class="form-group">
                    <label for="reporter_name" class="required">Your Name</label>
                    <input type="text" id="reporter_name" name="reporter_name" placeholder="Enter your name" required>
                </div>
                
                <!-- Your Email -->
                <div class="form-group">
                    <label for="reporter_email">Your Email (optional)</label>
                    <input type="email" id="reporter_email" name="reporter_email" placeholder="your.email@example.com">
                </div>
                
                <!-- Event Date & Time -->
                <div class="form-group">
                    <label for="eventDate">Event Date & Time</label>
                    <input type="datetime-local" id="eventDate" name="eventDate">
                </div>
                
                <!-- Location -->
                <div class="form-group">
                    <label for="location" class="required">Location</label>
                    <input type="text" id="location" name="location" placeholder="Where did this occur?" required>
                    <input type="hidden" id="location_id" name="location_id">
                </div>
                
                <!-- GPS Coordinates (Hidden) -->
                <input type="hidden" id="gps_coordinates" name="gps_coordinates">
                <div id="gps_status" class="gps-status acquiring">
                    <i class="fas fa-spinner fa-spin"></i>
                    <span>Acquiring GPS location...</span>
                </div>
                
                <!-- Event Category -->
                <div class="form-group">
                    <label for="primaryCategory" class="required">Event Category</label>
                    <select id="primaryCategory" name="primaryCategory" required>
                        <option value="">-- Select Category --</option>
                        <option value="Audit">Audit</option>
                        <option value="Near Miss">Near Miss</option>
                        <option value="Accident">Accident</option>
                        <option value="Good Catch">Good Catch</option>
                        <option value="Opportunity for Improvement">Opportunity for Improvement</option>
                    </select>
                </div>
                
                <!-- Subcategory -->
                <div class="form-group">
                    <label for="secondaryCategory" class="required">Subcategory</label>
                    <select id="secondaryCategory" name="secondaryCategory" required disabled>
                        <option value="">-- Select Category First --</option>
                    </select>
                </div>
                
                <!-- Description -->
                <div class="form-group">
                    <label for="description" class="required">Description</label>
                    <textarea id="description" name="description" placeholder="Provide a detailed description of the event, observation, or comment..." required></textarea>
                </div>
                
                <!-- Camera Button -->
                <button type="button" class="camera-btn" onclick="document.getElementById('photo_input').click()">
                    <i class="fas fa-camera"></i>
                    <span>Take Photo / Add Attachment</span>
                </button>
                
                <!-- Hidden File Input -->
                <input type="file" id="photo_input" name="attachments[]" multiple accept="image/*,.pdf,.doc,.docx" capture="environment">
                
                <!-- File Preview -->
                <div id="file_preview" class="file-preview"></div>
                
                <!-- Submit Button -->
                <button type="submit" class="submit-btn">
                    <i class="fas fa-paper-plane"></i> Submit Report
                </button>
            </form>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            © 2025 SHEEner. All rights reserved.
            <br>
            <a href="#" id="manualInstallBtn" style="color: rgba(255,255,255,0.6); text-decoration: underline; font-size: 0.8rem; margin-top: 10px; display: inline-block; margin-right: 15px;">Install App</a>
            <a href="#" id="resetAppBtn" style="color: rgba(255,255,255,0.6); text-decoration: underline; font-size: 0.8rem; margin-top: 10px; display: inline-block;">Reset / Fix Issues</a>
        </div>
    </div>
    
    <!-- Status Bar -->
    <div class="status-bar" id="statusBar">
        <div class="status-indicator" id="statusIndicator">
            <i class="fas fa-circle"></i>
            <span id="statusText">Initializing...</span>
        </div>
        <div class="pending-count hidden" id="pendingCount">0</div>
    </div>
    
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-content">
            <div class="spinner">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <div class="loading-text">Submitting your report...</div>
        </div>
    </div>
    
    <!-- Offline Storage and Sync Scripts -->
    <script src="js/offline-storage.js" onerror="console.error('Failed to load offline-storage.js')"></script>
    <script src="js/sync-manager.js" onerror="console.error('Failed to load sync-manager.js')"></script>
    
    <script>
        // Prevent page freeze if scripts fail
        window.addEventListener('error', function(event) {
            console.error('Script error (non-blocking):', event.error);
            // Don't prevent default - let page continue
        }, true);
        
        // Ensure page is interactive even if initialization fails
        document.addEventListener('DOMContentLoaded', function() {
            // Mark page as ready after a short delay
            setTimeout(() => {
                document.body.style.pointerEvents = 'auto';
            }, 100);
        });
        // Initialize offline storage and sync
        let offlineStorage, syncManager;
        
        // Status UI elements
        const statusBar = document.getElementById('statusBar');
        const statusIndicator = document.getElementById('statusIndicator');
        const statusText = document.getElementById('statusText');
        const pendingCount = document.getElementById('pendingCount');
        
        // PWA Install Prompt - Initialize variables
        let deferredPrompt = null;
        let installPrompt = null;
        let installButton = null;
        
        // Make dismissInstallPrompt globally accessible immediately
        window.dismissInstallPrompt = function() {
            const prompt = document.getElementById('installPrompt');
            if (prompt) {
                prompt.classList.remove('show');
                localStorage.setItem('installPromptDismissed', Date.now().toString());
            }
        };
        
        // Show install prompt if app is not installed
        function showInstallPrompt() {
            try {
                // Check if already installed (standalone mode)
                if (window.matchMedia('(display-mode: standalone)').matches || 
                    window.navigator.standalone === true) {
                    return; // Already installed
                }
                
                // Check if user dismissed it before
                const dismissed = localStorage.getItem('installPromptDismissed');
                if (dismissed) {
                    const dismissedTime = parseInt(dismissed);
                    const daysSinceDismissed = (Date.now() - dismissedTime) / (1000 * 60 * 60 * 24);
                    if (daysSinceDismissed < 7) {
                        return; // Don't show again for 7 days
                    }
                }
                
                // Show prompt immediately (no delay needed)
                const prompt = document.getElementById('installPrompt');
                if (prompt) {
                    prompt.classList.add('show');
                    console.log('Install prompt shown');
                } else {
                    console.warn('Install prompt element not found');
                }
            } catch (error) {
                console.error('Error in showInstallPrompt:', error);
            }
        }
        
        // Listen for beforeinstallprompt event (Android Chrome)
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            showInstallPrompt();
        });
        
        // Also show prompt for iOS (which doesn't fire beforeinstallprompt)
        // Check if iOS and not already installed
        const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
        
        if (isIOS && !isStandalone) {
            // Show prompt for iOS after page load
            setTimeout(() => {
                showInstallPrompt();
            }, 3000);
        }
        
        // Handle install button click - set up after DOM ready
        function setupInstallButton() {
            installButton = document.getElementById('installButton');
            const manualInstallBtn = document.getElementById('manualInstallBtn');
            const dismissBtn = document.getElementById('dismissInstallBtn');
            
            // Shared install handler
            const handleInstall = async function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                console.log('Install button clicked, deferredPrompt:', deferredPrompt);
                
                if (deferredPrompt) {
                    // Android Chrome - use native prompt
                    try {
                        deferredPrompt.prompt();
                        const { outcome } = await deferredPrompt.userChoice;
                        console.log('User response to install prompt:', outcome);
                        deferredPrompt = null;
                        window.dismissInstallPrompt();
                    } catch (error) {
                        console.error('Error showing install prompt:', error);
                        showIOSInstallInstructions();
                    }
                } else {
                    // iOS or manual install instructions
                    showIOSInstallInstructions();
                }
            };
            
            if (installButton) {
                installButton.addEventListener('click', handleInstall);
            } else {
                console.warn('Install button not found');
            }
            
            if (manualInstallBtn) {
                manualInstallBtn.addEventListener('click', handleInstall);
            }
            
            // Set up dismiss button
            if (dismissBtn) {
                dismissBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Dismiss button clicked');
                    window.dismissInstallPrompt();
                });
            } else {
                console.warn('Dismiss button not found');
            }
        }
        
        function showIOSInstallInstructions() {
            const isIOSDevice = /iPad|iPhone|iPod/.test(navigator.userAgent);
            if (isIOSDevice) {
                alert('To install:\n\n1. Tap the Share button (square with arrow up)\n2. Scroll down and tap "Add to Home Screen"\n3. Tap "Add"\n\nOnce installed, the app will work offline and sync automatically when connected!');
            } else {
                alert('To install:\n\n1. Look for the install icon in your browser address bar\n2. Or use browser menu → "Install App" or "Add to Home Screen"\n\nOnce installed, the app will work offline and sync automatically when connected!');
            }
            window.dismissInstallPrompt();
        }
        
        // Initialize on page load - with error handling to prevent freezing
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize PWA install prompt elements
            installPrompt = document.getElementById('installPrompt');
            
            // Set up install button handler (must be after DOM ready)
            try {
                setupInstallButton();
            } catch (error) {
                console.error('Error setting up install button:', error);
            }
            
            // Set up category change handler (must be after DOM ready)
            try {
                const primaryCategory = document.getElementById('primaryCategory');
                const secondaryCategory = document.getElementById('secondaryCategory');
                
                if (primaryCategory && secondaryCategory) {
                    primaryCategory.addEventListener('change', function() {
                        const selectedPrimary = this.value;
                        
                        secondaryCategory.innerHTML = '<option value="">-- Select Subcategory --</option>';
                        
                        if (selectedPrimary && categoryMapping[selectedPrimary]) {
                            secondaryCategory.disabled = false;
                            categoryMapping[selectedPrimary].forEach(cat => {
                                const option = document.createElement('option');
                                option.value = cat;
                                option.textContent = cat;
                                secondaryCategory.appendChild(option);
                            });
                        } else {
                            secondaryCategory.disabled = true;
                        }
                    });
                    console.log('Category change handler initialized');
                } else {
                    console.error('Category elements not found');
                }
            } catch (error) {
                console.error('Error setting up category handler:', error);
            }
            
            // Initialize offline features asynchronously to prevent blocking
            (async function() {
                try {
                    // Wait a bit for scripts to load
                    await new Promise(resolve => setTimeout(resolve, 500));
                    
                    // Check if offline storage is available
                    if (!window.indexedDB) {
                        console.warn('IndexedDB not supported, offline features disabled');
                        if (statusText) {
                            statusText.textContent = 'Online mode only';
                        }
                        return;
                    }
                    
                    // Check if offline storage script loaded
                    if (!window.offlineStorage) {
                        console.warn('Offline storage script not loaded, offline features disabled');
                        if (statusText) {
                            statusText.textContent = 'Online mode only';
                        }
                        return;
                    }
                    
                    // Initialize offline storage with timeout
                    const initPromise = new Promise((resolve, reject) => {
                        const timeout = setTimeout(() => {
                            reject(new Error('Storage initialization timeout'));
                        }, 5000);
                        
                        window.offlineStorage.init()
                            .then(() => {
                                clearTimeout(timeout);
                                resolve();
                            })
                            .catch((error) => {
                                clearTimeout(timeout);
                                reject(error);
                            });
                    });
                    
                    await initPromise;
                    offlineStorage = window.offlineStorage;
                    console.log('Offline storage initialized');
                    
                    // Initialize sync manager
                    if (!window.syncManager) {
                        console.warn('Sync manager script not loaded');
                        return;
                    }
                    
                    syncManager = window.syncManager;
                        
                        // Update status UI immediately
                        if (statusIndicator && statusText) {
                            if (navigator.onLine) {
                                statusIndicator.className = 'status-indicator online';
                                statusText.textContent = 'Online';
                            } else {
                                statusIndicator.className = 'status-indicator offline';
                                statusText.textContent = 'Offline';
                            }
                        }
                        updatePendingCount();
                        
                        // DISABLED: Auto-sync to prevent continuous checking
                        // User can manually sync by submitting a form or the sync will happen on form submit
                        // setTimeout(() => {
                        //     try {
                        //         syncManager.startAutoSync(60000);
                        //     } catch (error) {
                        //         console.error('Error starting auto-sync:', error);
                        //     }
                        // }, 10000);
                        
                        // Listen for sync status updates
                        syncManager.onSyncStatus((status, data) => {
                            try {
                                handleSyncStatus(status, data);
                            } catch (error) {
                                console.error('Error in sync status handler:', error);
                            }
                        });

                    
                    // Monitor online/offline events (only add once) - simple status update only
                    if (!window.onlineOfflineListenersAdded) {
                        window.addEventListener('online', () => {
                            try {
                                // Just update basic status, no connection checking
                                if (statusIndicator && statusText) {
                                    statusIndicator.className = 'status-indicator online';
                                    statusText.textContent = 'Online';
                                }
                            } catch (error) {
                                console.error('Error handling online event:', error);
                            }
                        });
                        
                        window.addEventListener('offline', () => {
                            try {
                                if (statusIndicator && statusText) {
                                    statusIndicator.className = 'status-indicator offline';
                                    statusText.textContent = 'Offline';
                                }
                            } catch (error) {
                                console.error('Error handling offline event:', error);
                            }
                        });
                        window.onlineOfflineListenersAdded = true;
                    }
                    
                // Show install prompt if applicable (after initialization)
                // Show even if offline features fail - don't block on errors
                // Show immediately, don't wait
                try {
                    showInstallPrompt();
                } catch (error) {
                    console.error('Error showing install prompt:', error);
                }
                    
                } catch (error) {
                    console.error('Error initializing offline storage:', error);
                    // Don't block the page - just show a warning and set to online mode
                    if (statusText) {
                        statusText.textContent = navigator.onLine ? 'Online' : 'Offline';
                    }
                    if (statusIndicator) {
                        statusIndicator.className = navigator.onLine ? 'status-indicator online' : 'status-indicator offline';
                    }
                }
            })();
        });
        
        // Update connection status UI
        // Simplified connection status - no automatic checking to prevent loops
        function updateConnectionStatus() {
            if (!statusIndicator || !statusText) {
                return; // Elements not ready
            }
            
            // Just show basic online/offline status - no intranet checking
            // Intranet connection will be checked only when needed (form submission)
            if (navigator.onLine) {
                statusIndicator.className = 'status-indicator online';
                statusText.textContent = 'Online';
            } else {
                statusIndicator.className = 'status-indicator offline';
                statusText.textContent = 'Offline';
            }
        }
        
        // Update pending count
        async function updatePendingCount() {
            try {
                const count = await offlineStorage.getPendingCount();
                if (count > 0) {
                    pendingCount.textContent = count;
                    pendingCount.classList.remove('hidden');
                } else {
                    pendingCount.classList.add('hidden');
                }
            } catch (error) {
                console.error('Error getting pending count:', error);
            }
        }
        
        // Handle sync status updates
        function handleSyncStatus(status, data) {
            switch (status) {
                case 'start':
                    statusIndicator.className = 'status-indicator syncing';
                    statusText.textContent = 'Syncing...';
                    break;
                case 'syncing':
                    statusIndicator.className = 'status-indicator syncing';
                    statusText.textContent = 'Syncing events...';
                    break;
                case 'complete':
                    statusIndicator.className = 'status-indicator online';
                    statusText.textContent = `Synced ${data.synced} event(s)`;
                    updatePendingCount();
                    // Don't update connection status immediately - let it throttle naturally
                    // Show success message
                    if (data.synced > 0) {
                        showMessage(`Successfully synced ${data.synced} event(s)`, 'success');
                    }
                    break;
                case 'offline':
                    statusIndicator.className = 'status-indicator offline';
                    statusText.textContent = 'Offline';
                    break;
                case 'failed':
                    statusIndicator.className = 'status-indicator offline';
                    statusText.textContent = 'Sync failed';
                    // Don't update connection status immediately - let it throttle naturally
                    break;
            }
        }
        
        // Show message helper
        function showMessage(message, type) {
            const messageBox = document.getElementById('messageBox');
            messageBox.className = `message-box ${type}`;
            messageBox.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i> ${message}`;
            messageBox.style.display = 'block';
            setTimeout(() => {
                messageBox.style.display = 'none';
            }, 5000);
        }
        
        // Category mapping
        const categoryMapping = {
            'Audit': ['Material', 'Equipment', 'Process', 'Documentation', 'Training', 'Environmental', 'Other'],
            'Near Miss': ['Material', 'Equipment', 'Injuries', 'Environmental', 'Process', 'Safety', 'Other'],
            'Accident': ['Material', 'Equipment', 'Injuries', 'Environmental', 'Process', 'Safety', 'Other'],
            'Good Catch': ['Material', 'Equipment', 'Process', 'Quality', 'Safety', 'Environmental', 'Other'],
            'Opportunity for Improvement': ['Material', 'Equipment', 'Process', 'Efficiency', 'Quality', 'Environmental', 'Other']
        };
        
        // GPS Geolocation (with error handling to prevent freezing and loops)
        let gpsAcquiring = false; // Flag to prevent multiple simultaneous GPS requests
        let lastGPSAttempt = 0; // Track last GPS attempt time
        
        function acquireGPS() {
            // Prevent multiple simultaneous GPS requests
            if (gpsAcquiring) {
                console.log('GPS acquisition already in progress, skipping');
                return;
            }
            
            // Throttle GPS requests - don't retry more than once per 5 seconds
            const now = Date.now();
            if (now - lastGPSAttempt < 5000) {
                console.log('GPS request throttled');
                return;
            }
            
            try {
                const gpsStatus = document.getElementById('gps_status');
                const gpsCoordinates = document.getElementById('gps_coordinates');
                
                if (!gpsStatus || !gpsCoordinates) {
                    console.warn('GPS elements not found');
                    return;
                }
                
                gpsAcquiring = true;
                lastGPSAttempt = now;
                
                if ("geolocation" in navigator) {
                    // Set timeout to prevent hanging
                    const timeoutId = setTimeout(() => {
                        gpsAcquiring = false;
                        if (gpsStatus) {
                            gpsStatus.className = 'gps-status error';
                            gpsStatus.innerHTML = '<i class="fas fa-exclamation-triangle"></i><span>GPS timeout</span>';
                        }
                    }, 10000); // 10 second max
                    
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            clearTimeout(timeoutId);
                            gpsAcquiring = false;
                            try {
                                const lat = position.coords.latitude;
                                const lon = position.coords.longitude;
                                const accuracy = position.coords.accuracy;
                                
                                if (gpsCoordinates) {
                                    gpsCoordinates.value = lat + "," + lon;
                                }
                                if (gpsStatus) {
                                    gpsStatus.className = 'gps-status success';
                                    gpsStatus.innerHTML = '<i class="fas fa-check-circle"></i><span>Location: ' + 
                                        lat.toFixed(6) + ', ' + lon.toFixed(6) + ' (±' + Math.round(accuracy) + 'm)</span>';
                                }
                            } catch (error) {
                                console.error('Error processing GPS position:', error);
                                gpsAcquiring = false;
                            }
                        },
                        function(error) {
                            clearTimeout(timeoutId);
                            gpsAcquiring = false;
                            try {
                                if (gpsStatus) {
                                    gpsStatus.className = 'gps-status error';
                                    let errorMsg = 'Location unavailable';
                                    if (error.code === error.PERMISSION_DENIED) {
                                        errorMsg = 'Location permission denied';
                                    } else if (error.code === error.TIMEOUT) {
                                        errorMsg = 'Location request timeout';
                                    }
                                    gpsStatus.innerHTML = '<i class="fas fa-exclamation-triangle"></i><span>' + errorMsg + '</span>';
                                }
                            } catch (e) {
                                console.error('Error handling GPS error:', e);
                            }
                        },
                        {
                            enableHighAccuracy: false, // Changed to false to prevent hanging
                            timeout: 8000, // 8 second timeout
                            maximumAge: 60000 // Accept cached location up to 1 minute old
                        }
                    );
                } else {
                    gpsAcquiring = false;
                    if (gpsStatus) {
                        gpsStatus.className = 'gps-status error';
                        gpsStatus.innerHTML = '<i class="fas fa-times-circle"></i><span>GPS not supported</span>';
                    }
                }
            } catch (error) {
                console.error('Error in acquireGPS:', error);
                gpsAcquiring = false;
                // Don't let GPS errors freeze the page
            }
        }
        
        // Category change handler - moved inside DOMContentLoaded to ensure elements exist
        // This is now handled below in the DOMContentLoaded section
        
        // File preview
        let selectedFiles = [];
        
        document.getElementById('photo_input').addEventListener('change', function(e) {
            const files = Array.from(e.target.files);
            selectedFiles = selectedFiles.concat(files);
            updateFilePreview();
        });
        
        function updateFilePreview() {
            const preview = document.getElementById('file_preview');
            preview.innerHTML = '';
            
            selectedFiles.forEach((file, index) => {
                const fileItem = document.createElement('div');
                fileItem.className = 'file-item';
                
                if (file.type.startsWith('image/')) {
                    const img = document.createElement('img');
                    img.src = URL.createObjectURL(file);
                    fileItem.appendChild(img);
                }
                
                const removeBtn = document.createElement('button');
                removeBtn.className = 'remove-btn';
                removeBtn.innerHTML = '<i class="fas fa-times"></i>';
                removeBtn.onclick = function() {
                    selectedFiles.splice(index, 1);
                    updateFilePreview();
                };
                fileItem.appendChild(removeBtn);
                
                preview.appendChild(fileItem);
            });
        }
        
        // Form submission with offline support
        document.getElementById('reportForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const loadingOverlay = document.getElementById('loadingOverlay');
            const messageBox = document.getElementById('messageBox');
            const submitBtn = document.querySelector('.submit-btn');
            
            // Show loading
            loadingOverlay.classList.add('show');
            submitBtn.disabled = true;
            messageBox.style.display = 'none';
            
            // Create FormData
            const formData = new FormData(this);
            formData.append('anonymous', '1');
            formData.append('send_emails', '0'); // Default to no emails for mobile
            
            // Add selected files
            selectedFiles.forEach(file => {
                formData.append('attachments[]', file);
            });
            
            // Check connection only when form is submitted (not continuously)
            let isConnected = false;
            if (syncManager && navigator.onLine) {
                try {
                    isConnected = await syncManager.isIntranetConnected();
                } catch (error) {
                    console.log('Connection check failed, will save offline:', error);
                    isConnected = false;
                }
            }
            
            if (isConnected && navigator.onLine) {
                // Try to submit online
                try {
                    const response = await fetch('php/submit_anonymous_event.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    loadingOverlay.classList.remove('show');
                    submitBtn.disabled = false;
                    
                    if (result.success) {
                        messageBox.className = 'message-box success';
                        messageBox.innerHTML = '<i class="fas fa-check-circle"></i> ' + (result.message || 'Report submitted successfully!');
                        messageBox.style.display = 'block';
                        
                        // Reset form
                        document.getElementById('reportForm').reset();
                        selectedFiles = [];
                        updateFilePreview();
                        document.getElementById('secondaryCategory').disabled = true;
                        
                        // Scroll to top
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                        
                        // Re-acquire GPS for next report (only if not already acquiring)
                        setTimeout(() => {
                            if (!gpsAcquiring) {
                                acquireGPS();
                            }
                        }, 2000);
                    } else {
                        // Server error - save offline as fallback
                        await saveOffline(formData, selectedFiles);
                    }
                } catch (error) {
                    // Network error - save offline
                    console.log('Network error, saving offline:', error);
                    await saveOffline(formData, selectedFiles);
                }
            } else {
                // Offline - save to local storage
                await saveOffline(formData, selectedFiles);
            }
        });
        
        // Save event offline
        async function saveOffline(formData, files) {
            try {
                // Check storage quota before saving
                const estimate = await offlineStorage.getStorageEstimate();
                if (estimate && estimate.usagePercent > 90) {
                    throw new Error('Storage almost full. Please sync existing events or clear storage.');
                }
                
                const eventId = await offlineStorage.saveEvent(formData, files);
                
                loadingOverlay.classList.remove('show');
                submitBtn.disabled = false;
                
                // Show success message
                messageBox.className = 'message-box success';
                messageBox.innerHTML = '<i class="fas fa-save"></i> Event saved offline. It will be synced automatically when you reconnect to the network.';
                messageBox.style.display = 'block';
                
                // Update pending count
                await updatePendingCount();
                
                // Reset form
                document.getElementById('reportForm').reset();
                selectedFiles = [];
                updateFilePreview();
                document.getElementById('secondaryCategory').disabled = true;
                
                // Scroll to top
                window.scrollTo({ top: 0, behavior: 'smooth' });
                
                // Re-acquire GPS for next report
                setTimeout(acquireGPS, 1000);
                
                console.log('Event saved offline:', eventId);
            } catch (error) {
                console.error('Error saving offline:', error);
                loadingOverlay.classList.remove('show');
                submitBtn.disabled = false;
                
                let errorMessage = 'Error saving event offline: ' + error.message;
                
                // Provide helpful error messages
                if (error.message.includes('QuotaExceededError') || error.message.includes('quota')) {
                    errorMessage = 'Storage full. Please sync existing events or remove some attachments.';
                } else if (error.message.includes('Storage almost full')) {
                    errorMessage = error.message;
                }
                
                messageBox.className = 'message-box error';
                messageBox.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + errorMessage;
                messageBox.style.display = 'block';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }
        
        // Set current date/time
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        document.getElementById('eventDate').value = now.toISOString().slice(0, 16);
        
        // Acquire GPS on page load (non-blocking)
        window.addEventListener('load', function() {
            // Delay GPS acquisition to prevent blocking
            setTimeout(() => {
                try {
                    acquireGPS();
                } catch (error) {
                    console.error('Error acquiring GPS:', error);
                }
            }, 1000);
        });
        
        // Register Service Worker (non-blocking)
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                // Delay service worker registration to prevent blocking
                setTimeout(() => {
                    try {
                        navigator.serviceWorker.register('service-worker.js')
                            .then(reg => {
                                console.log('Service Worker registered:', reg.scope);
                                
                                // Check for background sync support
                                if ('sync' in reg) {
                                    console.log('Background sync API supported');
                                } else {
                                    console.log('Background sync API not supported, using manual sync');
                                }
                            })
                            .catch(err => {
                                // Don't block page if service worker fails
                                console.warn('Service Worker registration failed (non-critical):', err.message);
                            });
                    } catch (error) {
                        console.warn('Service Worker registration error (non-critical):', error);
                    }
                }, 2000);
            });
        }
        
        // Listen for service worker messages (non-blocking)
        if ('serviceWorker' in navigator) {
            try {
                navigator.serviceWorker.addEventListener('message', function(event) {
                    if (event.data && event.data.type === 'SYNC_EVENTS') {
                        console.log('Service worker requested sync');
                        if (syncManager) {
                            try {
                                try {
                                    syncManager.syncAll();
                                } catch (error) {
                                    // Ignore sync errors
                                }
                            } catch (error) {
                                console.error('Error syncing from service worker:', error);
                            }
                        }
                    }
                });
            } catch (error) {
                console.warn('Service worker message listener error (non-critical):', error);
            }
        }

        // Reset App Button Logic
        document.getElementById('resetAppBtn').addEventListener('click', async function(e) {
            e.preventDefault();
            if (confirm('This will reset the app, clear cached data, and try to fix installation issues. Your saved reports will NOT be deleted. Continue?')) {
                // 1. Clear installation dismissal
                localStorage.removeItem('installPromptDismissed');
                
                // 2. Unregister Service Workers
                if ('serviceWorker' in navigator) {
                    const registrations = await navigator.serviceWorker.getRegistrations();
                    for (let registration of registrations) {
                        await registration.unregister();
                    }
                }
                
                // 3. Reload cleanly
                window.location.reload(true);
            }
        });
    </script>
</body>
</html>
