<?php /* File: sheener/qr_generator.php */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SHEEner - Mobile QR Code</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #0A2F64 0%, #1a4d8f 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 700px;
            width: 100%;
            text-align: center;
        }
        
        h1 {
            color: #0A2F64;
            margin-bottom: 10px;
            font-size: 2rem;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1rem;
        }
        
        .url-form {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            border: 2px solid #dee2e6;
        }
        
        .url-form h3 {
            color: #0A2F64;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }
        
        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: 'Courier New', monospace;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #3685fe;
        }
        
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 0.85rem;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #3685fe 0%, #276ac8 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 1rem;
            transition: transform 0.2s;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
        }
        
        .qr-container {
            background: #f8f9fa;
            border-radius: 16px;
            padding: 30px;
            margin: 30px 0;
            border: 3px solid #0A2F64;
        }
        
        #qrcode {
            display: inline-block;
            padding: 20px;
            background: white;
            border-radius: 12px;
        }
        
        .url-display {
            background: #e8f5e9;
            border: 2px solid #4CAF50;
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            word-break: break-all;
            font-family: 'Courier New', monospace;
            font-size: 0.95rem;
            color: #2e7d32;
        }
        
        .url-display.https {
            background: #e3f2fd;
            border-color: #2196F3;
            color: #1565c0;
        }
        
        .instructions {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
            border-radius: 8px;
        }
        
        .instructions h3 {
            color: #e65100;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }
        
        .instructions ol {
            margin-left: 20px;
            line-height: 1.8;
            color: #555;
        }
        
        .instructions li {
            margin-bottom: 10px;
        }
        
        .warning {
            background: #ffebee;
            border: 2px solid #e74c3c;
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            color: #c62828;
            font-weight: 600;
        }
        
        .warning i {
            margin-right: 8px;
        }
        
        .success-box {
            background: #e8f5e9;
            border: 2px solid #4CAF50;
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            color: #2e7d32;
            font-weight: 600;
        }
        
        .success-box i {
            margin-right: 8px;
        }
        
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
            text-align: left;
            border-radius: 8px;
            color: #1565c0;
            line-height: 1.6;
        }
        
        .btn {
            display: inline-block;
            padding: 15px 30px;
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            margin: 10px;
            transition: transform 0.2s;
            border: none;
            cursor: pointer;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #3685fe 0%, #276ac8 100%);
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-qrcode"></i> Mobile Access QR Code</h1>
        <p class="subtitle">Scan this code with your phone to access the mobile report form</p>
        
        <?php
        // Check if custom URL is provided
        $customURL = isset($_POST['custom_url']) ? trim($_POST['custom_url']) : '';
        $urlSource = '';
        $isHTTPS = false;
        
        if (!empty($customURL)) {
            // Use custom URL (e.g., ngrok URL)
            $mobileURL = $customURL;
            if (!str_ends_with($mobileURL, '/sheener/mobile_report.php')) {
                if (!str_ends_with($mobileURL, '/')) {
                    $mobileURL .= '/';
                }
                $mobileURL .= 'sheener/mobile_report.php';
            }
            $urlSource = "Custom URL (ngrok or HTTPS)";
            $isHTTPS = str_starts_with($mobileURL, 'https://');
        } else {
            // Get server IP address
            $serverIP = $_SERVER['SERVER_ADDR'];
            
            // Alternative method if SERVER_ADDR is not available
            if (empty($serverIP) || $serverIP == '::1' || $serverIP == '127.0.0.1') {
                // Try to get the actual network IP
                $serverIP = gethostbyname(gethostname());
            }
            
            // Construct the URL
            $protocol = 'http';
            $port = $_SERVER['SERVER_PORT'];
            $portString = ($port != 80 && $port != 443) ? ":$port" : "";
            $mobileURL = "$protocol://$serverIP$portString/sheener/mobile_report.php";
            $urlSource = "Local Network IP";
            $isHTTPS = false;
        }
        ?>
        
        <!-- URL Input Form -->
        <div class="url-form">
            <h3><i class="fas fa-link"></i> Use ngrok HTTPS URL (Optional)</h3>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="custom_url">Enter your ngrok HTTPS URL:</label>
                    <input 
                        type="text" 
                        id="custom_url" 
                        name="custom_url" 
                        placeholder="https://abc123.ngrok-free.app"
                        value="<?php echo htmlspecialchars($customURL); ?>"
                    >
                    <small>Leave empty to use local IP address (<?php echo $_SERVER['SERVER_ADDR']; ?>)</small>
                </div>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-sync-alt"></i> Generate QR Code
                </button>
            </form>
        </div>
        
        <?php if ($isHTTPS): ?>
        <div class="success-box">
            <i class="fas fa-check-circle"></i>
            <strong>HTTPS Enabled!</strong> PWA installation and offline features will work on mobile devices.
        </div>
        <?php else: ?>
        <div class="warning">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>HTTP Mode:</strong> Install option won't appear on mobile. Use ngrok for full PWA features.
        </div>
        <?php endif; ?>
        
        <div class="url-display <?php echo $isHTTPS ? 'https' : ''; ?>">
            <strong><?php echo $isHTTPS ? '🔒 HTTPS' : '🔓 HTTP'; ?> URL (<?php echo $urlSource; ?>):</strong><br>
            <?php echo htmlspecialchars($mobileURL); ?>
        </div>
        
        <div class="qr-container">
            <div id="qrcode"></div>
        </div>
        
        <?php if (!$isHTTPS): ?>
        <div class="info-box">
            <strong><i class="fas fa-info-circle"></i> Want HTTPS?</strong><br>
            Run the setup script to install ngrok:
            <ul style="margin-top: 10px; margin-left: 20px;">
                <li>Open PowerShell in the sheener folder</li>
                <li>Run: <code>.\setup_ngrok.ps1</code></li>
                <li>Follow the instructions to get your HTTPS URL</li>
                <li>Paste the URL above and regenerate the QR code</li>
            </ul>
        </div>
        <?php endif; ?>
        
        <div class="instructions">
            <h3><i class="fas fa-mobile-alt"></i> How to Use:</h3>
            <ol>
                <?php if (!$isHTTPS): ?>
                <li><strong>Ensure your phone is on the same WiFi network</strong> as this computer</li>
                <?php endif; ?>
                <li><strong>Open your phone's camera app</strong> and point it at the QR code above</li>
                <li><strong>Tap the notification</strong> that appears to open the link</li>
                <li>The mobile report form will open in your browser</li>
                <?php if ($isHTTPS): ?>
                <li><strong>Install as an app:</strong>
                    <ul style="margin-top: 8px;">
                        <li><strong>Android:</strong> Tap the menu (⋮) → "Install app" or "Add to Home screen"</li>
                        <li><strong>iOS:</strong> Tap Share (<i class="fas fa-share"></i>) → "Add to Home Screen"</li>
                    </ul>
                </li>
                <?php endif; ?>
            </ol>
        </div>
        
        <div style="margin-top: 30px;">
            <a href="mobile_report.php" class="btn">
                <i class="fas fa-desktop"></i> Open on This Computer
            </a>
            <a href="javascript:window.print()" class="btn btn-secondary">
                <i class="fas fa-print"></i> Print QR Code
            </a>
        </div>
    </div>
    
    <!-- QR Code Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        // Generate QR code
        const mobileURL = "<?php echo $mobileURL; ?>";
        new QRCode(document.getElementById("qrcode"), {
            text: mobileURL,
            width: 256,
            height: 256,
            colorDark: "#0A2F64",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
    </script>
</body>
</html>
