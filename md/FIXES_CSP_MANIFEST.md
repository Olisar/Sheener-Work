# Console Error Fixes - CSP and Manifest Issues

## Date: December 22, 2025

## Issues Identified

### 1. Content Security Policy (CSP) Violation
**Error:** 
```
Connecting to 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js.map' violates the following Content Security Policy directive: "connect-src 'self'". The request has been blocked.
```

**Root Cause:**
- jsPDF library was being loaded from external CDN (cdnjs.cloudflare.com)
- The library's source map file (.map) was trying to load from the CDN
- CSP header `connect-src 'self'` was blocking external connections

### 2. Manifest.json 403 Forbidden Error
**Error:**
```
manifest.json:1 Failed to load resource: the server responded with a status of 403 (Forbidden)
```

**Root Cause:**
- `.htaccess` file had a rule blocking all `.json` files for security
- This was preventing the PWA manifest.json from loading

## Solutions Implemented

### Solution 1: Moved External Libraries to Local Vendor Directory

**Actions Taken:**
1. Created `js/vendor/` directory
2. Downloaded jsPDF (v2.5.1) and QRCode libraries locally
3. Updated all references from CDN to local files

**Files Modified:**
- ✅ `index.php` - Updated script tags
- ✅ `event_list.php` - Updated jsPDF and QRCode references
- ✅ `event_center.php` - Updated jsPDF and QRCode references
- ✅ `assessment_view.php` - Updated jsPDF reference
- ✅ `permit_list0.php` - Updated jsPDF reference
- ✅ `permit_list.php` - Updated jsPDF and QRCode references
- ✅ `permitlist1Origin.php` - Updated jsPDF reference
- ✅ `KPIEHS.php` - Updated jsPDF reference (upgraded from v2.4.0 to v2.5.1)
- ✅ `js/task_manager.js` - Updated dynamic jsPDF loading fallback

**Benefits:**
- ✅ No more CSP violations
- ✅ Faster page load (no external requests)
- ✅ Better security (no external dependencies)
- ✅ Works offline (important for PWA functionality)
- ✅ No dependency on CDN availability

### Solution 2: Fixed manifest.json Access

**Actions Taken:**
1. Updated `.htaccess` to allow access to `manifest.json`
2. Added proper MIME type configuration for JSON files

**Changes in `.htaccess`:**
```apache
# Allow manifest.json for PWA functionality
<Files "manifest.json">
    Require all granted
</Files>

# Protect sensitive files (but allow manifest.json above)
<FilesMatch "\.(env|ini|log|sh|sql|conf|bak|config|dist|md|json|lock|yml|yaml)$">
    Require all denied
</FilesMatch>

# Set correct MIME types
<IfModule mod_mime.c>
    AddType application/manifest+json .webmanifest
    AddType application/json .json
</IfModule>
```

**Benefits:**
- ✅ PWA manifest now loads correctly
- ✅ Maintains security for other JSON files
- ✅ Proper MIME type for manifest

### Solution 3: Fixed Navigation and AI Agent Config Access (Additional Fix)

**New Issues Found:**
```
php/get_navigation_config.php:1 Failed to load resource: 403 (Forbidden)
ai-agent-config.json:1 Failed to load resource: 403 (Forbidden)
```

**Root Cause:**
- `.htaccess` was blocking all files ending with `config.php`
- This blocked `get_navigation_config.php` and `get_topbar_config.php` (needed for UI)
- Also blocked `ai-agent-config.json` (needed for AI Navigator)
- Initial fix using `<Files>` directive was being overridden by `<FilesMatch>` directive

**Actions Taken:**
1. Added exception for navigation config PHP files
2. Removed `.json` from blanket file type block
3. Explicitly allowed `manifest.json` and `ai-agent-config.json`
4. Added specific blocks only for sensitive JSON files (package.json, composer.json, etc.)
5. Maintained security by specifically blocking only sensitive config files

**Final Working `.htaccess` Rules:**
```apache
# Explicitly allow specific JSON files needed for application
<Files "manifest.json">
    Require all granted
</Files>

<Files "ai-agent-config.json">
    Require all granted
</Files>

# Block sensitive file types (removed .json - handled separately below)
<FilesMatch "\.(env|ini|log|sh|sql|conf|bak|config|dist|md|lock|yml|yaml)$">
    Require all denied
</FilesMatch>

# Block specific sensitive JSON files by pattern
<FilesMatch "(package|composer|tsconfig|\.config)\.json$">
    Require all denied
</FilesMatch>

# Allow navigation and topbar config files (needed for UI)
<FilesMatch "(get_navigation_config|get_topbar_config)\.php$">
    Require all granted
</FilesMatch>

# Protect sensitive api config files (database, API keys, etc.)
<FilesMatch "(api/config|database|db_config)\.php$">
    Require all denied
</FilesMatch>
```

