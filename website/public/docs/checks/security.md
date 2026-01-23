---
url: /docs/checks/security.md
---
# Security Checks

Security checks evaluate your site's protection against common vulnerabilities and attacks. These checks are critical for protecting your site and user data.

**Total checks in this category: 21**

## Security Headers (6 checks)

### X-Frame-Options (Deprecated)

Prevents clickjacking attacks.

* **Good**: Set to `DENY` or `SAMEORIGIN`
* **Warning**: Not set

**Why it matters**: Prevents your site from being embedded in iframes on malicious sites.

**Deprecation Note**: X-Frame-Options is considered deprecated in favor of the Content-Security-Policy `frame-ancestors` directive, which provides more flexibility (e.g., allowing specific domains). However, X-Frame-Options is still recommended for backwards compatibility with older browsers that don't support CSP frame-ancestors. For best protection, use both headers together.

**How to fix** (Apache `.htaccess`):

```apache
Header always set X-Frame-Options "SAMEORIGIN"
Header always set Content-Security-Policy "frame-ancestors 'self'"
```

### X-Content-Type-Options

Prevents MIME type sniffing.

* **Good**: Set to `nosniff`
* **Warning**: Not set

**Why it matters**: Stops browsers from interpreting files as different MIME types than declared.

**How to fix**:

```apache
Header always set X-Content-Type-Options "nosniff"
```

### X-XSS-Protection

Enables browser XSS filtering.

* **Good**: Set to `1; mode=block`
* **Warning**: Not set

**Why it matters**: Provides additional XSS attack protection in older browsers.

**How to fix**:

```apache
Header always set X-XSS-Protection "1; mode=block"
```

### Content-Security-Policy

Controls resource loading sources.

* **Good**: Configured with proper directives
* **Warning**: Not set or too permissive

**Why it matters**: Prevents XSS attacks by controlling which resources can load.

**Example**:

```apache
Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'"
```

### Referrer-Policy

Controls referrer information.

* **Good**: Set to `strict-origin-when-cross-origin` or stricter
* **Warning**: Not set

**Why it matters**: Prevents leaking sensitive URL information to third parties.

**How to fix**:

```apache
Header always set Referrer-Policy "strict-origin-when-cross-origin"
```

### Permissions-Policy

Controls browser features.

* **Good**: Configured
* **Warning**: Not set

**Why it matters**: Restricts access to sensitive browser APIs.

**Example**:

```apache
Header always set Permissions-Policy "camera=(), microphone=(), geolocation=()"
```

## File System Security (7 checks)

### configuration.php Permissions

Checks config file is read-only.

* **Good**: 444 or 440
* **Warning**: 644
* **Critical**: 777 or world-writable

**Why it matters**: Writable config files can be modified by attackers.

**How to fix**:

```bash
chmod 444 configuration.php
```

### configuration.php Location

Verifies config is in root directory.

* **Good**: Located in site root
* **Critical**: Accessible via web browser

**Why it matters**: Config file contains database credentials.

### Writable Directories Audit

Identifies web-writable directories.

* **Good**: Only necessary directories writable
* **Warning**: Excessive writable permissions

**Why it matters**: Writable directories are targets for malware uploads.

**Recommended writable**:

* `/images`
* `/cache`
* `/tmp`
* `/administrator/cache`
* `/logs` (if not moved outside webroot)

### Index.html in Directories

Checks for directory listing protection.

* **Good**: index.html files present in all directories
* **Warning**: Missing in some directories

**Why it matters**: Prevents directory browsing that could expose file structure.

**How to fix**:

```bash
find . -type d -exec touch {}/index.html \;
```

### .htaccess Present

Verifies .htaccess file exists and is configured.

* **Good**: Present with proper rules
* **Warning**: Missing or misconfigured

**Why it matters**: Provides Apache-level security rules.

### Installation Directory Removed

Checks if installation folder is deleted.

* **Good**: Removed
* **Critical**: Still exists

**Why it matters**: Installation directory can be used to reinstall and compromise your site.

**How to fix**:

```bash
rm -rf installation/
```

### Backup Files Exposed

Scans for publicly accessible backup files.

* **Good**: No exposed backups
* **Critical**: Backup files accessible via web

**Why it matters**: Backups contain database dumps with sensitive data.

**Common backup file patterns**:

* `*.sql`, `*.sql.gz`, `*.zip`
* `backup-*`, `*-backup.*`
* `.bak`, `.old`, `.backup`

## Joomla Security Settings (5 checks)

### Debug Mode Disabled

Verifies debugging is off in production.

* **Good**: Disabled
* **Critical**: Enabled in production

**Why it matters**: Debug mode exposes database queries, file paths, and system information.

**How to fix**: System → Global Configuration → System → Debug System: No

### Error Reporting Level

Checks error display settings.

* **Good**: None or Simple
* **Warning**: Maximum or Development

**Why it matters**: Detailed errors expose system information to attackers.

**How to fix**: System → Global Configuration → Server → Error Reporting: None

### FTP Layer Disabled

Verifies FTP layer is not in use.

* **Good**: Disabled
* **Warning**: Enabled

**Why it matters**: FTP credentials stored in config are insecure; use SFTP instead.

**How to fix**: Remove FTP settings from Global Configuration → Server

### Secret Key Set

Checks if secret key is configured.

