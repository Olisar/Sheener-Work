# SHEEner PWA Setup Guide

## Overview
This guide will help you set up the SHEEner Reporter as a Progressive Web App (PWA) accessible via Cloudflare Tunnel, allowing visitors on WiFi to report incidents without needing to be on the internal network.

---

## Phase 1: Set Up Cloudflare Tunnel (Network Bridge)

### What is Cloudflare Tunnel?
Cloudflare Tunnel creates a secure connection between your localhost server and the internet without opening firewall ports. Visitors on WiFi can access your reporting page through a public URL.

### Step 1: Install Cloudflare Tunnel (cloudflared)

#### For Windows:
1. Download cloudflared from: https://github.com/cloudflare/cloudflared/releases
2. Download the Windows executable: `cloudflared-windows-amd64.exe`
3. Rename it to `cloudflared.exe`
4. Move it to a permanent location (e.g., `C:\Program Files\cloudflared\`)
5. Add to PATH or use full path when running commands

### Step 2: Quick Start (No Account Required)

For testing, you can use Cloudflare's quick tunnel feature:

```bash
cloudflared tunnel --url http://localhost/sheener/index.php
```

This will generate a temporary public URL like:
```
https://random-name.trycloudflare.com
```

**Note:** This URL changes each time you restart the tunnel. For a permanent solution, see Step 3.

### Step 3: Permanent Tunnel Setup (Recommended for Production)

1. **Login to Cloudflare:**
   ```bash
   cloudflared tunnel login
   ```
   This opens a browser window to authenticate.

2. **Create a Named Tunnel:**
   ```bash
   cloudflared tunnel create sheener-reporter
   ```
   This creates a tunnel and saves credentials.

3. **Create a Configuration File:**
   Create `config.yml` in `C:\Users\YourUsername\.cloudflared\` (Windows) or `~/.cloudflared/` (Linux/Mac):

   ```yaml
   tunnel: sheener-reporter
   credentials-file: C:\Users\YourUsername\.cloudflared\<TUNNEL-ID>.json
   
   ingress:
     - hostname: sheener-reporter.yourdomain.com
       service: http://localhost/sheener/index.php
     - service: http_status:404
   ```

4. **Route DNS:**
   ```bash
   cloudflared tunnel route dns sheener-reporter sheener-reporter.yourdomain.com
   ```

5. **Run the Tunnel:**
   ```bash
   cloudflared tunnel run sheener-reporter
   ```

### Step 4: Run Tunnel as a Service (Auto-Start)

#### Windows:
```bash
cloudflared service install
```

#### Linux:
```bash
sudo cloudflared service install
sudo systemctl start cloudflared
sudo systemctl enable cloudflared
```

---

## Phase 2: PWA Features (Already Implemented)

The following features have been added to your `index.php`:

### ✅ PWA Manifest (`manifest.json`)
- App name: "SHEEner Reporter"
- Standalone display mode (looks like a native app)
- Custom theme colors
- App icon configuration

### ✅ Service Worker (`service-worker.js`)
- Offline caching for faster load times
- Background sync capabilities
- App-like performance

### ✅ Camera Integration
- File input now triggers mobile camera directly
- `capture="environment"` attribute uses rear camera
- Supports both photos and document uploads

### ✅ GPS Geolocation
- Automatically acquires GPS coordinates when modal opens
- Shows real-time location status with accuracy
- Stores coordinates in hidden field for submission
- Graceful error handling for permission denials

---

## Phase 3: Generate QR Code for Visitors

### Option 1: Online QR Code Generator
1. Get your public tunnel URL (e.g., `https://sheener-reporter.trycloudflare.com`)
2. Visit: https://www.qr-code-generator.com/
3. Enter your URL
4. Download the QR code as PNG or PDF
5. Print and place in facility

### Option 2: Using Your Existing System
You already have QR code generation in your codebase. You can create a simple page:

Create `qr_generator.html`:
```html
<!DOCTYPE html>
<html>
<head>
    <title>QR Code Generator</title>
    <script src="https://cdn.jsdelivr.net/npm/qrcode/build/qrcode.min.js"></script>
</head>
<body>
    <h1>SHEEner Reporter QR Code</h1>
    <input type="text" id="url" placeholder="Enter your tunnel URL" style="width: 400px; padding: 10px;">
    <button onclick="generateQR()">Generate QR Code</button>
    <br><br>
    <canvas id="qrcode"></canvas>
    
    <script>
        function generateQR() {
            const url = document.getElementById('url').value;
            const canvas = document.getElementById('qrcode');
            QRCode.toCanvas(canvas, url, {
                width: 300,
                margin: 2
            }, function (error) {
                if (error) console.error(error);
                console.log('QR code generated!');
            });
        }
    </script>
</body>
</html>
```

---

## Phase 4: Testing the PWA

### On Mobile (iOS/Safari):
1. Open the tunnel URL in Safari
2. Tap the Share button
3. Scroll down and tap "Add to Home Screen"
4. The app icon appears on your home screen
5. Tap to open - it runs in standalone mode (no browser UI)

### On Mobile (Android/Chrome):
1. Open the tunnel URL in Chrome
2. Chrome will show "Add to Home Screen" prompt automatically
3. Or tap the menu (⋮) → "Add to Home Screen"
4. The app icon appears on your home screen
5. Tap to open - it runs in standalone mode

### Testing Camera:
1. Open the report modal
2. Tap on the "Attachments" field
3. Mobile should show options: "Take Photo" or "Choose from Library"
4. Select "Take Photo" - rear camera should open directly

### Testing GPS:
1. Open the report modal
2. Allow location permission when prompted
3. You should see: "Location locked: [coordinates] (±accuracy)"
4. The coordinates are automatically included in the submission

---

## Phase 5: Backend Update (Handle GPS Data)

Update your `php/submit_anonymous_event.php` to handle GPS coordinates:

```php
// Get GPS coordinates if provided
$gps_coordinates = isset($_POST['gps_coordinates']) ? trim($_POST['gps_coordinates']) : null;

// Parse coordinates
$latitude = null;
$longitude = null;
if ($gps_coordinates && strpos($gps_coordinates, ',') !== false) {
    list($latitude, $longitude) = explode(',', $gps_coordinates);
    $latitude = floatval($latitude);
    $longitude = floatval($longitude);
}

// Add to your database insert
// Example:
// $stmt = $pdo->prepare("INSERT INTO events (..., latitude, longitude) VALUES (..., ?, ?)");
// $stmt->execute([..., $latitude, $longitude]);
```

---

## Security Considerations

### 1. Restrict Access to Report Page Only
In your `config.yml`, you can add path-based routing:

```yaml
ingress:
  - hostname: sheener-reporter.yourdomain.com
    path: /sheener/index.php
    service: http://localhost/sheener/index.php
  - hostname: sheener-reporter.yourdomain.com
    path: /sheener/php/submit_anonymous_event.php
    service: http://localhost/sheener/php/submit_anonymous_event.php
  - service: http_status:403
```

### 2. Add Rate Limiting
Consider adding rate limiting to prevent spam submissions.

### 3. HTTPS Only
Cloudflare Tunnel automatically provides HTTPS, which is required for:
- GPS geolocation
- Camera access
- Service Worker registration

---

## Troubleshooting

### Issue: "Service Worker registration failed"
- **Solution:** Ensure you're accessing via HTTPS (Cloudflare Tunnel provides this)
- Service Workers require secure context (HTTPS or localhost)

### Issue: "Location permission denied"
- **Solution:** User must manually enable location in browser settings
- On iOS: Settings → Safari → Location → Allow
- On Android: Settings → Site Settings → Location → Allow

### Issue: Camera not opening
- **Solution:** Ensure you're on HTTPS and user has granted camera permission
- Test on actual mobile device (not desktop browser)

### Issue: Tunnel URL not accessible
- **Solution:** 
  - Check if cloudflared is running: `cloudflared tunnel info sheener-reporter`
  - Check firewall isn't blocking cloudflared
  - Verify XAMPP is running and accessible on localhost

---

## Quick Reference Commands

### Start Quick Tunnel (Testing):
```bash
cloudflared tunnel --url http://localhost/sheener/index.php
```

### Start Named Tunnel:
```bash
cloudflared tunnel run sheener-reporter
```

### Check Tunnel Status:
```bash
cloudflared tunnel info sheener-reporter
```

### Stop Tunnel Service:
```bash
# Windows
cloudflared service uninstall

# Linux
sudo systemctl stop cloudflared
```

---

## Next Steps

1. ✅ PWA files created (manifest.json, service-worker.js)
2. ✅ index.php updated with PWA support
3. ✅ Camera and GPS features implemented
4. ⏳ Install and configure Cloudflare Tunnel
5. ⏳ Generate and print QR codes
6. ⏳ Update backend to handle GPS coordinates
7. ⏳ Test on mobile devices
8. ⏳ Deploy to production

---

## Support Resources

- Cloudflare Tunnel Docs: https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/
- PWA Documentation: https://web.dev/progressive-web-apps/
- Geolocation API: https://developer.mozilla.org/en-US/docs/Web/API/Geolocation_API

---

**Questions or Issues?**
Contact your IT department for assistance with Cloudflare Tunnel setup and network configuration.
