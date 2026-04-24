# SHEEner Mobile Report - Setup Guide

## Current Issues Fixed

### 1. ✅ Manifest Icons
- Added proper PNG icons (192x192 and 512x512) required for PWA installation
- Icons are now located in `img/icons/`

### 2. ✅ QR Code Generator
- Created `qr_generator.php` that automatically detects your server's IP address
- Generates a QR code that mobile devices can scan
- No more localhost issues!

## How to Use

### Step 1: Access the QR Code Generator
1. Open your browser on your computer
2. Navigate to: `http://localhost/sheener/qr_generator.php`
3. You'll see a QR code and the mobile URL

### Step 2: Scan with Your Phone
1. Make sure your phone is on the **same WiFi network** as your computer
2. Open your phone's camera app
3. Point it at the QR code
4. Tap the notification to open the link

### Step 3: Test the Form
- The form should now load on your phone
- You can fill it out and submit reports
- GPS location will be captured automatically

## Known Limitations (HTTP vs HTTPS)

### What Works with HTTP (Current Setup):
- ✅ Form loads and works
- ✅ Can submit reports
- ✅ GPS location capture
- ✅ Camera/photo upload
- ❌ **PWA Install option will NOT appear on mobile** (requires HTTPS)
- ❌ **Service Worker will NOT register on mobile** (requires HTTPS)
- ❌ **Offline functionality will NOT work on mobile** (requires HTTPS)

### To Enable Full PWA Features (Install + Offline):

You need HTTPS. Here are your options:

#### Option 1: Self-Signed Certificate (Quick, for internal use)
```bash
# In XAMPP, enable SSL and create a self-signed certificate
# Users will see a security warning but can proceed
```

#### Option 2: ngrok (Easiest for testing)
```bash
# Download ngrok from https://ngrok.com/
# Run: ngrok http 80
# Use the https URL it provides
```

#### Option 3: Let's Encrypt (Best for production)
```bash
# Requires a domain name
# Free SSL certificate
# No security warnings
```

## Troubleshooting

### Phone Can't Connect
- **Check WiFi**: Ensure phone and computer are on the same network
- **Check Firewall**: Windows Firewall might be blocking connections
  - Go to Windows Defender Firewall → Allow an app
  - Allow Apache HTTP Server

### Install Option Not Showing
- **This is expected with HTTP!** 
- PWA installation requires HTTPS on mobile devices
- The form will still work, just won't have the "install" option

### GPS Not Working
- **Allow location permissions** when prompted
- Some browsers require HTTPS for GPS on mobile

### Form Submissions Failing
- Check that `php/submit_anonymous_event.php` exists
- Check XAMPP Apache and MySQL are running
- Check browser console for errors

## Testing Checklist

- [ ] QR code generator shows your correct IP address
- [ ] Phone can scan QR code and open the page
- [ ] Form loads on mobile browser
- [ ] Can fill out all fields
- [ ] Can take/upload photos
- [ ] GPS location is captured
- [ ] Form submits successfully
- [ ] Receives confirmation message

## Next Steps (Optional)

If you want the full PWA experience with offline support:
1. Set up HTTPS (see options above)
2. Update `qr_generator.php` to use `https://` instead of `http://`
3. Regenerate the QR code
4. Scan with phone - install option should now appear!

## Files Modified/Created

- `manifest.json` - Updated with proper PNG icons
- `img/icons/icon-192x192.png` - New icon
- `img/icons/icon-512x512.png` - New icon
- `qr_generator.php` - New QR code generator page
- `MOBILE_SETUP_GUIDE.md` - This file

## Support

If you encounter issues:
1. Check the browser console (F12) for errors
2. Verify your IP address is correct in the QR code
3. Ensure XAMPP is running
4. Check that your phone and computer are on the same network
