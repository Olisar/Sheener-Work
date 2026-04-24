# WiFi Access Implementation Checklist
**Project:** SHEEner Mobile App WiFi Access  
**Server:** 172.21.10.99  
**Date:** December 22, 2025

---

## ⚠️ CRITICAL: Do NOT Enable WiFi Access Until This Checklist is Complete

---

## Pre-Implementation Checklist

### **Documentation Review**
- [ ] Read `SECURITY_IMPLEMENTATION_SUMMARY.md`
- [ ] Review `HTTPS_IMPLEMENTATION_GUIDE.md`
- [ ] Understand risks in `SECURITY_ASSESSMENT.md`
- [ ] Management approval obtained
- [ ] IT department approval obtained

---

## Phase 1: HTTPS Implementation (REQUIRED)

### **1. SSL Certificate Generation**
- [ ] Open Command Prompt as Administrator
- [ ] Navigate to `C:\xampp\apache`
- [ ] Run OpenSSL command to generate certificate
- [ ] Common Name set to: `172.21.10.99`
- [ ] Certificate files created:
  - [ ] `conf/ssl.crt/server.crt`
  - [ ] `conf/ssl.key/server.key`

**Command:**
```powershell
cd C:\xampp\apache
bin\openssl.exe req -x509 -nodes -days 365 -newkey rsa:2048 -keyout conf\ssl.key\server.key -out conf\ssl.crt\server.crt
```

---

### **2. Apache Configuration**
- [ ] Edit `C:\xampp\apache\conf\httpd.conf`
- [ ] Uncommented: `LoadModule ssl_module modules/mod_ssl.so`
- [ ] Uncommented: `Include conf/extra/httpd-ssl.conf`
- [ ] Edit `C:\xampp\apache\conf\extra\httpd-ssl.conf`
- [ ] Set `DocumentRoot "C:/xampp/htdocs/sheener"`
- [ ] Set `ServerName 172.21.10.99:443`
- [ ] Set `SSLCertificateFile "conf/ssl.crt/server.crt"`
- [ ] Set `SSLCertificateKeyFile "conf/ssl.key/server.key"`
- [ ] Added `<Directory>` section with `AllowOverride All`

---

### **3. HTTPS Redirect**
- [ ] Edit `.htaccess` in sheener directory
- [ ] Uncommented HTTPS redirect lines (lines 6-7)
- [ ] Verified rewrite rules are active

**Lines to uncomment in `.htaccess`:**
```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

### **4. Update Application Code**
- [ ] Edit `index.php` line 1388
- [ ] Changed protocol from `http://` to `https://`

**Change in `index.php`:**
```javascript
// FROM:
const protocol = 'http://';

// TO:
const protocol = 'https://';
```

---

### **5. PHP Configuration**
- [ ] Edit `C:\xampp\php\php.ini`
- [ ] Set `session.cookie_secure = 1`
- [ ] Set `session.cookie_httponly = 1`
- [ ] Set `session.cookie_samesite = Strict`
- [ ] Set `session.use_strict_mode = 1`

---

### **6. Apache Restart**
- [ ] Stop Apache in XAMPP Control Panel
- [ ] Start Apache in XAMPP Control Panel
- [ ] Check for errors in Apache error log
- [ ] Verify Apache is running on port 443

**Check logs:**
- `C:\xampp\apache\logs\error.log`
- `C:\xampp\apache\logs\ssl_error.log`

---

### **7. HTTPS Testing**
- [ ] Open browser: `https://172.21.10.99/sheener/`
- [ ] Accept security warning (expected for self-signed cert)
- [ ] Homepage loads correctly
- [ ] Login works over HTTPS
- [ ] Session persists after login
- [ ] All pages load without errors
- [ ] Check browser console for errors
- [ ] Verify security headers present (F12 → Network → Headers)

**Expected Security Headers:**
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN`
- `X-XSS-Protection: 1; mode=block`
- `Content-Security-Policy: ...`

---

### **8. QR Code Verification**
- [ ] QR code displays on homepage
- [ ] QR code generates HTTPS URL
- [ ] Scan QR code with phone
- [ ] URL opens: `https://172.21.10.99/sheener/mobile_report.php`
- [ ] Accept certificate warning on phone
- [ ] Mobile page loads correctly

