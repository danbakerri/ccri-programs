# Security Configuration Guide

This guide explains how to secure the CCRI Program Pages system against unauthorized data access.

## Overview

The system has three areas that need protection:

1. **Workforce JSON data** - Block direct access to `.json` files
2. **Cache directory** - Block all direct web access  
3. **Management interface** - Protect with SSO (already configured)

---

## 1. Protect Workforce JSON Files

### Location
`/workforce/manage-programs/workforce-programs.json`

### Risk
Without protection, anyone can directly access:
```
https://www.ccri.edu/workforce/manage-programs/workforce-programs.json
```

### Solution

**For Apache Servers:**

Upload `workforce/.htaccess` to `/workforce/manage-programs/.htaccess`

**For IIS Servers:**

Upload `workforce/web.config` to `/workforce/manage-programs/web.config`

### What This Does
- ❌ Blocks direct browser access to `.json` files
- ✅ PHP scripts can still read/write the file
- ✅ Only `get-programs.php` can serve the data

### Testing

**Before protection:**
```bash
curl https://www.ccri.edu/workforce/manage-programs/workforce-programs.json
# Returns: JSON data (BAD)
```

**After protection:**
```bash
curl https://www.ccri.edu/workforce/manage-programs/workforce-programs.json
# Returns: 403 Forbidden (GOOD)

curl https://www.ccri.edu/workforce/manage-programs/get-programs.php
# Returns: JSON data (GOOD - served via PHP)
```

---

## 2. Protect Cache Directory

### Location
`/_resources-2025/php/cache/`

### Risk
Without protection, anyone can directly access cached files:
```
https://www.ccri.edu/_resources-2025/php/cache/program_abc123.html
https://www.ccri.edu/_resources-2025/php/cache/program-list.json
```

### Solution

**For Apache Servers:**

Upload `cache-security/.htaccess` to `/_resources-2025/php/cache/.htaccess`

**For IIS Servers:**

Upload `cache-security/web.config` to `/_resources-2025/php/cache/web.config`

### What This Does
- ❌ Blocks ALL direct web access to cache directory
- ✅ PHP scripts can still read/write cache files
- ✅ Only `fetch-program-content.php` and `program-list.php` serve cached data

### Testing

**Before protection:**
```bash
curl https://www.ccri.edu/_resources-2025/php/cache/program-list.json
# Returns: Cached JSON (BAD)
```

**After protection:**
```bash
curl https://www.ccri.edu/_resources-2025/php/cache/program-list.json
# Returns: 403 Forbidden (GOOD)

curl https://www.ccri.edu/_resources-2025/php/program-list.php
# Returns: JSON data (GOOD - served via PHP)
```

---

## 3. Management Interface Protection

### Location
`/workforce/manage-programs/`

### Risk
Without SSO, anyone can add/edit/delete workforce programs.

### Solution

**Already Configured** - Your IT team should have this in place.

**Verify it works:**
```bash
curl https://www.ccri.edu/workforce/manage-programs/
# Should return: 401 Unauthorized or redirect to SSO login
```

If it returns the HTML page without authentication, SSO is NOT configured!

---

## File Summary

```
Repository Files                    Upload Location
====================               =====================

workforce/.htaccess          →     /workforce/manage-programs/.htaccess
workforce/web.config         →     /workforce/manage-programs/web.config

cache-security/.htaccess     →     /_resources-2025/php/cache/.htaccess
cache-security/web.config    →     /_resources-2025/php/cache/web.config
```

**Choose Apache (.htaccess) OR IIS (web.config) based on your server type.**

---

## Installation Steps

### Step 1: Identify Server Type

**Apache:**
- Uses .htaccess files
- Common on Linux servers
- Check: Does your server have .htaccess files in other directories?

**IIS (Windows Server):**
- Uses web.config files
- Common on Windows servers
- Check: Does your server have web.config files in other directories?

### Step 2: Upload Configuration Files

**For Apache:**
```bash
# Via FTP/SMB
/workforce/manage-programs/.htaccess     ← Upload this
/_resources-2025/php/cache/.htaccess     ← Upload this
```

**For IIS:**
```bash
# Via FTP/SMB
/workforce/manage-programs/web.config    ← Upload this
/_resources-2025/php/cache/web.config    ← Upload this
```

### Step 3: Test Protection

**Test workforce JSON:**
```bash
# Should fail (403)
curl https://www.ccri.edu/workforce/manage-programs/workforce-programs.json

# Should work (returns data via PHP)
curl https://www.ccri.edu/workforce/manage-programs/get-programs.php
```

