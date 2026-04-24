# HTTPS Implementation Guide for SHEEner
**Priority: CRITICAL - Required before enabling WiFi access**

---

## Overview

This guide will help you implement HTTPS on your XAMPP/Apache server running on Windows. Without HTTPS, all data (including passwords) is transmitted in plain text over WiFi.

---

## Option 1: Self-Signed Certificate (For Internal Network) ⭐ RECOMMENDED

### **Advantages:**
- Free and quick to implement
- Sufficient for internal company network
- No external dependencies

### **Disadvantages:**
- Browser warnings (can be bypassed by adding to trusted certificates)
- Not suitable for external access

### **Implementation Steps:**

#### Step 1: Generate Self-Signed Certificate

Open Command Prompt as Administrator and navigate to your XAMPP directory:

```powershell
cd C:\xampp\apache
```

Generate the certificate (valid for 365 days):

```powershell
bin\openssl.exe req -x509 -nodes -days 365 -newkey rsa:2048 -keyout conf\ssl.key\server.key -out conf\ssl.crt\server.crt
```

**Fill in the prompts:**
- Country Name: IE
- State: Your State
- Locality: Your City
- Organization Name: Amneal Pharmaceuticals
- Organizational Unit: EHS Department
- Common Name: **172.21.10.99** (IMPORTANT: Use your server IP)
- Email: your-email@company.com

#### Step 2: Enable SSL Module in Apache

Edit `C:\xampp\apache\conf\httpd.conf`:

Uncomment these lines (remove the `#` at the start):
```apache
LoadModule ssl_module modules/mod_ssl.so
Include conf/extra/httpd-ssl.conf
```

#### Step 3: Configure SSL Virtual Host

Edit `C:\xampp\apache\conf\extra\httpd-ssl.conf`:

Find the `<VirtualHost _default_:443>` section and update:

```apache
<VirtualHost _default_:443>
    DocumentRoot "C:/xampp/htdocs/sheener"
    ServerName 172.21.10.99:443
    ServerAdmin admin@company.com
    
    ErrorLog "C:/xampp/apache/logs/ssl_error.log"
    TransferLog "C:/xampp/apache/logs/ssl_access.log"
    
    SSLEngine on
    SSLCertificateFile "conf/ssl.crt/server.crt"
    SSLCertificateKeyFile "conf/ssl.key/server.key"
    
    <Directory "C:/xampp/htdocs/sheener">
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Require all granted
    </Directory>
    
    # Modern SSL Configuration
    SSLProtocol all -SSLv3 -TLSv1 -TLSv1.1
    SSLCipherSuite HIGH:!aNULL:!MD5:!3DES
    SSLHonorCipherOrder on
</VirtualHost>
```

#### Step 4: Force HTTPS Redirect

Create or edit `C:\xampp\htdocs\sheener\.htaccess`:

```apache
# Force HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Security Headers
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "SAMEORIGIN"
Header set X-XSS-Protection "1; mode=block"
Header set Referrer-Policy "strict-origin-when-cross-origin"
Header set Strict-Transport-Security "max-age=31536000; includeSubDomains"

# Prevent directory listing
Options -Indexes

# Protect sensitive files
<FilesMatch "\.(env|ini|log|sh|sql|conf|bak)$">
    Require all denied
</FilesMatch>

# Protect uploads directory from script execution
<Directory "uploads">
    php_flag engine off
    RemoveHandler .php .phtml .php3 .php4 .php5 .phps
    RemoveType .php .phtml .php3 .php4 .php5 .phps
</Directory>
```

#### Step 5: Update PHP Session Configuration

Edit `C:\xampp\php\php.ini`:

Find and update these settings:
```ini
session.cookie_secure = 1
session.cookie_httponly = 1
session.cookie_samesite = Strict
session.use_strict_mode = 1
```

#### Step 6: Restart Apache

In XAMPP Control Panel:
1. Stop Apache
2. Start Apache
3. Check for errors in the logs

#### Step 7: Update QR Code URL

The QR code in `index.php` has already been updated to use the company server IP. Now update it to use HTTPS:

**File:** `d:\xampp\htdocs\sheener\index.php`

Change line 1388 from:
```javascript
const mobileUrl = `http://${localIP}${basePath}/mobile_report.php`;
```

To:
```javascript
const mobileUrl = `https://${localIP}${basePath}/mobile_report.php`;
```

#### Step 8: Test HTTPS

1. Open browser and navigate to: `https://172.21.10.99/sheener/`
2. You'll see a security warning (expected for self-signed certificate)
3. Click "Advanced" → "Proceed to 172.21.10.99 (unsafe)"
4. Verify the site loads correctly

#### Step 9: Install Certificate on Mobile Devices

To avoid security warnings on mobile devices:

**For Android:**
1. Copy `C:\xampp\apache\conf\ssl.crt\server.crt` to your phone
2. Settings → Security → Install from storage
3. Select the certificate file
4. Give it a name (e.g., "SHEEner Server")
5. Select "VPN and apps" usage

**For iOS:**
1. Email the certificate to yourself
2. Open email on iPhone
3. Tap the certificate attachment
4. Install the profile
5. Settings → General → About → Certificate Trust Settings
6. Enable full trust for the certificate

---

## Option 2: Let's Encrypt (For External Access)

### **Advantages:**
- Free, trusted SSL certificate
- No browser warnings
- Auto-renewal

### **Disadvantages:**
- Requires domain name (not just IP address)
- Requires external internet access
- More complex setup

### **Implementation Steps:**

#### Prerequisites:
- Domain name pointing to your server (e.g., sheener.company.com)
- Port 80 and 443 accessible from internet

