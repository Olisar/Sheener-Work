# 🚀 SHEEner PWA - Quick Start Guide

## ✅ What's Been Implemented

### 1. PWA Files Created
- ✅ `manifest.json` - App configuration
- ✅ `service-worker.js` - Offline support & caching
- ✅ `qr_generator.html` - QR code generator tool
- ✅ `PWA_SETUP_GUIDE.md` - Detailed documentation

### 2. Enhanced Features in index.php
- ✅ PWA meta tags for iOS and Android
- ✅ Service Worker registration
- ✅ **GPS Geolocation** - Auto-captures location when modal opens
- ✅ **Camera Integration** - Direct camera access on mobile
- ✅ Location status indicator with accuracy

---

## 🎯 Next Steps (What You Need to Do)

### Step 1: Install Cloudflare Tunnel (5 minutes)

**Download cloudflared:**
- Windows: https://github.com/cloudflare/cloudflared/releases
- Download `cloudflared-windows-amd64.exe`
- Rename to `cloudflared.exe`

**Quick Test (No Account Needed):**
```bash
cloudflared tunnel --url http://localhost/sheener/index.php
```

You'll get a URL like: `https://random-name.trycloudflare.com`

### Step 2: Generate QR Code (2 minutes)

1. Open: `http://localhost/sheener/qr_generator.html`
2. Paste your Cloudflare Tunnel URL
3. Click "Generate QR Code"
4. Click "Print QR Code"
5. Place printed QR code in your facility

### Step 3: Test on Mobile (3 minutes)

1. **Scan the QR code** with your phone
2. **Allow Location Access** when prompted
3. **Test Camera**: Tap "Attachments" → Should open camera
4. **Check GPS**: Should show "Location locked: [coordinates]"

### Step 4: Install as App (Optional)

**iOS (Safari):**
- Tap Share button → "Add to Home Screen"

**Android (Chrome):**
- Tap menu (⋮) → "Add to Home Screen"

---

## 📱 New Features for Users

### 🎥 Camera Access
- File input now opens camera directly on mobile
- Uses rear camera by default
- Still allows photo library selection

### 📍 GPS Location
- Automatically captures GPS when modal opens
- Shows coordinates with accuracy (e.g., "±15m")
- Stored in hidden field: `gps_coordinates`
- Format: `latitude,longitude` (e.g., "40.712776,-74.005974")

### 📲 App-Like Experience
- Installs to home screen
- Runs in fullscreen (no browser UI)
- Works offline (cached resources)
- Fast loading with service worker

---

## 🔧 Backend Integration Needed

Update `php/submit_anonymous_event.php` to handle GPS:

```php
// Get GPS coordinates
$gps_coordinates = isset($_POST['gps_coordinates']) ? trim($_POST['gps_coordinates']) : null;

// Parse coordinates
$latitude = null;
$longitude = null;
if ($gps_coordinates && strpos($gps_coordinates, ',') !== false) {
    list($latitude, $longitude) = explode(',', $gps_coordinates);
    $latitude = floatval($latitude);
    $longitude = floatval($longitude);
}

// Add to database
// UPDATE your INSERT query to include latitude and longitude columns
```

**Database Schema Update:**
```sql
ALTER TABLE events ADD COLUMN latitude DECIMAL(10, 8) NULL;
ALTER TABLE events ADD COLUMN longitude DECIMAL(11, 8) NULL;
ALTER TABLE events ADD COLUMN gps_accuracy DECIMAL(10, 2) NULL;
```

---

## 🧪 Testing Checklist

- [ ] Cloudflare Tunnel running and accessible
- [ ] QR code generated and printed
- [ ] Mobile can scan QR code and open app
- [ ] GPS location acquired successfully
- [ ] Camera opens when tapping attachments
- [ ] Form submits with GPS coordinates
- [ ] App can be installed to home screen
- [ ] App works in standalone mode

---

## 🔒 Security Features

✅ **HTTPS Required** - Cloudflare Tunnel provides automatic HTTPS
✅ **Secure Geolocation** - Only works over HTTPS
✅ **Camera Permissions** - User must explicitly grant access
✅ **No Firewall Changes** - Tunnel bypasses firewall safely
✅ **Anonymous Reporting** - No login required

---

## 📊 How It Works

```
Visitor WiFi → QR Code Scan → Cloudflare Tunnel → Your Server → Database
                    ↓
            Opens PWA in Browser
                    ↓
            Requests GPS & Camera
                    ↓
            User Fills Form
                    ↓
            Submits with GPS coords
```

---

## 🆘 Common Issues & Solutions

### "Service Worker registration failed"
- ✅ Must use HTTPS (Cloudflare Tunnel provides this)
- ✅ Check browser console for errors

### "Location permission denied"
- ✅ User must enable location in browser settings
- ✅ iOS: Settings → Safari → Location → Allow
- ✅ Android: Settings → Site Settings → Location → Allow

### "Camera not opening"
- ✅ Must be on HTTPS
- ✅ User must grant camera permission
- ✅ Test on actual mobile device (not desktop)

### "Tunnel URL not accessible"
- ✅ Ensure cloudflared is running
- ✅ Check XAMPP is running
- ✅ Verify URL is correct

---

## 📞 Support Commands

**Start Tunnel:**
```bash
cloudflared tunnel --url http://localhost/sheener/index.php
```

**Check if Service Worker is Active:**
Open browser console and type:
```javascript
navigator.serviceWorker.getRegistrations().then(r => console.log(r));
```

**Test GPS in Console:**
```javascript
navigator.geolocation.getCurrentPosition(
    pos => console.log(pos.coords),
    err => console.error(err)
);
```

---

## 🎉 Benefits Summary

| Feature | Before | After |
|---------|--------|-------|
| **Network Access** | Internal only | Visitor WiFi ✅ |
| **Installation** | N/A | Add to Home Screen ✅ |
| **Camera** | File upload only | Direct camera access ✅ |
| **Location** | Manual entry | Auto GPS capture ✅ |
| **Offline** | Requires connection | Cached & works offline ✅ |
| **Updates** | N/A | Instant (update server) ✅ |
| **Cost** | N/A | Free (Cloudflare) ✅ |

---

## 📚 Files Modified/Created

**Created:**
- `manifest.json` - PWA configuration
- `service-worker.js` - Offline caching
- `qr_generator.html` - QR code tool
- `PWA_SETUP_GUIDE.md` - Full documentation
- `QUICK_START.md` - This file

**Modified:**
- `index.php` - Added PWA support, GPS, camera integration

---

## 🚀 Production Deployment

For permanent production use:

1. **Create Named Tunnel:**
   ```bash
   cloudflared tunnel login
   cloudflared tunnel create sheener-reporter
   ```

2. **Configure DNS:**
   ```bash
   cloudflared tunnel route dns sheener-reporter reporter.yourdomain.com
   ```

3. **Run as Service:**
   ```bash
   cloudflared service install
   ```

4. **Update QR Codes** with permanent URL

---

**Ready to go! 🎊**

Start with Step 1 above and you'll be live in under 10 minutes!