**Test cache directory:**
```bash
# Should fail (403)
curl https://www.ccri.edu/_resources-2025/php/cache/program-list.json

# Should work (returns data via PHP)
curl https://www.ccri.edu/_resources-2025/php/program-list.php
```

### Step 4: Verify PHP Still Works

1. Visit your management interface (should prompt for SSO login)
2. Add a test program
3. Check main directory - test program should appear
4. Delete test program

If all works ✅ - Security is properly configured!

---

## Common Issues

### Issue: 500 Internal Server Error

**Cause:** Web server doesn't support .htaccess or web.config syntax

**Fix:**
1. Check server error logs
2. Verify correct file type for your server (Apache vs IIS)
3. Check file permissions (should be readable by web server)

### Issue: PHP Scripts Can't Read JSON File

**Symptom:** Management interface shows "Failed to read programs data"

**Cause:** Web server config is blocking PHP too (misconfiguration)

**Fix:**
- The configs provided ONLY block HTTP access, not file system access
- Check web server error logs for details
- Verify file permissions haven't changed

### Issue: Cache Files Not Being Created

**Symptom:** Always shows "FRESH FETCH" never "CACHE HIT"

**Cause:** Unrelated to security - this is a permissions issue

**Fix:**
```bash
# Set cache directory permissions
chmod 755 /_resources-2025/php/cache
chown www-data:www-data /_resources-2025/php/cache  # Linux
icacls "C:\path\to\cache" /grant "IIS_IUSRS:(OI)(CI)M"  # Windows
```

---

## Security Best Practices

### ✅ Do:
- Block direct access to data files
- Protect management interfaces with SSO
- Keep backups outside web root
- Review server logs regularly
- Update PHP and web server software

### ❌ Don't:
- Store sensitive data in JSON files (use database for PII)
- Rely on "security through obscurity"
- Disable security for "testing" and forget to re-enable
- Share SSO credentials

---

## Advanced Security (Optional)

### Rate Limiting

Prevent abuse of PHP endpoints:

**Apache (mod_evasive):**
```apache
<Location "/workforce/manage-programs">
    DOSHashTableSize 3097
    DOSPageCount 10
    DOSSiteCount 50
    DOSPageInterval 1
    DOSSiteInterval 1
</Location>
```

**IIS (Dynamic IP Restrictions):**
Enable in IIS Manager → Dynamic IP Restrictions

### HTTPS Only

**Apache:**
```apache
# Force HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

**IIS:**
Enable "Require SSL" in IIS Manager

### Content Security Policy

Add to PHP files:
```php
header("Content-Security-Policy: default-src 'self'");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
```

---

## Monitoring

### What to Monitor

**Watch for in server logs:**
- Multiple 403 errors (attempted data access)
- Failed SSO attempts (unauthorized access attempts)
- Unusual POST requests to save-program.php
- High volume requests to get-programs.php

**Set up alerts for:**
- Repeated 403 errors from same IP
- Failed authentication attempts
- JSON file modifications outside business hours

---

## Incident Response

**If you suspect unauthorized access:**

1. **Immediately:** Block the IP at firewall level
2. **Check logs:** Identify what was accessed
3. **Review data:** Check workforce-programs.json for unauthorized changes
4. **Restore backup** if data was modified
5. **Update passwords** if SSO might be compromised
6. **Notify IT security team**

---

## Questions for Your IT Team

✅ **Resolved by this guide:**
- ✓ JSON files publicly accessible → Now blocked
- ✓ Cache files exposed → Now blocked
- ✓ Need security for management interface → SSO configured

❓ **Additional questions they might have:**

**Q: Should we move JSON files outside web root?**  
A: Optional but more secure. See "Moving Data Files" section in workforce/README.md

**Q: Should we use a database instead?**  
A: For <100 programs, JSON is fine. Scale to database if you exceed 500 programs or need advanced features.

**Q: Do we need encryption?**  
A: Data is public info (program names/URLs). HTTPS encrypts transmission. No need to encrypt JSON file itself.

**Q: What about GDPR/privacy?**  
A: No PII stored. All data is public catalog information. No privacy concerns.

---

## Support

For security questions:
1. Check this guide first
2. Review server error logs
3. Test with curl commands provided
4. Contact your IT security team
5. Review MAINTENANCE.md for ongoing security tasks

---

## Version History

### v1.0 (March 2026)
- Initial security configuration
- Apache and IIS support
- Workforce JSON protection
- Cache directory protection