#### Step 1: Install Certbot

Download Certbot for Windows from: https://certbot.eff.org/

#### Step 2: Generate Certificate

```powershell
certbot certonly --standalone -d sheener.company.com
```

#### Step 3: Configure Apache

Update `httpd-ssl.conf`:
```apache
SSLCertificateFile "C:/Certbot/live/sheener.company.com/cert.pem"
SSLCertificateKeyFile "C:/Certbot/live/sheener.company.com/privkey.pem"
SSLCertificateChainFile "C:/Certbot/live/sheener.company.com/chain.pem"
```

#### Step 4: Auto-Renewal

Create a scheduled task to run:
```powershell
certbot renew
```

---

## Option 3: Internal Certificate Authority (Enterprise)

### **Best for:** Large organizations with existing PKI infrastructure

Contact your IT department to:
1. Request an SSL certificate for 172.21.10.99
2. Install the certificate on the server
3. Distribute the CA certificate to all employee devices

---

## Verification Checklist

After implementing HTTPS, verify:

- [ ] Site accessible via `https://172.21.10.99/sheener/`
- [ ] HTTP automatically redirects to HTTPS
- [ ] Login works correctly
- [ ] Session cookies have `Secure` flag
- [ ] QR code generates HTTPS URL
- [ ] Mobile app works over HTTPS
- [ ] File uploads work correctly
- [ ] No mixed content warnings in browser console

---

## Testing Commands

### Test SSL Configuration:
```powershell
# Test SSL certificate
openssl s_client -connect 172.21.10.99:443 -showcerts

# Check certificate expiration
openssl x509 -in C:\xampp\apache\conf\ssl.crt\server.crt -noout -dates

# Test HTTPS connection
curl -k https://172.21.10.99/sheener/
```

### Check Apache Configuration:
```powershell
# Test Apache configuration
C:\xampp\apache\bin\httpd.exe -t

# View loaded modules
C:\xampp\apache\bin\httpd.exe -M | findstr ssl
```

---

## Troubleshooting

### Issue: Apache won't start after enabling SSL

**Solution:**
1. Check error log: `C:\xampp\apache\logs\error.log`
2. Verify certificate paths are correct
3. Ensure port 443 is not blocked by firewall
4. Check if another service is using port 443:
   ```powershell
   netstat -ano | findstr :443
   ```

### Issue: Browser shows "NET::ERR_CERT_AUTHORITY_INVALID"

**Solution:**
- This is expected for self-signed certificates
- Install the certificate on client devices (see Step 9)
- Or use Let's Encrypt for trusted certificate

### Issue: Mixed content warnings

**Solution:**
- Ensure all resources (CSS, JS, images) use HTTPS or relative URLs
- Update any hardcoded HTTP URLs to HTTPS

### Issue: Session not persisting

**Solution:**
- Check `php.ini` has `session.cookie_secure = 1`
- Verify cookies are being set in browser DevTools
- Clear browser cookies and try again

---

## Security Best Practices

### 1. **Use Strong SSL Configuration**
```apache
# Only allow TLS 1.2 and 1.3
SSLProtocol all -SSLv3 -TLSv1 -TLSv1.1

# Use strong cipher suites
SSLCipherSuite ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384

# Prefer server cipher order
SSLHonorCipherOrder on
```

### 2. **Enable HSTS (HTTP Strict Transport Security)**
```apache
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
```

### 3. **Disable Weak Protocols**
```apache
SSLProtocol all -SSLv2 -SSLv3 -TLSv1 -TLSv1.1
```

### 4. **Regular Certificate Renewal**
- Self-signed: Renew annually
- Let's Encrypt: Auto-renews every 90 days
- Enterprise CA: Follow company policy

---

## Performance Considerations

### Enable HTTP/2 (Optional)
```apache
LoadModule http2_module modules/mod_http2.so
Protocols h2 http/1.1
```

### Enable SSL Session Caching
```apache
SSLSessionCache "shmcb:C:/xampp/apache/logs/ssl_scache(512000)"
SSLSessionCacheTimeout 300
```

---

## Firewall Configuration (For IT Department)

### Windows Firewall Rules:
```powershell
# Allow HTTPS (port 443)
netsh advfirewall firewall add rule name="HTTPS Inbound" dir=in action=allow protocol=TCP localport=443

# Block HTTP (port 80) from WiFi network (optional)
# Only if you want to force HTTPS only
```

### Network Firewall Rules:
```
Allow: WiFi VLAN → 172.21.10.99:443 (HTTPS)
Deny:  WiFi VLAN → 172.21.10.99:80 (HTTP)
```

---

## Estimated Implementation Time

- **Self-Signed Certificate:** 30-60 minutes
- **Let's Encrypt:** 1-2 hours
- **Enterprise CA:** Depends on IT department

---

## Next Steps After HTTPS

1. ✅ Implement CSRF token validation
2. ✅ Add rate limiting
3. ✅ Enhance session security
4. ✅ Add security logging
5. ✅ Implement file upload security

See `SECURITY_ASSESSMENT.md` for complete security roadmap.

---

## Support Resources

- **XAMPP SSL Documentation:** https://www.apachefriends.org/faq_windows.html
- **Apache SSL/TLS:** https://httpd.apache.org/docs/2.4/ssl/
- **Let's Encrypt:** https://letsencrypt.org/
- **SSL Labs Test:** https://www.ssllabs.com/ssltest/ (for external sites)

---

**Document Version:** 1.0  
**Last Updated:** December 22, 2025  
**Status:** Ready for implementation
