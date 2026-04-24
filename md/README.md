# 📱 SHEEner Mobile Report - Deployment Guide

**Version:** 1.0  
**Date:** December 2025  
**Type:** Progressive Web App (PWA)

---

## 📋 Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Server Requirements](#server-requirements)
4. [Installation Steps](#installation-steps)
5. [Configuration](#configuration)
6. [HTTPS Setup Options](#https-setup-options)
7. [Testing & Verification](#testing--verification)
8. [Troubleshooting](#troubleshooting)
9. [Maintenance](#maintenance)

---

## 🎯 Overview

The SHEEner Mobile Report is a Progressive Web App (PWA) that allows employees to submit safety reports from their mobile devices. It works offline and syncs automatically when connected.

### **Key Features:**
- ✅ Works offline (reports saved locally)
- ✅ Automatic background sync
- ✅ GPS location capture
- ✅ Photo/file attachments
- ✅ Installable as native app
- ✅ No login required

### **Technology Stack:**
- **Backend:** PHP 8.1+ with MySQL 8.0 (Optimized for Strict Mode)
- **Frontend:** HTML5, JavaScript (ES6+), CSS3
- **Storage:** IndexedDB for offline storage
- **Server:** Apache 2.4+

---

## 📦 Prerequisites

### **Server Requirements:**
- **Operating System:** Windows Server 2016+ or Linux
- **Web Server:** Apache 2.4+ with mod_rewrite enabled
- **PHP:** Version 8.1 or higher (Compatible with 8.2 and 8.3)
- **MySQL:** Version 8.0 or higher
- **SSL Certificate:** Required for full PWA features (optional for testing)

### **PHP Extensions Required:**
- `php_mysqli` - MySQL database connection
- `php_json` - JSON encoding/decoding
- `php_fileinfo` - File upload handling
- `php_gd` or `php_imagick` - Image processing (optional)

### **Apache Modules Required:**
- `mod_rewrite` - URL rewriting
- `mod_headers` - HTTP headers
- `mod_ssl` - HTTPS support (if using SSL)

---

## 🚀 Installation Steps

### **Step 1: Copy Files to Server**

1. **Upload the `sheener` folder** to your web server's document root:
   ```
   C:\inetpub\wwwroot\sheener\          (Windows IIS)
   C:\xampp\htdocs\sheener\             (XAMPP)
   /var/www/html/sheener/               (Linux Apache)
   ```

2. **Verify folder structure:**
   ```
   sheener/
   ├── mobile_report.php          # Main mobile form
   ├── manifest.json              # PWA manifest
   ├── service-worker.js          # Service worker for offline
   ├── index.php                  # Main dashboard
   ├── img/
   │   ├── icons/                 # PWA icons
   │   │   ├── icon-192x192.png
   │   │   └── icon-512x512.png
   │   └── favicon/
   ├── js/
   │   ├── offline-storage.js     # Offline storage logic
   │   └── sync-manager.js        # Background sync
   ├── php/
   │   └── submit_anonymous_event.php  # Form submission handler
   └── css/
       └── (stylesheets)
   ```

### **Step 2: Configure Database Connection**

1. **Locate the database configuration file:**
   - Usually in `php/config.php` or at the top of `submit_anonymous_event.php`

2. **Update database credentials:**
   ```php
   <?php
   // Database configuration
   $db_host = 'localhost';           // Database server
   $db_name = 'sheener_db';          // Database name
   $db_user = 'sheener_user';        // Database username
   $db_pass = 'your_password_here';  // Database password
   
   // Create connection
   $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
   ```

3. **Test database connection:**
   - Access: `http://your-server/sheener/php/test_db.php`
   - Should show "Database connected successfully"

### **Step 3: Set File Permissions**

**On Windows Server:**
```powershell
# Give IIS_IUSRS write permissions to upload folders
icacls "C:\inetpub\wwwroot\sheener\uploads" /grant IIS_IUSRS:(OI)(CI)M
```

**On Linux:**
```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/html/sheener

# Set permissions
sudo chmod -R 755 /var/www/html/sheener
sudo chmod -R 775 /var/www/html/sheener/uploads
```

### **Step 4: Update Configuration Files**

#### **A. Update `manifest.json`**

Edit `manifest.json` to match your server:

```json
{
  "name": "SHEEner Reporter",
  "short_name": "Report",
  "description": "Safety, Health, Environment & Energy Event Reporter",
  "start_url": "/sheener/mobile_report.php",
  "scope": "/sheener/",
  "display": "standalone",
  "background_color": "#0A2F64",
  "theme_color": "#0A2F64",
  "orientation": "portrait",
  "icons": [
    {
      "src": "img/icons/icon-192x192.png",
      "sizes": "192x192",
      "type": "image/png",
      "purpose": "any maskable"
    },
    {
      "src": "img/icons/icon-512x512.png",
      "sizes": "512x512",
      "type": "image/png",
      "purpose": "any maskable"
    }
  ]
}
```

**Important:** Update `start_url` and `scope` if your installation path is different.

#### **B. Update `service-worker.js`**

Update the cache name and files list:

```javascript
const CACHE_NAME = 'sheener-v1.0';
const urlsToCache = [
  '/sheener/mobile_report.php',
  '/sheener/css/styles.css',
  '/sheener/js/offline-storage.js',
  '/sheener/js/sync-manager.js',
  '/sheener/img/icons/icon-192x192.png',
  '/sheener/img/icons/icon-512x512.png'
];
```

**Important:** Update paths if your installation directory is different.

---

## ⚙️ Configuration

### **Server IP Address Configuration**

#### **Option 1: Using Server's Internal IP (Recommended for Internal Network)**

1. **Find your server's IP address:**

   **Windows:**
   ```powershell
   ipconfig
   # Look for IPv4 Address under your active network adapter
   # Example: 192.168.1.100
   ```

   **Linux:**
   ```bash
   ip addr show
   # or
   hostname -I
   ```

2. **Update QR Generator:**
   
   The QR generator (`qr_generator.php`) automatically detects the server IP, but you can hardcode it if needed:

   ```php
   // In qr_generator.php, around line 167
   $serverIP = '192.168.1.100';  // Your server's IP
   ```

3. **Test access:**
   ```
   http://192.168.1.100/sheener/mobile_report.php
   ```

#### **Option 2: Using Domain Name (Recommended for Production)**

1. **Configure DNS:**
   - Add an A record pointing to your server's IP
   - Example: `sheener.yourcompany.com` → `192.168.1.100`

2. **Update Apache Virtual Host:**

   **Windows (httpd-vhosts.conf):**
   ```apache
   <VirtualHost *:80>
       ServerName sheener.yourcompany.com
       DocumentRoot "C:/inetpub/wwwroot/sheener"
       
       <Directory "C:/inetpub/wwwroot/sheener">
           Options Indexes FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

   **Linux (/etc/apache2/sites-available/sheener.conf):**
   ```apache
   <VirtualHost *:80>
       ServerName sheener.yourcompany.com
       DocumentRoot /var/www/html/sheener
       
       <Directory /var/www/html/sheener>
           Options Indexes FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

3. **Enable the site (Linux):**
   ```bash
   sudo a2ensite sheener.conf
   sudo systemctl reload apache2
   ```

---

## 🔒 HTTPS Setup Options

**IMPORTANT:** HTTPS is **required** for full PWA functionality on mobile devices (install option, offline mode, background sync).

### **Option 1: Self-Signed Certificate (Internal Use Only)**

**Pros:** Free, quick setup  
**Cons:** Browser security warnings, not trusted by default

**Windows (IIS):**
```powershell
# Create self-signed certificate
New-SelfSignedCertificate -DnsName "sheener.yourcompany.com" -CertStoreLocation "cert:\LocalMachine\My"

# Bind to IIS site
# Use IIS Manager → Site → Bindings → Add HTTPS binding
```

**Linux (Apache):**
```bash
# Generate certificate
sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout /etc/ssl/private/sheener.key \
  -out /etc/ssl/certs/sheener.crt

# Update Apache config
sudo nano /etc/apache2/sites-available/sheener-ssl.conf
```

```apache
<VirtualHost *:443>
    ServerName sheener.yourcompany.com
    DocumentRoot /var/www/html/sheener
    
    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/sheener.crt
    SSLCertificateKeyFile /etc/ssl/private/sheener.key
    
    <Directory /var/www/html/sheener>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

```bash
# Enable SSL module and site
sudo a2enmod ssl
sudo a2ensite sheener-ssl.conf
sudo systemctl reload apache2
```

### **Option 2: Let's Encrypt (Free, Trusted Certificate)**

**Pros:** Free, trusted by all browsers, auto-renewal  
**Cons:** Requires public domain name

```bash
# Install Certbot
sudo apt-get update
sudo apt-get install certbot python3-certbot-apache

# Get certificate
sudo certbot --apache -d sheener.yourcompany.com

# Auto-renewal is configured automatically
```

### **Option 3: Commercial SSL Certificate**

**Pros:** Trusted, professional, warranty  
**Cons:** Annual cost ($50-$500/year)

1. Purchase SSL certificate from provider (DigiCert, Comodo, etc.)
2. Generate CSR on your server
3. Submit CSR to certificate authority
4. Install certificate files on server
5. Configure Apache/IIS to use certificate

### **Option 4: ngrok (Development/Testing Only)**

**Pros:** Instant HTTPS, no configuration  
**Cons:** URL changes on restart, not for production

```bash
# Download ngrok from https://ngrok.com/download
# Authenticate with your token
ngrok config add-authtoken YOUR_TOKEN

# Start tunnel
ngrok http 80

# Use the HTTPS URL provided
# Example: https://abc123.ngrok-free.app/sheener/mobile_report.php
```

---

## 📱 QR Code Generation

### **Automatic QR Code Generator**

1. **Access the QR generator:**
   ```
   http://your-server-ip/sheener/qr_generator.php
   ```

2. **For HTTPS (recommended):**
   - Enter your HTTPS URL in the form
   - Click "Generate QR Code"
   - Print or display the QR code

3. **For HTTP (testing only):**
   - The generator automatically detects your server IP
   - Generates QR code pointing to: `http://192.168.1.100/sheener/mobile_report.php`

### **Manual QR Code Creation**

If you prefer to create QR codes manually:

1. **Use an online QR generator:** https://qr-code-generator.com
2. **Enter your URL:** `https://sheener.yourcompany.com/sheener/mobile_report.php`
3. **Download and print** the QR code

---

## ✅ Testing & Verification

### **Pre-Deployment Checklist**

- [ ] Database connection works
- [ ] File upload permissions set correctly
- [ ] Apache/IIS configured and running
- [ ] PHP extensions installed
- [ ] SSL certificate installed (for production)
- [ ] Firewall allows HTTP/HTTPS traffic
- [ ] Server IP is accessible from internal network

### **Test Procedure**

#### **1. Desktop Browser Test**

1. Open: `http://your-server-ip/sheener/mobile_report.php`
2. Check browser console (F12) for errors
3. Fill out a test report
4. Submit and verify it saves to database

#### **2. Mobile Browser Test (HTTP)**

1. Scan QR code or enter URL manually
2. Form should load
3. Fill out and submit a test report
4. **Note:** Install option won't appear over HTTP

#### **3. Mobile PWA Test (HTTPS)**

1. Access via HTTPS URL
2. Look for "Install app" prompt
3. Install the app
4. Test offline mode:
   - Turn on Airplane Mode
   - Fill out a report
   - Submit (should save locally)
   - Turn off Airplane Mode
   - Report should sync automatically

#### **4. Offline Functionality Test**

1. Install app on mobile device
2. Submit a report while online (should sync immediately)
3. Turn on Airplane Mode
4. Submit another report (should save locally)
5. Check footer - should show "Offline" and pending count
6. Turn off Airplane Mode
7. Report should sync automatically
8. Footer should show "Synced X event(s)"

---

## 🔧 Troubleshooting

### **Common Issues**

#### **Issue 1: "Install App" Option Not Appearing**

**Cause:** Not using HTTPS  
**Solution:** Set up SSL certificate (see HTTPS Setup Options)

**Verification:**
- URL must start with `https://`
- QR generator should show green "HTTPS Enabled!" message
- No SSL certificate errors in browser

---

#### **Issue 2: Form Not Submitting**

**Possible Causes:**
1. Database connection failed
2. PHP file permissions
3. Missing PHP extensions

**Solutions:**
```bash
# Check PHP error log
tail -f /var/log/apache2/error.log  # Linux
# or check Windows Event Viewer

# Test database connection
mysql -u sheener_user -p sheener_db

# Check PHP extensions
php -m | grep mysqli
```

---

#### **Issue 3: Subcategory Dropdown Not Working**

**Cause:** JavaScript not loading or syntax error  
**Solution:** 
- Check browser console for errors
- Verify all JS files are accessible
- Clear browser cache

---

#### **Issue 4: GPS Location Not Working**

**Cause:** Not using HTTPS or permissions denied  
**Solution:**
- Use HTTPS (required for geolocation on mobile)
- User must allow location permissions when prompted

---

#### **Issue 5: Service Worker Not Registering**

**Cause:** Not using HTTPS or incorrect paths  
**Solution:**
- Verify HTTPS is working
- Check `service-worker.js` paths match your installation
- Clear browser cache and re-register

---

#### **Issue 6: Mobile Devices Can't Access Server**

**Possible Causes:**
1. Firewall blocking access
2. Wrong IP address
3. Not on same network

**Solutions:**

**Windows Firewall:**
```powershell
# Allow Apache through firewall
New-NetFirewallRule -DisplayName "Apache HTTP" -Direction Inbound -LocalPort 80 -Protocol TCP -Action Allow
New-NetFirewallRule -DisplayName "Apache HTTPS" -Direction Inbound -LocalPort 443 -Protocol TCP -Action Allow
```

**Linux Firewall:**
```bash
# UFW
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# firewalld
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

**Verify IP:**
```powershell
# Windows
ipconfig

# Linux
ip addr show
```

---

## 🔄 Maintenance

### **Regular Tasks**

#### **Daily:**
- Monitor server logs for errors
- Check database for new submissions

#### **Weekly:**
- Review and clear old cached data
- Check disk space for uploads folder
- Verify SSL certificate validity

#### **Monthly:**
- Update PHP and Apache to latest versions
- Review and optimize database
- Test backup and restore procedures

### **Backup Procedures**

#### **Database Backup:**
```bash
# MySQL backup
mysqldump -u sheener_user -p sheener_db > sheener_backup_$(date +%Y%m%d).sql

# Restore
mysql -u sheener_user -p sheener_db < sheener_backup_20251220.sql
```

#### **File Backup:**
```bash
# Linux
tar -czf sheener_files_$(date +%Y%m%d).tar.gz /var/www/html/sheener

# Windows
Compress-Archive -Path "C:\inetpub\wwwroot\sheener" -DestinationPath "sheener_backup_$(Get-Date -Format 'yyyyMMdd').zip"
```

### **Updating the Application**

1. **Backup current installation**
2. **Upload new files** (don't overwrite config files)
3. **Update database** if schema changed
4. **Clear browser caches** on all devices
5. **Update service worker version** to force cache refresh:
   ```javascript
   const CACHE_NAME = 'sheener-v1.1';  // Increment version
   ```
6. **Test thoroughly** before announcing to users

---

## 📊 Monitoring

### **Key Metrics to Monitor**

1. **Server Performance:**
   - CPU usage
   - Memory usage
   - Disk space

2. **Application Health:**
   - Form submission success rate
   - Average response time
   - Error rate

3. **User Activity:**
   - Number of reports submitted
   - Peak usage times
   - Mobile vs desktop usage

### **Log Files**

**Apache Access Log:**
```bash
# Linux
tail -f /var/log/apache2/access.log

# Windows
Get-Content "C:\xampp\apache\logs\access.log" -Tail 50 -Wait
```

**Apache Error Log:**
```bash
# Linux
tail -f /var/log/apache2/error.log

# Windows
Get-Content "C:\xampp\apache\logs\error.log" -Tail 50 -Wait
```

**PHP Error Log:**
```bash
# Check php.ini for error_log location
# Usually: /var/log/php_errors.log
tail -f /var/log/php_errors.log
```

---

## 📞 Support

### **Documentation Files**

- `README.md` - This file
- `HTTPS_SETUP_COMPLETE.md` - HTTPS setup guide
- `MOBILE_FIXES.md` - Recent bug fixes
- `SUCCESS_REPORT.md` - Initial setup success report
- `TEST_REPORT.md` - Testing procedures

### **Getting Help**

1. Check browser console for JavaScript errors (F12)
2. Check server error logs
3. Review this documentation
4. Contact your IT department

---

## 📝 Configuration Summary

### **Quick Reference**

| Setting | Value | Location |
|---------|-------|----------|
| **Installation Path** | `/sheener/` | Web root |
| **Database Name** | `sheener_db` | config.php |
| **Database User** | `sheener_user` | config.php |
| **Server IP** | `192.168.x.x` | Auto-detected |
| **HTTPS** | Required for PWA | SSL certificate |
| **PHP Version** | 7.4+ | Server |
| **Apache Version** | 2.4+ | Server |

### **URLs**

| Purpose | URL |
|---------|-----|
| **Mobile Form** | `https://your-server/sheener/mobile_report.php` |
| **QR Generator** | `https://your-server/sheener/qr_generator.php` |
| **Main Dashboard** | `https://your-server/sheener/index.php` |
| **Manifest** | `https://your-server/sheener/manifest.json` |
| **Service Worker** | `https://your-server/sheener/service-worker.js` |

---

## ✅ Deployment Checklist

Use this checklist when deploying to production:

### **Pre-Deployment**
- [ ] Server meets all requirements
- [ ] PHP extensions installed
- [ ] Apache modules enabled
- [ ] Database created and configured
- [ ] SSL certificate obtained and installed
- [ ] Files uploaded to server
- [ ] File permissions set correctly
- [ ] Configuration files updated

### **Testing**
- [ ] Desktop browser test passed
- [ ] Mobile browser test passed
- [ ] PWA install test passed
- [ ] Offline functionality test passed
- [ ] Form submission test passed
- [ ] GPS location test passed
- [ ] File upload test passed

### **Production**
- [ ] Firewall configured
- [ ] Backup procedures in place
- [ ] Monitoring configured
- [ ] QR codes generated and distributed
- [ ] Users trained
- [ ] Documentation provided

---

## 🎉 Conclusion

Your SHEEner Mobile Report PWA is now ready for deployment! Follow this guide step-by-step to ensure a smooth installation on your company server.

**Key Takeaways:**
- ✅ HTTPS is required for full PWA features
- ✅ Test thoroughly before production deployment
- ✅ Keep backups of database and files
- ✅ Monitor logs and performance regularly

**For questions or issues, refer to the Troubleshooting section or contact your IT support team.**

---

**Document Version:** 1.0  
**Last Updated:** December 2025  
**Author:** SHEEner Development Team
