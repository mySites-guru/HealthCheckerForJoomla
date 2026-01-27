# Health Checker for Joomla - Definitive Check List

This is the **canonical source** for all health checks. Any changes to checks should be made here first.

**Total: 128 checks across 8+ categories**

> **Note**: No SSL/certificate checks - these are infrastructure-level concerns handled by hosting.

---

## System & Hosting (33 checks)

### PHP Configuration
- PHP Version (8.1+ required)
- PHP End of Life Status
- Memory Limit (256M+ recommended)
- Max Execution Time
- Max Input Time
- Max Input Variables
- Post Max Size
- Upload Max Filesize
- Output Buffering
- Realpath Cache Size
- PHP SAPI Type

### Required PHP Extensions
- JSON Extension
- SimpleXML Extension
- DOM Extension
- PDO MySQL Extension
- GD or Imagick
- Zip Extension
- Mbstring Extension
- Intl Extension
- cURL Extension
- OpenSSL Extension
- Fileinfo Extension
- EXIF Extension

### Server Environment
- Disk Space Available
- Temp Directory Writable
- Server Time Sync (with database comparison)
- OPcache Status
- Apache Modules
- Log File Size
- Mail Function
- Session Save Path

### Scheduler
- Overdue Scheduled Tasks
- Failed Scheduled Tasks

---

## Database (17 checks)

### Connection & Server
- Database Connection
- Database Server Version
- Database User Privileges
- Connection Charset

### Table Health
- Table Engine Consistency (InnoDB/MEMORY)
- Table Charset/Collation
- Table Status (no corruption)
- Auto-increment Headroom
- Orphaned Tables Detection (with SQL file parsing)
- Index Usage

### Database Configuration
- SQL Mode Compatibility
- Max Allowed Packet
- Wait Timeout
- Table Prefix Set
- Database Size
- Slow Query Log
- Transaction Isolation Level

---

## Security (20 checks)

### Security Headers
- X-Frame-Options (Deprecated - use CSP frame-ancestors)
- Content-Security-Policy
- CORS Configuration
- HTTPS Redirect

### File System Security
- configuration.php Permissions
- .htaccess Protection

### Joomla Security Settings
- Debug Mode Disabled
- Error Reporting Level
- Force SSL
- Default Secret Key Set
- Session Handler Configured
- Session Lifetime

### Authentication Security
- Two-Factor Authentication Available
- Password Policy Configured
- Admin Username Check

### Privacy & API
- Privacy Dashboard
- reCAPTCHA/CAPTCHA
- API Authentication
- Action Logs Enabled
- Mailer Security

---

## Users (12 checks)

### Admin User Security
- Super Admin Count (warn if >5)
- Admin Email Unique
- Last Login (90 day warning)

### User Account Health
- Inactive Users (365 days)
- Blocked Users Count
- Duplicate Email Addresses
- Password Expiry

### User Configuration
- User Registration Settings
- Default User Group Assignment
- User Groups Configured
- User Fields
- User Notes

---

## Extensions (13 checks)

### Core Updates
- Joomla Core Version
- Update Sites Accessible

### Extension Health
- Extensions with Updates Available
- Disabled Extensions Present
- Legacy Extensions (2+ years old, non-core only)
- Language Packs

### Plugin Status
- Plugin Order
- Cache Plugin
- Search Plugins

### Module Status
- Module Positions
- Unused Modules

### Template Status
- Template Check
- Template Overrides

---

## Performance (10 checks)

### Caching
- System Cache Enabled
- Page Cache for Guests
- Database Query Cache
- Browser Cache Headers

### Compression & Optimization
- Gzip Compression Enabled
- Image Optimization
- Lazy Loading Enabled
- Media Manager Thumbnails

### Search & Redirects
- Smart Search Index
- Redirect Chains Detection

---

## SEO (12 checks)

### Meta Information
- Site Meta Description Set
- Meta Keywords (deprecated warning)

### URL Structure
- SEF URLs Enabled
- Canonical URLs Configured
- Robots.txt Present
- XML Sitemap

### Social Media
- Open Graph Tags
- Facebook Open Graph
- Twitter/X Cards

### Content SEO
- Images Missing Alt Text
- Broken Internal Links
- Structured Data

---

## Content Quality (11 checks)

### Content Health
- Orphaned Articles (not in menu)
- Uncategorized Content
- Draft Articles
- Empty Articles
- Trashed Content
- Archived Content
- Scheduled Content
- Expired Content
- Articles in Unpublished Categories

### Menu & Navigation
- Menu Orphans
- Category Depth

---

## Summary

| Category | Checks |
|----------|--------|
| System & Hosting | 33 |
| Database | 18 |
| Security | 20 |
| Users | 12 |
| Extensions | 13 |
| Performance | 10 |
| SEO | 12 |
| Content Quality | 11 |
| **Total** | **129** |