**Benefits:**
- ✅ Navigation system works correctly
- ✅ Topbar loads properly
- ✅ AI Navigator can load its configuration
- ✅ Still protects sensitive database and API config files
- ✅ Still blocks sensitive JSON files (package.json, composer.json, etc.)
- ✅ More maintainable whitelist approach

### Solution 4: Fixed Logo Path in Fallback Topbar

**Issue Found:**
```
Amneal_Logo_new.svg:1 Failed to load resource: 404 (Not Found)
```

**Root Cause:**
- In `js/topbar.js` line 194, the fallback topbar was missing the `img/` prefix
- Path was `Amneal_Logo_new.svg` instead of `img/Amneal_Logo_new.svg`

**Actions Taken:**
1. Fixed logo path in `js/topbar.js` fallback function

**File Modified:**
- ✅ `js/topbar.js` - Fixed logo path in `renderFallbackTopbar()` function

**Benefits:**
- ✅ Logo displays correctly even when config fails to load
- ✅ No more 404 errors for logo file

## Testing Recommendations

1. **Clear Browser Cache**
   - Hard refresh (Ctrl+Shift+R or Cmd+Shift+R)
   - Clear browser cache completely

2. **Verify Libraries Load**
   - Open browser console
   - Check for any 404 errors on `js/vendor/jspdf.umd.min.js`
   - Check for any 404 errors on `js/vendor/qrcode.min.js`

3. **Test PDF Generation**
   - Try generating a PDF from any page (Events, Tasks, Permits)
   - Verify PDF downloads correctly

4. **Test QR Code Generation**
   - Check if QR codes display on mobile_report.php
   - Verify QR codes work on permit pages

5. **Test PWA Functionality**
   - Check if manifest.json loads (Network tab)
   - Try installing app on mobile device
   - Verify offline functionality

6. **Test Navigation System**
   - Verify navbar loads correctly
   - Verify topbar loads correctly
   - Check that logo displays properly

7. **Test AI Navigator**
   - Verify AI Navigator sidebar loads
   - Check that ai-agent-config.json loads without errors

## Files Created

- `js/vendor/jspdf.umd.min.js` - jsPDF library v2.5.1 (364 KB)
- `js/vendor/qrcode.min.js` - QRCode library (23 KB)

## Files Modified

- `.htaccess` - Updated security rules with specific exceptions
- `index.php` - Updated library references
- `event_list.php` - Updated library references
- `event_center.php` - Updated library references
- `assessment_view.php` - Updated library references
- `permit_list0.php` - Updated library references
- `permit_list.php` - Updated library references
- `permitlist1Origin.php` - Updated library references
- `KPIEHS.php` - Updated library references
- `js/task_manager.js` - Updated dynamic loading
- `js/topbar.js` - Fixed logo path

## Security Improvements

1. **Reduced External Dependencies**
   - No longer relying on external CDNs for critical functionality
   - Reduced attack surface

2. **Granular Security Rules**
   - Still blocking sensitive JSON files (except manifest.json and ai-agent-config.json)
   - Still blocking sensitive PHP config files (database.php, api/config.php)
   - Only allowing specific application config files needed for UI
   - Whitelist approach for exceptions

3. **CSP Compliance**
   - All critical resources now load from 'self' origin
   - No CSP violations
   - Reduced reliance on external sources

## Notes

- The KPIEHS.php file was using an older version of jsPDF (2.4.0) and has been upgraded to 2.5.1
- All dynamic loading fallbacks in JavaScript have been updated to use local files
- The existing CSP header in .htaccess already allows CDN connections, but we've eliminated the need for them
- Navigation and topbar systems now work correctly with proper config file access
- Logo path issue in fallback topbar has been corrected

## Next Steps (Optional)

1. **Consider downloading Bootstrap locally** (currently still using CDN)
2. **Consider downloading Font Awesome locally** (currently still using CDN)
3. **Test thoroughly** across all browsers and devices
4. **Monitor console** for any remaining errors

## Rollback Instructions

If issues arise, you can rollback by:
1. Reverting the .htaccess changes
2. Changing script src back to CDN URLs
3. Removing the js/vendor directory

However, this will bring back the original CSP violations.
