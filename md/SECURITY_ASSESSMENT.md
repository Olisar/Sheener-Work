# Security Assessment: WiFi Access to SHEEner Application
**Date:** December 22, 2025  
**Environment:** Dedicated VM on Company Network, XAMPP/Apache Stack  
**Scope:** Granting WiFi access to employees' mobile devices

---

## Executive Summary

Allowing WiFi-connected mobile devices to access your internal server introduces **moderate to high security risks** depending on your current security controls. This document analyzes your current security posture, identifies vulnerabilities, and provides actionable recommendations.

**Risk Level:** ⚠️ **MEDIUM-HIGH** (without additional security measures)

---

## Current Security Analysis

### ✅ **Security Measures ALREADY IMPLEMENTED**

Based on code review, your application has the following security controls:

#### 1. **Authentication & Authorization**
- ✅ Session-based authentication (`session_start()` in all protected endpoints)
- ✅ Password hashing using `password_verify()` (bcrypt/argon2)
- ✅ Role-based access control (RBAC) with multiple roles
- ✅ Session regeneration after login (`session_regenerate_id(true)`)
- ✅ CSRF token generation (`$_SESSION['csrf_token']`)
- ✅ Authorization flag (`$_SESSION['is_authorized']`)

**File:** `php/login.php` (Lines 20, 71, 81)

#### 2. **Input Validation & Sanitization**
- ✅ Prepared statements for database queries (PDO with parameterized queries)
- ✅ Output escaping with `htmlspecialchars()` in multiple locations
- ✅ Email validation (`filter_var($email, FILTER_VALIDATE_EMAIL)`)
- ✅ File upload validation (size limits: 5MB per file, max 10 files)
- ✅ File type restrictions (images, PDF, Office documents)

**Files:** `php/submit_anonymous_event.php` (Lines 35-40, 69-87), `php/login.php` (Lines 126, 129)

#### 3. **File Upload Security**
- ✅ File size limits enforced (5MB per file)
- ✅ File count limits (max 10 files)
- ✅ Dedicated upload directory with restricted access
- ✅ File type validation based on extensions

**File:** `php/submit_anonymous_event.php` (Lines 62-88)

#### 4. **Database Security**
- ✅ PDO with prepared statements (prevents SQL injection)
- ✅ Parameterized queries throughout the application
- ✅ Database connection abstraction layer

**Files:** Multiple PHP files using `$pdo->prepare()` and `$stmt->execute()`

---

## ⚠️ **CRITICAL SECURITY GAPS IDENTIFIED**

### 1. **NO HTTPS/TLS ENCRYPTION** 🔴 **CRITICAL**
**Current State:** HTTP only (`http://172.21.10.99`)

**Risks:**
- All data transmitted in **plain text** over WiFi
- Credentials can be intercepted (man-in-the-middle attacks)
- Session cookies can be stolen (session hijacking)
- Sensitive data exposure (event reports, personal information)

**Impact:** **CRITICAL** - Anyone on the WiFi network can intercept all traffic

**Recommendation:** **IMPLEMENT HTTPS IMMEDIATELY** before allowing WiFi access

---

### 2. **Missing CSRF Protection Implementation** 🟡 **HIGH**
**Current State:** CSRF token generated but not validated

**Risks:**
- Cross-Site Request Forgery attacks
- Unauthorized actions performed on behalf of authenticated users

**Impact:** **HIGH** - Attackers can trick users into performing unwanted actions

**Recommendation:** Implement CSRF token validation on all state-changing requests

---

### 3. **No Rate Limiting** 🟡 **HIGH**
**Current State:** No rate limiting on login or form submissions

**Risks:**
- Brute force password attacks
- Denial of Service (DoS) through form spam
- Resource exhaustion

**Impact:** **HIGH** - Attackers can attempt unlimited login attempts

**Recommendation:** Implement rate limiting on authentication and form submissions

---

### 4. **Session Security Weaknesses** 🟡 **MEDIUM**
**Current State:** Basic session management

**Risks:**
- Session fixation attacks
- Session hijacking over HTTP
- No session timeout enforcement
- No IP address validation