* **Good**: Strong random secret set
* **Critical**: Empty or default value

**Why it matters**: Used for encrypting sensitive data and tokens.

**Located in**: `configuration.php` → `$secret`

### Database Password Strength

Evaluates database password complexity.

* **Good**: Strong password (16+ chars, mixed case, numbers, symbols)
* **Warning**: Weak password

**Why it matters**: Weak passwords allow database compromise.

**Best practices**:

* Minimum 16 characters
* Mix of uppercase, lowercase, numbers, symbols
* Avoid dictionary words
* Unique (not reused)

## Authentication & Logging (3 checks)

### Two-Factor Authentication Available

Checks if 2FA is enabled for Super Admins.

* **Good**: All Super Admins use 2FA
* **Warning**: Some Super Admins without 2FA
* **Critical**: No Super Admins use 2FA

**Why it matters**: 2FA prevents account compromise even if password is stolen.

**How to enable**: User Menu → Edit Account → Two Factor Authentication

### Action Logs Enabled

Verifies Action Logs system plugin is enabled.

* **Good**: Action logs enabled and recording
* **Warning**: Action logs disabled

**Why it matters**: Action logs track administrative actions for security auditing and compliance.

**How to enable**: System → Plugins → System - Action Logs → Enable

### User Actions Log

Verifies user activity logging is enabled for security monitoring.

* **Good**: User Actions Log plugin enabled and Action Log plugins configured
* **Warning**: User Actions Log or Action Log plugins disabled

**Why it matters**: The User Actions Log plugin records important user activities including logins, logouts, and other security-relevant events. Without this logging, you cannot detect suspicious activity, investigate security incidents, or maintain an audit trail of user actions on your site.

**What gets logged**:

* User logins and logouts
* Failed login attempts
* Password changes
* User account modifications

**How to enable**:

1. Go to System → Plugins
2. Enable "User - User Actions Log" plugin
3. Ensure Action Log plugins are also enabled
4. Review logs at Users → User Actions Log

## Common Security Issues & Solutions

### Security Headers Missing

**Symptoms**: Vulnerability scanner warnings

**Solutions**:

1. **Apache** - Add to `.htaccess`:
   ```apache
   <IfModule mod_headers.c>
       Header always set X-Frame-Options "SAMEORIGIN"
       Header always set X-Content-Type-Options "nosniff"
       Header always set X-XSS-Protection "1; mode=block"
       Header always set Referrer-Policy "strict-origin-when-cross-origin"
   </IfModule>
   ```

2. **Nginx** - Add to config:
   ```nginx
   add_header X-Frame-Options "SAMEORIGIN" always;
   add_header X-Content-Type-Options "nosniff" always;
   add_header X-XSS-Protection "1; mode=block" always;
   add_header Referrer-Policy "strict-origin-when-cross-origin" always;
   ```

### Insecure File Permissions

**Symptoms**: Files modified by unauthorized users

**Solutions**:

```bash
# Files: 644 (owner read/write, group/world read)
find . -type f -exec chmod 644 {} \;

# Directories: 755 (owner all, group/world read/execute)
find . -type d -exec chmod 755 {} \;

# Configuration: 444 (read-only for all)
chmod 444 configuration.php

# Writable directories: 755 with web server as owner
chown -R www-data:www-data images/ cache/ tmp/
```

### Installation Directory Still Present

**Symptoms**: Security scanner warnings

**Solution**:

```bash
rm -rf installation/
```

Or use Joomla's post-installation cleanup tool.

### Debug Mode Enabled

**Symptoms**: Database queries visible, system paths exposed

**Solutions**:

1. Global Configuration → System → Debug System: No
2. Global Configuration → Server → Error Reporting: None
3. Check `.htaccess` doesn't enable display\_errors

### Weak Database Password

**Symptoms**: Security audit failures

**Solutions**:

1. Generate strong password:
   ```bash
   openssl rand -base64 32
   ```
2. Update database user:
   ```sql
   SET PASSWORD FOR 'dbuser'@'localhost' = PASSWORD('newpassword');
   FLUSH PRIVILEGES;
   ```
3. Update `configuration.php`:
   ```php
   public $password = 'newpassword';
   ```

## Security Best Practices

### Regular Updates

* Update Joomla core immediately when releases available
* Update extensions within 1 week
* Subscribe to Joomla security announcements
* Test updates on staging first

### Access Control

* Use strong passwords (16+ characters)
* Enable 2FA for all admin accounts
* Limit number of Super Admin accounts
* Use principle of least privilege
* Change default admin URL (optional)

### File System

* Move logs outside web root
* Remove unused extensions completely
* Delete old backup files
* Protect admin directory with password
* Use secure file permissions

### Monitoring

* Review security logs weekly
* Monitor failed login attempts
* Check file integrity regularly
* Use security scanner monthly
* Monitor for malware

### Backup & Recovery

* Daily automated backups
* Store backups off-site
* Test restoration process
* Keep multiple versions
* Encrypt backup files

### Server Security

* Keep server OS updated
* Use firewall (iptables, CSF)
* Install malware scanner
* Enable ModSecurity WAF
* Use intrusion detection (Fail2ban)

## Next Steps

* [Users Checks](./users.md) - Review user account security
* [Extensions Checks](./extensions.md) - Check extension security
* [System Checks](./system.md) - Verify server security
