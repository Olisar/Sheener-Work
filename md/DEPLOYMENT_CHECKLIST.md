# 🚀 SHEEner Mobile Report - Quick Deployment Checklist

**Use this checklist when deploying to your company server**

---

## ✅ Pre-Deployment (Before Installation)

### **Server Preparation**
- [ ] Server running Windows Server 2016+ or Linux
- [ ] Apache 2.4+ installed and running
- [ ] PHP 7.4+ installed
- [ ] MySQL 5.7+ installed and running
- [ ] Required PHP extensions installed:
  - [ ] php_mysqli
  - [ ] php_json
  - [ ] php_fileinfo
- [ ] Apache modules enabled:
  - [ ] mod_rewrite
  - [ ] mod_headers
  - [ ] mod_ssl (if using HTTPS)

### **Network Configuration**
- [ ] Server IP address documented: `___________________`
- [ ] Firewall configured to allow HTTP (port 80)
- [ ] Firewall configured to allow HTTPS (port 443)
- [ ] DNS configured (if using domain name)

### **SSL Certificate (Required for Production)**
- [ ] SSL certificate obtained
- [ ] Certificate installed on server
- [ ] HTTPS working and tested
- [ ] Certificate expiry date noted: `___________________`

---

## 📦 Installation Steps

### **Step 1: File Upload**
- [ ] Uploaded `sheener` folder to web root
- [ ] Verified folder structure is correct
- [ ] All files present (check README.md for list)

### **Step 2: Database Setup**
- [ ] Database created: `sheener_db`
- [ ] Database user created: `sheener_user`
- [ ] User permissions granted
- [ ] Database credentials documented securely

### **Step 3: Configuration**
- [ ] Updated `php/config.php` with database credentials
- [ ] Updated `manifest.json` with correct paths
- [ ] Updated `service-worker.js` with correct paths
- [ ] Updated QR generator (if needed)

### **Step 4: Permissions**
- [ ] Set file permissions correctly
- [ ] Upload folder writable by web server
- [ ] Logs folder writable (if applicable)

---

## 🧪 Testing Phase

### **Desktop Testing**
- [ ] Accessed form via desktop browser
- [ ] No console errors (F12)
- [ ] Form loads correctly
- [ ] Can select categories and subcategories
- [ ] Form submission works
- [ ] Data appears in database

### **Mobile Testing (HTTP - Basic)**
- [ ] Generated QR code
- [ ] Scanned QR code with mobile device
- [ ] Form loads on mobile
- [ ] Can fill out form
- [ ] Form submits successfully
- [ ] GPS location captured

### **Mobile Testing (HTTPS - Full PWA)**
- [ ] Accessed via HTTPS URL
- [ ] "Install app" prompt appears
- [ ] Successfully installed app
- [ ] App icon on home screen
- [ ] App opens in standalone mode
- [ ] Offline mode tested:
  - [ ] Turned on Airplane Mode
  - [ ] Submitted report offline
  - [ ] Report saved locally
  - [ ] Turned off Airplane Mode
  - [ ] Report synced automatically

### **Feature Testing**
- [ ] GPS location working
- [ ] Camera/file upload working
- [ ] All form fields working
- [ ] Validation working
- [ ] Error messages displaying
- [ ] Success messages displaying
- [ ] Status bar updating correctly

---

## 🔒 Security Checklist

- [ ] HTTPS enabled and working
- [ ] SSL certificate valid and trusted
- [ ] Database credentials secure
- [ ] File upload validation in place
- [ ] SQL injection protection enabled
- [ ] XSS protection enabled
- [ ] CSRF protection enabled (if applicable)
- [ ] Server security updates applied

---

## 📱 User Deployment

### **QR Code Distribution**
- [ ] QR codes generated with HTTPS URL
- [ ] QR codes printed/distributed
- [ ] QR codes posted in visible locations
- [ ] Instructions provided to users

### **User Training**
- [ ] Users shown how to scan QR code
- [ ] Users shown how to install app
- [ ] Users shown how to submit reports
- [ ] Users shown offline functionality
- [ ] Support contact information provided

---

## 📊 Monitoring Setup

### **Logging**
- [ ] Apache access log location noted
- [ ] Apache error log location noted
- [ ] PHP error log location noted
- [ ] Log rotation configured

### **Monitoring**
- [ ] Server monitoring configured
- [ ] Application monitoring configured
- [ ] Alert thresholds set
- [ ] Notification recipients configured

### **Backups**
- [ ] Database backup scheduled
- [ ] File backup scheduled
- [ ] Backup restoration tested
- [ ] Backup retention policy defined

---

## 📝 Documentation

- [ ] Server details documented
- [ ] Database credentials stored securely
- [ ] SSL certificate details documented
- [ ] Deployment date recorded: `___________________`
- [ ] Deployed by: `___________________`
- [ ] README.md reviewed
- [ ] Support contacts documented

---

## 🎯 Go-Live Checklist

### **Final Verification**
- [ ] All tests passed
- [ ] No critical errors in logs
- [ ] Performance acceptable
- [ ] Security scan passed
- [ ] Backup verified

### **Communication**
- [ ] Users notified of new system
- [ ] Training materials distributed
- [ ] Support team briefed
- [ ] Escalation process defined

### **Post-Deployment**
- [ ] Monitor for first 24 hours
- [ ] Check logs for errors
- [ ] Verify submissions working
- [ ] Collect user feedback
- [ ] Address any issues immediately

---

## 📞 Support Information

### **Technical Contacts**

**Server Administrator:**
- Name: `___________________`
- Email: `___________________`
- Phone: `___________________`

**Database Administrator:**
- Name: `___________________`
- Email: `___________________`
- Phone: `___________________`

**Application Support:**
- Name: `___________________`
- Email: `___________________`
- Phone: `___________________`

### **Emergency Contacts**

**After Hours:**
- Contact: `___________________`
- Phone: `___________________`

**Escalation:**
- Contact: `___________________`
- Phone: `___________________`

---

## 🔧 Common Issues Quick Reference

| Issue | Quick Fix |
|-------|-----------|
| Install option not showing | Verify HTTPS is working |
| Form not submitting | Check database connection |
| Subcategory not working | Clear browser cache, check console |
| GPS not working | Verify HTTPS, check permissions |
| Can't access from mobile | Check firewall, verify IP address |
| Service worker not registering | Verify HTTPS, check paths in service-worker.js |

---

## ✅ Sign-Off

### **Deployment Completed**

**Date:** `___________________`

**Deployed By:** `___________________`

**Signature:** `___________________`

### **Testing Verified**

**Tested By:** `___________________`

**Date:** `___________________`

**Signature:** `___________________`

### **Approved for Production**

**Approved By:** `___________________`

**Date:** `___________________`

**Signature:** `___________________`

---

## 📋 Notes

Use this space for deployment-specific notes:

```
_________________________________________________________________

_________________________________________________________________

_________________________________________________________________

_________________________________________________________________

_________________________________________________________________
```

---

**Document Version:** 1.0  
**Last Updated:** December 2025  
**Next Review Date:** `___________________`
