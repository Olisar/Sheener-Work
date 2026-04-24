# 🚀 ngrok Installation Guide - Step by Step

## Current Status: Download Page Open

The ngrok download page is now open in your browser. Follow these steps to complete the installation:

---

## 📥 Step 1: Download ngrok

### Option A: From the Browser (Easiest)
1. Look at the browser window that just opened
2. Click the blue **"Download for Windows (64-bit)"** button
3. Save the file to your Downloads folder
4. The file will be named: `ngrok-v3-stable-windows-amd64.zip`

### Option B: Direct Link
If the browser didn't open, use this link:
https://bin.equinox.io/c/bNyj1mQVY4c/ngrok-v3-stable-windows-amd64.zip

---

## 📂 Step 2: Extract ngrok

1. Go to your **Downloads** folder
2. Find `ngrok-v3-stable-windows-amd64.zip`
3. **Right-click** → **Extract All...**
4. Extract to: `C:\ngrok\`
   - Or any folder you prefer
   - Remember this location!

After extraction, you should have:
- `C:\ngrok\ngrok.exe`

---

## 🔑 Step 3: Sign Up for ngrok (Free)

1. Go to: https://dashboard.ngrok.com/signup
2. Sign up with:
   - Email
   - Google account
   - GitHub account
3. Verify your email (if required)
4. You'll be redirected to the dashboard

---

## 🎫 Step 4: Get Your Auth Token

1. After signing in, you'll see: https://dashboard.ngrok.com/get-started/your-authtoken
2. Copy your authtoken (it looks like: `2abc...xyz`)
3. Keep this handy for the next step

---

## ⚙️ Step 5: Configure ngrok

Open PowerShell and run:

```powershell
# Navigate to where you extracted ngrok
cd C:\ngrok

# Add your authtoken (replace YOUR_TOKEN with your actual token)
.\ngrok.exe config add-authtoken YOUR_TOKEN_HERE
```

You should see:
```
Authtoken saved to configuration file: C:\Users\YourName\.ngrok2\ngrok.yml
```

---

## 🚀 Step 6: Start ngrok Tunnel

Still in PowerShell, run:

```powershell
.\ngrok.exe http 80
```

You'll see output like:
```
ngrok

Session Status                online
Account                       your@email.com
Version                       3.x.x
Region                        United States (us)
Latency                       -
Web Interface                 http://127.0.0.1:4040
Forwarding                    https://abc123.ngrok-free.app -> http://localhost:80

Connections                   ttl     opn     rt1     rt5     p50     p90
                              0       0       0.00    0.00    0.00    0.00
```

**IMPORTANT:** Copy the HTTPS URL from the "Forwarding" line!
Example: `https://abc123.ngrok-free.app`

---

## 📱 Step 7: Generate QR Code with HTTPS

1. Open: http://localhost/sheener/qr_generator.php
2. In the form, paste your ngrok HTTPS URL
3. Click **"Generate QR Code"**
4. You should see:
   - 🔒 HTTPS URL displayed
   - Green "HTTPS Enabled!" message
   - New QR code generated

---

## 📲 Step 8: Test on Mobile

1. **Scan the QR code** with your phone's camera
2. Tap the notification to open the link
3. The form should load
4. **Look for the "Install" option!**
   - Android: Menu (⋮) → "Install app"
   - iOS: Share button → "Add to Home Screen"
5. Install the app
6. Test offline functionality!

---

## ✅ Success Indicators

You'll know it's working when:
- ✅ ngrok shows "Session Status: online"
- ✅ QR generator shows green "HTTPS Enabled!" message
- ✅ Phone can open the HTTPS URL
- ✅ **"Install app" option appears on mobile**
- ✅ App works offline after installation

---

## 🔧 Troubleshooting

### "ngrok not found"
- Make sure you're in the correct directory (`cd C:\ngrok`)
- Or use the full path: `C:\ngrok\ngrok.exe http 80`

### "Invalid authtoken"
- Double-check you copied the full token
- Make sure there are no extra spaces
- Get a new token from: https://dashboard.ngrok.com/get-started/your-authtoken

### "Port 80 already in use"
- Make sure XAMPP Apache is running
- If you get this error, Apache might not be on port 80
- Check XAMPP config for the actual port

### Phone can't access the URL
- Make sure ngrok is still running (don't close the window)
- Try the URL in your phone's browser first
- Check that you copied the full HTTPS URL

### Install option not showing
- Verify the URL starts with `https://`
- Check the QR generator shows green "HTTPS Enabled!" message
- Clear browser cache on phone
- Try in Chrome or Safari

---

## 💡 Quick Commands Reference

```powershell
# Navigate to ngrok folder
cd C:\ngrok

# Configure authtoken (one-time)
.\ngrok.exe config add-authtoken YOUR_TOKEN

# Start HTTPS tunnel
.\ngrok.exe http 80

# Stop ngrok
# Press Ctrl+C in the ngrok window
```

---

## 📝 Important Notes

1. **Keep ngrok running** - Don't close the PowerShell window
2. **URL changes** - Free tier gives you a new URL each time
3. **Regenerate QR** - Create a new QR code after restarting ngrok
4. **Free tier limits** - Should be fine for testing/internal use
5. **Paid option** - Get permanent URLs with paid account

---

## 🎯 Current Step: Download ngrok

**Action Required:** Click the download button in the browser window

**Next:** Extract to `C:\ngrok\` and continue with Step 3

---

**Ready?** Let me know when you've downloaded ngrok and I'll help you with the next steps!