---

## Phase 2: Network Configuration (IT Department)

### **Firewall Rules**
- [ ] Allow: WiFi VLAN → 172.21.10.99:443 (HTTPS)
- [ ] Deny: WiFi VLAN → 172.21.10.99:80 (HTTP)
- [ ] Test: HTTP requests are blocked from WiFi
- [ ] Test: HTTPS requests work from WiFi

**Windows Firewall (on server):**
```powershell
netsh advfirewall firewall add rule name="HTTPS Inbound" dir=in action=allow protocol=TCP localport=443
```

**Network Firewall:**
- Create rule allowing WiFi subnet to 172.21.10.99:443
- Block WiFi subnet to 172.21.10.99:80

---

### **WiFi Network Configuration**
- [ ] WPA3 or WPA2-Enterprise enabled
- [ ] Client isolation configured (optional)
- [ ] Separate SSID for employee devices (optional)
- [ ] VLAN segmentation implemented (optional)

---

### **Network Monitoring**
- [ ] IDS/IPS enabled on WiFi network
- [ ] Monitoring configured for 172.21.10.99
- [ ] Alerting configured for suspicious activity
- [ ] Log retention policy defined

---

## Phase 3: Mobile Device Setup

### **Certificate Installation (Android)**
- [ ] Copy `server.crt` to phone
- [ ] Settings → Security → Install from storage
- [ ] Select certificate file
- [ ] Name: "SHEEner Server"
- [ ] Usage: "VPN and apps"
- [ ] Certificate installed successfully
- [ ] Test: No security warning when accessing HTTPS

---

### **Certificate Installation (iOS)**
- [ ] Email certificate to user
- [ ] Open email on iPhone
- [ ] Tap certificate attachment
- [ ] Install profile
- [ ] Settings → General → About → Certificate Trust Settings
- [ ] Enable full trust for certificate
- [ ] Test: No security warning when accessing HTTPS

---

### **Mobile App Testing**
- [ ] Connect phone to company WiFi
- [ ] Scan QR code from homepage
- [ ] Mobile app page loads
- [ ] Install PWA (Add to Home Screen)
- [ ] App icon appears on home screen
- [ ] Open app from home screen
- [ ] GPS location works
- [ ] Event submission works
- [ ] File upload works
- [ ] Offline mode works
- [ ] Sync works when reconnected

---

## Phase 4: Security Verification

### **Security Testing**
- [ ] HTTPS enforced (HTTP redirects to HTTPS)
- [ ] Security headers present
- [ ] Session cookies have Secure flag
- [ ] Session cookies have HttpOnly flag
- [ ] Session cookies have SameSite=Strict
- [ ] File uploads restricted to allowed types
- [ ] Sensitive files not accessible (test: `/api/config.php`)
- [ ] Directory listing disabled
- [ ] PHP execution disabled in uploads directory

**Test Commands:**
```powershell
# Test HTTPS
curl -I https://172.21.10.99/sheener/

# Test HTTP redirect
curl -I http://172.21.10.99/sheener/

# Test security headers
curl -I https://172.21.10.99/sheener/ | findstr "X-Frame-Options"
```

---

### **Penetration Testing (Optional but Recommended)**
- [ ] SQL injection testing
- [ ] XSS testing
- [ ] CSRF testing
- [ ] Session hijacking testing
- [ ] File upload vulnerability testing
- [ ] Authentication bypass testing

**Tools:**
- OWASP ZAP
- Burp Suite
- Nikto
- SQLMap

---

## Phase 5: User Training

### **Employee Training**
- [ ] Security awareness training completed
- [ ] Mobile app usage instructions provided
- [ ] Certificate installation guide distributed
- [ ] Incident reporting procedure explained
- [ ] Password policy communicated

---

### **Documentation**
- [ ] User manual created
- [ ] FAQ document created
- [ ] Troubleshooting guide created
- [ ] Support contact information provided

---

## Phase 6: Monitoring and Maintenance

### **Ongoing Monitoring**
- [ ] Daily log review scheduled
- [ ] Weekly security scan scheduled
- [ ] Monthly certificate expiration check
- [ ] Quarterly security audit scheduled

---

### **Incident Response**
- [ ] Incident response plan created
- [ ] Security team contact list updated
- [ ] Escalation procedures defined
- [ ] Backup and recovery plan tested

