---
url: /docs/checks/system.md
---
# System & Hosting Checks

System and hosting checks examine your PHP environment, server configuration, and hosting infrastructure. These form the foundation of a healthy Joomla site.

**Total checks in this category: 33**

## PHP Configuration (8 checks)

### PHP Version

Ensures PHP version meets Joomla 5 minimum requirements (8.1+).

* **Good**: PHP 8.1.0 or higher
* **Critical**: Below PHP 8.1.0

**Why it matters**: Joomla 5 requires PHP 8.1+ for security, performance, and compatibility.

### Memory Limit

Checks PHP memory limit is sufficient.

* **Good**: 256MB or higher
* **Warning**: Below 256MB
* **Critical**: Below 128MB

**Why it matters**: Insufficient memory causes errors, failed updates, and backup failures.

### Max Execution Time

Verifies scripts have enough time to complete.

* **Good**: 60 seconds or more
* **Warning**: Below 60 seconds
* **Critical**: Below 30 seconds

**Why it matters**: Long operations (backups, updates) need adequate execution time.

### Max Input Time

Checks time allowed for POST/upload processing.

* **Good**: 60 seconds or more
* **Warning**: Below 60 seconds

**Why it matters**: Large file uploads require sufficient input processing time.

### Max Input Variables

Ensures enough POST variables can be processed.

* **Good**: 2000 or more
* **Warning**: Below 2000
* **Critical**: Below 1000

**Why it matters**: Complex forms and extensions need adequate variable limits.

### Post Max Size

Checks maximum POST request size.

* **Good**: 32MB or higher
* **Warning**: Below 32MB
* **Critical**: Below 8MB

**Why it matters**: Affects maximum file upload sizes and form submissions.

### Upload Max Filesize

Verifies maximum file upload size.

* **Good**: 32MB or higher
* **Warning**: Below 32MB
* **Critical**: Below 8MB

**Why it matters**: Users need to upload images, documents, and media files.

### Output Buffering

Checks if output buffering is enabled.

* **Good**: Enabled
* **Warning**: Disabled

**Why it matters**: Prevents "headers already sent" errors.

## Required PHP Extensions (11 checks)

### JSON Extension

Verifies JSON support is available.

* **Good**: Installed and enabled
* **Critical**: Missing or disabled

**Why it matters**: Required for Joomla core functionality and modern web APIs.

### SimpleXML Extension

Checks XML parsing support.

* **Good**: Installed and enabled
* **Critical**: Missing or disabled

**Why it matters**: Required for Joomla XML manifests and configuration files.

### DOM Extension

Verifies DOM manipulation support.

* **Good**: Installed and enabled
* **Critical**: Missing or disabled

**Why it matters**: Essential for HTML/XML processing in Joomla core.

### PDO MySQL Extension

Checks database connectivity support.

* **Good**: Installed and enabled
* **Critical**: Missing or disabled

**Why it matters**: Required for all database operations in Joomla 5.

### GD or Imagick

Verifies image processing capability.

* **Good**: GD or Imagick installed
* **Warning**: Neither installed

**Why it matters**: Needed for thumbnail generation and image manipulation.

### Zip Extension

Checks archive handling support.

* **Good**: Installed and enabled
* **Critical**: Missing or disabled

**Why it matters**: Required for extension installation and updates.

### Mbstring Extension

Verifies multibyte string support.

* **Good**: Installed and enabled
* **Warning**: Missing or disabled

**Why it matters**: Essential for proper handling of international characters.

### Intl Extension

Checks internationalization support.

* **Good**: Installed and enabled
* **Warning**: Missing or disabled

**Why it matters**: Required for proper date/time/currency formatting across languages.

### cURL Extension

Verifies HTTP request capability.

* **Good**: Installed and enabled
* **Warning**: Missing or disabled

**Why it matters**: Used for remote API calls and update checks.

### OpenSSL Extension

Checks SSL/TLS support.

* **Good**: Installed and enabled
* **Critical**: Missing or disabled

**Why it matters**: Required for HTTPS connections and secure communications.

### Fileinfo Extension

Verifies MIME type detection.

* **Good**: Installed and enabled
* **Warning**: Missing or disabled

**Why it matters**: Helps prevent malicious file uploads by detecting true file types.

## Server Environment (6 checks)

### Disk Space Available

Monitors available disk space.

* **Good**: More than 20% free or 1GB+
* **Warning**: 10-20% free
* **Critical**: Less than 10% free or under 500MB

