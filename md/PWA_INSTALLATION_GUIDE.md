# PWA Installation & Offline Usage Guide

## How It Works

### 1. Scanning the QR Code
- When you scan the QR code, it opens the mobile reporting app in your browser
- The app is **NOT automatically installed** - you need to install it as a PWA (one simple step)

### 2. Installing as a Native-Like App

#### On Android (Chrome):
1. After scanning QR code, an "Install" banner will appear at the top
2. Tap "Install" button
3. Or manually: Tap the menu (⋮) → "Add to Home Screen" or "Install App"
4. The app icon appears on your home screen
5. Tap the icon to open - it runs like a native app (no browser UI)

#### On iOS (Safari):
1. After scanning QR code, tap the Share button (square with arrow up)
2. Scroll down and tap "Add to Home Screen"
3. Tap "Add" in the top right
4. The app icon appears on your home screen
5. Tap the icon to open - it runs like a native app (no browser UI)

### 3. Using the App Offline (No WiFi Required)

**YES - The app works completely offline once installed!**

#### How Offline Mode Works:
1. **Install the app** (as described above)
2. **Open the app** from your home screen
3. **Fill out the form** - works exactly the same offline
4. **Add photos/attachments** - stored on your phone
5. **Submit the event** - saved locally on your phone
6. **See confirmation**: "Event saved offline - will sync when connected"

#### What Happens When You Reconnect to WiFi:
- The app **automatically detects** when you reconnect to the intranet WiFi
- All pending events **sync automatically** in the background
- You'll see a notification: "X events synced successfully"
- No action required - it happens automatically!

### 4. Key Features

#### ✅ Works Without WiFi
- Fill out forms offline
- Take photos offline
- Submit events offline
- All data stored on your phone

#### ✅ Automatic Sync
- Detects when WiFi connection is restored
- Syncs all pending events automatically
- Shows sync progress and status
- Retries failed syncs automatically

#### ✅ Native App Experience
- Once installed, opens like a native app
- No browser address bar
- Fullscreen experience
- Fast loading (cached)

#### ✅ Data Stored Locally
- Events stored in phone's IndexedDB
- Photos stored as Blobs
- Safe and secure
- Cleared after successful sync

## Installation Status

### Check if Already Installed:
- If you see a browser address bar → **Not installed** (install to get offline access)
- If it opens fullscreen with no address bar → **Already installed** (works offline)

### Re-installation:
- If you uninstall, just scan the QR code again and re-install
- All your pending events remain stored on your phone

## Troubleshooting

### App Not Installing:
- **Android**: Make sure you're using Chrome browser
- **iOS**: Make sure you're using Safari browser
- Try closing and reopening the browser
- Clear browser cache if needed

### Offline Mode Not Working:
- Make sure the app is **installed** (not just opened in browser)
- Check that service worker is registered (should happen automatically)
- Try uninstalling and re-installing

### Events Not Syncing:
- Make sure you're connected to the intranet WiFi (192.168.178.41)
- Check the status bar at bottom - should show "Connected"
- Wait a few seconds - sync happens automatically
- Check pending count - should decrease after sync

## Summary

**To Answer Your Questions:**

1. **Does it become native on the phone?**
   - **Yes**, after you install it (one tap after scanning QR code)
   - Once installed, it works like a native app

2. **Can it be used anytime with no WiFi?**
   - **Yes**, once installed, it works completely offline
   - Events are saved on your phone
   - Syncs automatically when WiFi is restored

**The app is designed to work offline-first!** Install it once, and you can use it anywhere, anytime - even without internet connection.
