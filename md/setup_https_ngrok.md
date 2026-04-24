# Setting Up HTTPS with ngrok for SHEEner Mobile

## What is ngrok?
ngrok creates a secure HTTPS tunnel to your local XAMPP server, allowing:
- ✅ PWA installation on mobile devices
- ✅ Service Worker registration
- ✅ Offline functionality
- ✅ Full GPS access
- ✅ No certificate configuration needed

## Step-by-Step Setup

### Step 1: Download ngrok
1. Go to https://ngrok.com/
2. Sign up for a free account (required)
3. Download ngrok for Windows
4. Extract the `ngrok.exe` file to a folder (e.g., `C:\ngrok\`)

### Step 2: Authenticate ngrok
1. Log in to your ngrok dashboard: https://dashboard.ngrok.com/
2. Copy your authtoken
3. Open PowerShell or Command Prompt
4. Run:
   ```bash
   C:\ngrok\ngrok.exe config add-authtoken YOUR_AUTH_TOKEN_HERE
   ```

### Step 3: Start ngrok Tunnel
1. Make sure XAMPP Apache is running
2. Open PowerShell or Command Prompt
3. Run:
   ```bash
   C:\ngrok\ngrok.exe http 80
   ```
4. You'll see output like:
   ```
   Forwarding  https://abc123.ngrok-free.app -> http://localhost:80
   ```

### Step 4: Get Your HTTPS URL
- Copy the HTTPS URL (e.g., `https://abc123.ngrok-free.app`)
- Your mobile form will be at: `https://abc123.ngrok-free.app/sheener/mobile_report.php`

### Step 5: Create QR Code with HTTPS URL
- We'll create an updated QR generator that uses your ngrok URL
- Or manually create a QR code pointing to your ngrok URL

## Important Notes

### ✅ Advantages:
- Instant HTTPS - no certificate setup
- Works from anywhere (not just local network)
- Free tier available
- Easy to use

### ⚠️ Limitations:
- Free tier URLs change each time you restart ngrok
- Free tier has bandwidth limits
- Shows ngrok warning page on first visit (can be dismissed)
- Requires ngrok to be running

### 💡 Tips:
- Keep the ngrok window open while testing
- Paid ngrok accounts get permanent URLs
- For production, consider a proper SSL certificate

## Alternative: XAMPP Self-Signed Certificate

If you prefer to keep everything local (same WiFi network only):

### Step 1: Enable SSL in XAMPP
1. Open XAMPP Control Panel
2. Click "Config" next to Apache
3. Select "httpd-ssl.conf"
4. Verify SSL is configured

### Step 2: Generate Certificate
1. Open XAMPP Control Panel
2. Click "Shell" button
3. Run:
   ```bash
   cd C:\xampp\apache
   makecert.bat
   ```

### Step 3: Update Virtual Hosts
1. Edit `C:\xampp\apache\conf\extra\httpd-vhosts.conf`
2. Add SSL virtual host for your IP
3. Restart Apache

### Step 4: Accept Certificate on Phone
- Navigate to `https://192.168.178.41/sheener/mobile_report.php`
- Accept the security warning
- Certificate will be trusted for that session

**Note:** Self-signed certificates show security warnings on every device.

## Recommended Approach

For your use case (internal company use), I recommend:

1. **For Testing/Demo:** Use ngrok (easiest, works immediately)
2. **For Production:** 
   - If you have a domain: Use Let's Encrypt (free, trusted)
   - If internal only: Use self-signed certificate
   - If budget allows: Purchase SSL certificate

## Next Steps

Choose your preferred method and I'll help you set it up!