**Why it matters**: Insufficient disk space prevents backups, uploads, and log writing.

### Temp Directory Writable

Checks if temporary directory is writable.

* **Good**: Writable
* **Critical**: Not writable

**Why it matters**: Required for file uploads, session storage, and temp operations.

### Server Time Sync

Verifies server clock accuracy.

* **Good**: Within 5 minutes of actual time
* **Warning**: 5-30 minutes off
* **Critical**: More than 30 minutes off

**Why it matters**: Accurate time is critical for SSL certificates, sessions, and scheduling.

### PHP SAPI Type

Checks PHP execution interface.

* **Good**: FPM, CGI/FastCGI, or LiteSpeed
* **Warning**: Apache module or CLI

**Why it matters**: Modern SAPI types offer better performance and security.

### PHP End-of-Life Status

Checks if your PHP version is approaching or past its end-of-life date.

* **Good**: PHP under active support with 90+ days remaining
* **Warning**: PHP in security-only support or nearing EOL
* **Critical**: PHP past end-of-life (no security patches)

**Why it matters**: PHP versions have defined support lifecycles:

* **Active support**: Bug fixes and security patches
* **Security support**: Security patches only
* **End of life**: No patches at all

Running an EOL PHP version means your server has known, unpatched security vulnerabilities.

**How to fix**: Contact your hosting provider to upgrade PHP to a supported version.

### OPcache Status

Verifies PHP opcache is enabled.

* **Good**: Enabled and active
* **Warning**: Disabled

**Why it matters**: Opcache dramatically improves PHP performance.

### Realpath Cache Size

Checks realpath cache configuration.

* **Good**: 4MB or higher
* **Warning**: Below 4MB

**Why it matters**: Larger cache reduces filesystem overhead.

## Log & Scheduler (3 checks)

### Log File Size

Monitors the total size of files in Joomla's log directory.

* **Good**: Log directory ≤100MB
* **Warning**: Log directory 100-500MB
* **Critical**: Log directory >500MB

**Why it matters**: Log files grow continuously and can quickly consume disk space:

* Runaway errors can generate gigabytes of logs in hours
* Large log files slow down analysis and debugging
* Full disks cause site failures, database corruption, and data loss
* Old logs may contain sensitive information

**How to manage**:

1. Configure Joomla's Task Scheduler for log rotation
2. Regularly review and archive old logs
3. Investigate recurring errors that fill logs
4. Consider external log management solutions

### Overdue Scheduled Tasks

Identifies tasks that haven't run on schedule.

* **Good**: No overdue tasks
* **Warning**: 1-5 overdue tasks
* **Critical**: More than 5 overdue tasks

**Why it matters**: Overdue tasks indicate scheduler problems or server issues.

### Failed Scheduled Tasks

Detects tasks that have failed.

* **Good**: No failed tasks
* **Warning**: 1-3 recent failures
* **Critical**: More than 3 failures or repeated failures

**Why it matters**: Failed tasks may indicate configuration problems or errors.

## Common Issues & Solutions

### Low Memory Limit

**Symptoms**: Extension installation fails, white screens, timeouts

**Solutions**:

1. Contact hosting provider to increase PHP memory limit
2. Add to `.htaccess`: `php_value memory_limit 256M`
3. Add to `php.ini`: `memory_limit = 256M`
4. Upgrade hosting plan if current plan restricts PHP settings

### Missing PHP Extensions

**Symptoms**: Errors during installation, missing features

**Solutions**:

1. Contact hosting provider to enable required extensions
2. Use hosting control panel (if available) to enable extensions
3. For VPS/dedicated: Install via package manager
   * Ubuntu/Debian: `apt-get install php8.1-{extension}`
   * CentOS/RHEL: `yum install php81-{extension}`

### Disk Space Issues

**Symptoms**: Failed backups, upload errors, slow performance

**Solutions**:

1. Clean up old backups and log files
2. Remove unused media files
3. Empty trash folders
4. Upgrade hosting plan for more storage
5. Use external storage for backups

### Outdated PHP Version

**Symptoms**: Compatibility warnings, security alerts

**Solutions**:

1. Contact hosting provider to upgrade PHP version
2. Use hosting control panel to switch PHP version
3. Test on staging site first before production upgrade
4. Review extension compatibility before upgrading

## Next Steps

* [Database Checks](./database.md) - Monitor database health
* [Security Checks](./security.md) - Evaluate security settings
* [Performance Checks](./performance.md) - Optimize site speed