**Impact:** **MEDIUM-HIGH** - Sessions can be compromised

**Recommendation:** Enhance session security with additional controls

---

### 5. **No Web Application Firewall (WAF)** 🟡 **MEDIUM**
**Current State:** No WAF protection

**Risks:**
- No protection against common web attacks
- No request filtering or anomaly detection
- No IP-based access control

**Impact:** **MEDIUM** - Vulnerable to automated attacks

**Recommendation:** Implement ModSecurity or similar WAF

---

### 6. **File Upload Vulnerabilities** 🟠 **MEDIUM**
**Current State:** Basic file type validation by extension only

**Risks:**
- File type spoofing (malicious files with fake extensions)
- No MIME type validation
- No virus/malware scanning
- Uploaded files may be directly accessible

**Impact:** **MEDIUM** - Malicious file uploads possible

**Recommendation:** Implement comprehensive file validation and scanning

---

### 7. **No Security Headers** 🟠 **MEDIUM**
**Current State:** No security headers detected

**Risks:**
- Clickjacking attacks
- XSS attacks
- MIME type sniffing
- Referrer leakage

**Impact:** **MEDIUM** - Browser-based attacks possible

**Recommendation:** Implement security headers

---

### 8. **Database Credentials in Code** 🟠 **MEDIUM**
**Current State:** `api/config.php` (gitignored but on server)

**Risks:**
- If server is compromised, credentials are exposed
- No encryption of credentials at rest

**Impact:** **MEDIUM** - Database compromise if server is breached

**Recommendation:** Use environment variables or encrypted credential storage

---

### 9. **No Intrusion Detection** 🟡 **LOW-MEDIUM**
**Current State:** No IDS/IPS

**Risks:**
- No detection of suspicious activity
- No alerting on security events
- No audit trail for security incidents

**Impact:** **LOW-MEDIUM** - Attacks may go unnoticed

**Recommendation:** Implement logging and monitoring

---

### 10. **Anonymous Event Submission Endpoint** 🟠 **MEDIUM**
**Current State:** Open endpoint without authentication

**Risks:**
- Spam submissions
- Resource exhaustion
- Data pollution

**Impact:** **MEDIUM** - System abuse possible

**Recommendation:** Implement CAPTCHA or honeypot protection

---

## WiFi-Specific Security Risks

### **Risks of Allowing WiFi Access:**

1. **Network Exposure**
   - WiFi is inherently less secure than wired networks
   - Easier for attackers to join the network (rogue devices)
   - Increased attack surface

2. **Man-in-the-Middle (MITM) Attacks**
   - Without HTTPS, all traffic can be intercepted
   - Credentials, session tokens, and data exposed
   - **CRITICAL RISK** in current configuration

3. **Rogue Access Points**
   - Attackers can create fake WiFi networks
   - Users may connect to malicious networks
   - Credentials can be harvested

4. **Device Security**
   - Employee phones may have malware
   - Lost/stolen devices can access the system
   - No device management or security policies

5. **Lateral Movement**
   - Compromised mobile device can access other network resources
   - Server may be vulnerable to attacks from WiFi network

---

## Recommended Security Measures

### **IMMEDIATE ACTIONS (Before Enabling WiFi Access)** 🔴

#### 1. **Implement HTTPS/TLS** ⭐ **CRITICAL**
**Priority:** HIGHEST

```apache
# Install SSL certificate (Let's Encrypt or internal CA)
# Configure Apache to use HTTPS
# Redirect all HTTP to HTTPS
```