---

### **Maintenance Schedule**
- [ ] Certificate renewal reminder (360 days)
- [ ] Security patch schedule defined
- [ ] Backup schedule configured
- [ ] Disaster recovery plan documented

---

## Go-Live Checklist

### **Final Verification Before WiFi Access**
- [ ] All Phase 1 tasks completed
- [ ] All Phase 2 tasks completed
- [ ] All Phase 3 tasks completed
- [ ] All Phase 4 tasks completed
- [ ] Management approval obtained
- [ ] IT approval obtained
- [ ] Security team approval obtained (if applicable)
- [ ] Compliance review completed (if required)
- [ ] Rollback plan prepared
- [ ] Support team briefed

---

### **Go-Live Steps**
1. [ ] Announce WiFi access to employees
2. [ ] Provide QR code access instructions
3. [ ] Provide certificate installation guide
4. [ ] Monitor for issues (first 24 hours)
5. [ ] Collect user feedback
6. [ ] Address any issues immediately
7. [ ] Document lessons learned

---

## Post-Implementation

### **Week 1 Review**
- [ ] Review access logs
- [ ] Review error logs
- [ ] Review security logs
- [ ] Collect user feedback
- [ ] Address any issues
- [ ] Update documentation

---

### **Month 1 Review**
- [ ] Security audit
- [ ] Performance review
- [ ] User satisfaction survey
- [ ] Plan Phase 2 security enhancements
- [ ] Update risk assessment

---

## Phase 2 Security Enhancements (Within 1 Week)

### **CSRF Protection**
- [ ] Implement CSRF token validation
- [ ] Test CSRF protection
- [ ] Update all forms with CSRF tokens

---

### **Rate Limiting**
- [ ] Implement rate limiting on login
- [ ] Implement rate limiting on form submissions
- [ ] Test rate limiting
- [ ] Configure lockout thresholds

---

### **Enhanced Session Security**
- [ ] Implement session timeout (30 minutes)
- [ ] Implement IP address validation
- [ ] Implement user agent validation
- [ ] Test session security

---

### **Security Logging**
- [ ] Implement authentication logging
- [ ] Implement access logging
- [ ] Implement error logging
- [ ] Configure log rotation
- [ ] Configure log monitoring

---

## Phase 3 Security Enhancements (Within 1 Month)

### **CAPTCHA Implementation**
- [ ] Install CAPTCHA library
- [ ] Add CAPTCHA to anonymous event form
- [ ] Add CAPTCHA to login form (after 3 failed attempts)
- [ ] Test CAPTCHA functionality

---

### **Web Application Firewall**
- [ ] Install ModSecurity
- [ ] Configure OWASP Core Rule Set
- [ ] Test WAF rules
- [ ] Fine-tune false positives

---

### **Advanced Monitoring**
- [ ] Implement intrusion detection
- [ ] Configure alerting
- [ ] Set up dashboard
- [ ] Test alert notifications

---

## Risk Acceptance

### **Accepted Risks**
Document any risks that are accepted (with management approval):

- [ ] Risk: ________________
  - Impact: ________________
  - Likelihood: ________________
  - Mitigation: ________________
  - Approved by: ________________
  - Date: ________________

---

## Sign-Off

### **Implementation Team**

**Server Administrator:**
- Name: ________________
- Signature: ________________
- Date: ________________

**IT Security:**
- Name: ________________
- Signature: ________________
- Date: ________________

**Network Administrator:**
- Name: ________________
- Signature: ________________
- Date: ________________

**Project Manager:**
- Name: ________________
- Signature: ________________
- Date: ________________

**Management Approval:**
- Name: ________________
- Signature: ________________
- Date: ________________

---

## Emergency Contacts

**In case of security incident:**

- IT Security: ________________
- Network Admin: ________________
- Server Admin: ________________
- Management: ________________
- External Security Consultant: ________________

---

## Notes and Comments

Use this space to document any issues, deviations, or additional information:

```
[Date] [Name] [Note]
_____________________________________________
_____________________________________________
_____________________________________________
_____________________________________________
_____________________________________________
```

---

**Document Version:** 1.0  
**Last Updated:** December 22, 2025  
**Next Review:** After Go-Live