**Steps:**
1. Generate SSL certificate (self-signed for internal use or Let's Encrypt)
2. Configure Apache to enable SSL module
3. Update virtual host configuration
4. Force HTTPS redirect
5. Update QR code to use HTTPS URL

**Files to create:**
- Apache SSL configuration
- SSL certificate and key files

---

#### 2. **Implement CSRF Token Validation** ⭐ **HIGH PRIORITY**

**Current:** Token generated but not validated  
**Required:** Validate token on all POST requests

**Implementation:**
```php
// In all forms, add CSRF token
<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

// In all POST handlers, validate token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('CSRF token validation failed');
}
```

---

#### 3. **Implement Rate Limiting** ⭐ **HIGH PRIORITY**

**Recommended:** Limit login attempts and form submissions

**Implementation:**
```php
// Track failed login attempts by IP
// Block IP after 5 failed attempts for 15 minutes
// Implement exponential backoff
```

---

#### 4. **Add Security Headers** ⭐ **MEDIUM PRIORITY**

**Create `.htaccess` file:**
```apache
# Security Headers
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "SAMEORIGIN"
Header set X-XSS-Protection "1; mode=block"
Header set Referrer-Policy "strict-origin-when-cross-origin"
Header set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; img-src 'self' data: https:; font-src 'self' https://cdnjs.cloudflare.com;"
Header set Permissions-Policy "geolocation=(self), microphone=(), camera=()"

# Prevent directory listing
Options -Indexes

# Protect sensitive files
<FilesMatch "\.(env|ini|log|sh|sql|conf)$">
    Require all denied
</FilesMatch>
```

---

#### 5. **Enhance Session Security** ⭐ **MEDIUM PRIORITY**

**Add to session configuration:**
```php
// At the start of each session
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Only over HTTPS
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);

// Session timeout (30 minutes)
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
    session_unset();
    session_destroy();
}
$_SESSION['LAST_ACTIVITY'] = time();

// IP address validation (optional, may cause issues with mobile)
if (!isset($_SESSION['USER_IP'])) {
    $_SESSION['USER_IP'] = $_SERVER['REMOTE_ADDR'];
} elseif ($_SESSION['USER_IP'] !== $_SERVER['REMOTE_ADDR']) {
    session_unset();
    session_destroy();
}
```

---

### **SHORT-TERM ACTIONS (Within 1-2 Weeks)** 🟡

#### 6. **Implement File Upload Security**

```php
// Validate MIME type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $_FILES['file']['tmp_name']);
finfo_close($finfo);

// Whitelist allowed MIME types
$allowedMimes = ['image/jpeg', 'image/png', 'application/pdf'];
if (!in_array($mimeType, $allowedMimes)) {
    die('Invalid file type');
}

// Rename uploaded files to prevent execution
$safeFilename = bin2hex(random_bytes(16)) . '.' . $extension;

// Store uploads outside web root or deny direct access
```

---

#### 7. **Add Logging and Monitoring**

```php
// Log all authentication attempts
// Log all failed requests
// Log all file uploads
// Monitor for suspicious patterns

// Example logging function
function securityLog($event, $details) {
    $logFile = '/var/log/sheener_security.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'];
    $message = "[$timestamp] [$ip] $event: $details\n";
    file_put_contents($logFile, $message, FILE_APPEND);
}
```

---

#### 8. **Implement CAPTCHA for Anonymous Submissions**

**Recommended:** Google reCAPTCHA v3 or hCaptcha

```php
// Add CAPTCHA to anonymous event form
// Validate CAPTCHA token on submission
// Prevent automated spam
```

---

### **LONG-TERM ACTIONS (Within 1-3 Months)** 🟢

#### 9. **Network Segmentation**
- Create separate VLAN for WiFi devices
- Implement firewall rules between WiFi and server VLAN
- Allow only specific ports (443 for HTTPS)

#### 10. **Implement VPN Access**
- Require VPN connection for mobile access
- Adds encryption layer even without HTTPS
- Better access control

#### 11. **Mobile Device Management (MDM)**
- Enforce device security policies
- Require device encryption
- Remote wipe capability for lost devices

#### 12. **Web Application Firewall (WAF)**
- Install ModSecurity for Apache
- Configure OWASP Core Rule Set
- Block common attack patterns

#### 13. **Database Encryption**
- Encrypt sensitive data at rest
- Use encrypted connections to database
- Implement field-level encryption for PII

---

## Network Configuration Recommendations

### **For IT Department:**

1. **WiFi Network Configuration:**
   - Use WPA3 encryption (or WPA2-Enterprise minimum)
   - Implement 802.1X authentication
   - Create separate SSID for employee devices
   - Enable WiFi client isolation

2. **Firewall Rules:**
   ```
   Allow: WiFi VLAN → Server IP (172.21.10.99) on port 443 (HTTPS only)
   Deny: WiFi VLAN → Server IP (172.21.10.99) on port 80 (HTTP)
   Deny: WiFi VLAN → All other internal resources (unless specifically needed)
   ```

3. **Network Monitoring:**
   - Enable IDS/IPS on WiFi network
   - Monitor for suspicious traffic patterns
   - Alert on anomalous behavior

4. **Access Control:**
   - MAC address filtering (optional, can be spoofed)
   - IP whitelisting for known devices
   - Time-based access restrictions

---

## Risk Assessment Summary

| Risk Category | Current Risk | With HTTPS | With All Measures |
|---------------|--------------|------------|-------------------|
| Data Interception | 🔴 Critical | 🟢 Low | 🟢 Low |
| Credential Theft | 🔴 Critical | 🟡 Medium | 🟢 Low |
| Session Hijacking | 🔴 Critical | 🟡 Medium | 🟢 Low |
| CSRF Attacks | 🟡 High | 🟡 High | 🟢 Low |
| Brute Force | 🟡 High | 🟡 High | 🟢 Low |
| File Upload Exploits | 🟠 Medium | 🟠 Medium | 🟢 Low |
| XSS Attacks | 🟠 Medium | 🟠 Medium | 🟢 Low |
| SQL Injection | 🟢 Low | 🟢 Low | 🟢 Low |

---

## Compliance Considerations

### **Pharmaceutical Industry Standards:**
- **FDA 21 CFR Part 11:** Electronic records and signatures
- **GxP Compliance:** Good practices for data integrity
- **ISO 27001:** Information security management
- **GDPR/Data Protection:** If handling personal data

**Recommendation:** Ensure all security measures align with regulatory requirements for pharmaceutical manufacturing.

---

## Implementation Priority

### **Phase 1: CRITICAL (Do NOT enable WiFi access without these)** 🔴
1. ✅ Implement HTTPS/TLS
2. ✅ Add security headers
3. ✅ Implement CSRF validation
4. ✅ Configure firewall rules (IT Department)

**Timeline:** 1-2 days  
**Effort:** Medium  
**Risk Reduction:** 70%

---

### **Phase 2: HIGH PRIORITY (Implement within 1 week)** 🟡
1. ✅ Implement rate limiting
2. ✅ Enhance session security
3. ✅ Add security logging
4. ✅ Improve file upload validation

**Timeline:** 3-5 days  
**Effort:** Medium  
**Risk Reduction:** 20%

---

### **Phase 3: MEDIUM PRIORITY (Implement within 1 month)** 🟢
1. ✅ Implement CAPTCHA
2. ✅ Add WAF (ModSecurity)
3. ✅ Network segmentation (IT)
4. ✅ Monitoring and alerting

**Timeline:** 2-4 weeks  
**Effort:** High  
**Risk Reduction:** 10%

---

## Conclusion

**Current State:** Your application has good foundational security (authentication, SQL injection protection, input validation), but **lacks critical protections for WiFi access**.

**Recommendation:** **DO NOT enable WiFi access until HTTPS is implemented.** Without encryption, all data (including passwords) will be transmitted in plain text over the WiFi network.

**Minimum Requirements Before WiFi Access:**
1. ✅ HTTPS/TLS encryption
2. ✅ Security headers
3. ✅ CSRF token validation
4. ✅ Firewall rules configured by IT
5. ✅ Rate limiting on authentication

**Estimated Time to Secure:** 2-3 days for critical measures

**Total Risk Reduction:** With all Phase 1 measures implemented, risk reduces from **CRITICAL** to **LOW-MEDIUM**.

---

## Next Steps

1. **Review this document with IT department**
2. **Prioritize HTTPS implementation**
3. **Create implementation plan with timeline**
4. **Test security measures in staging environment**
5. **Conduct security audit before production deployment**
6. **Train employees on security best practices**

---

## Contact & Support

For implementation assistance, consider:
- Internal IT security team
- External security consultant
- Penetration testing service (recommended after implementation)

---

**Document Version:** 1.0  
**Last Updated:** December 22, 2025  
**Next Review:** After Phase 1 implementation
