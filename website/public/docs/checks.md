---
url: /docs/checks.md
---
# Health Checks Reference

This page provides a complete reference of all health checks included in Health Checker for Joomla. Each check is designed to identify potential issues with your Joomla installation across eight categories.

## Status Indicators

* 🟢 **Good** - Everything is optimal, no action needed
* 🟡 **Warning** - Should be addressed, but site still functions
* 🔴 **Critical** - Immediate action required, site may be broken or compromised

## Categories

* [Content Quality](#content-quality) (11 checks)
* [Database](#database) (18 checks)
* [Extensions](#extensions) (13 checks)
* [Performance](#performance) (10 checks)
* [Security](#security) (21 checks)
* [SEO](#seo) (12 checks)
* [System & Hosting](#system-hosting) (33 checks)
* [Users](#users) (12 checks)

**Note:** The core plugin provides **129 checks** across these 8+ categories. Additional plugins may add more checks.

***

## Content Quality

Checks for content organization, quality, and accessibility issues.

| Check Name | 🟢 Good | 🟡 Warning | 🔴 Critical |
|------------|---------|------------|-------------|
| **Archived Content** | No archived articles or small count | - | - |
| **Category Depth** | Categories nested ≤5 levels | Categories nested >5 levels | - |
| **Draft Articles** | ≤20 draft articles | >20 draft articles | - |
| **Empty Articles** | ≤5 articles with minimal content | >5 published articles with <50 chars content | - |
| **Expired Content** | No expired content still published | Published articles past expiry date | - |
| **Menu Orphans** | All menu items link to existing content | - | Menu items link to non-existent articles (404s) |
| **Orphaned Articles** | ≤10 articles not in menus | >10 published articles not linked from menus | - |
| **Scheduled Content** | Report of future-scheduled articles | - | - |
| **Trashed Content** | ≤50 articles in trash | >50 articles in trash | - |
| **Uncategorized Content** | ≤10 articles in "Uncategorized" | >10 articles in "Uncategorized" category | - |
| **Unpublished Category Articles** | All published articles in published categories | Published articles in unpublished categories | - |

***

## Database

Checks for database health, configuration, performance, and integrity.

| Check Name | 🟢 Good | 🟡 Warning | 🔴 Critical |
|------------|---------|------------|-------------|
| **Auto-Increment Values** | All values have sufficient headroom | Auto-increment >80% of INT max | Database not available |
| **Backup Age** | Backup within last 7 days | Backup 7-30 days old OR Akeeba not installed | No backup in 30+ days OR never backed up |
| **Connection** | Database connected and responding | - | Connection failed (credentials, server down, etc.) |
| **Connection Charset** | Using utf8mb4 | Using utf8/utf8mb3 or non-UTF8 | Database not available |
| **Index Usage** | All tables have primary keys & indexes | Tables missing primary key OR no indexes | Database not available |
| **Max Packet Size** | max\_allowed\_packet ≥16MB | Between 1MB-16MB | <1MB |
| **Orphaned Tables** | Report of tables with Joomla prefix | - | Database not available |
| **Server Version** | MySQL 8.0.13+ OR MariaDB 10.4.0+ | Below recommended version | Database not available |
| **Database Size** | <1GB | 1-5GB (monitor growth) | >5GB |
| **Slow Query Log** | Enabled with threshold shown | Disabled | - |
| **SQL Mode** | No problematic modes | ONLY\_FULL\_GROUP\_BY enabled | Database not available |
| **Table Charset** | All tables using utf8mb4 | Non-utf8mb4 tables exist | Database not available |
| **Table Engine** | All tables using InnoDB | Non-InnoDB tables (e.g., MyISAM) | Database not available |
| **Table Prefix** | 3+ character prefix (not "jos\_") | Default "jos\_" OR <3 chars | - |
| **Table Status** | All tables healthy | - | Tables corrupted (NULL engine) |
| **Transaction Isolation** | REPEATABLE-READ or READ-COMMITTED | READ-UNCOMMITTED OR SERIALIZABLE | Database not available |
| **User Privileges** | Has all required privileges | Missing required privileges | Database not available |
| **Wait Timeout** | Between 30 seconds - 8 hours | <30s OR >8h | Database not available |

***

## Extensions

Checks for extension updates, compatibility, and configuration issues.

| Check Name | 🟢 Good | 🟡 Warning | 🔴 Critical |
|------------|---------|------------|-------------|
| **Cache Plugin** | System cache plugin enabled | Cache plugin disabled | - |
| **Disabled Extensions** | No unexpectedly disabled extensions | Extensions disabled that should be enabled | - |
| **Joomla Core Version** | Running latest stable version | Update available | Running unsupported/EOL version |
| **Language Packs** | All language packs up to date | Language pack updates available | - |
| **Legacy Extensions** | No deprecated extensions installed | Using deprecated extensions | Extensions incompatible with Joomla version |
| **Missing Updates** | All extensions up to date | Extension updates available | Critical security updates available |
| **Module Positions** | All published modules in valid positions | Modules in undefined/missing template positions | - |
| **Overrides** | No outdated template overrides | Template overrides outdated compared to core | - |
| **Plugin Order** | Critical plugins in correct load order | Plugins may have suboptimal load order | - |
| **Search Plugins** | Search plugins enabled | No search plugins enabled | - |
| **Template** | Using supported, up-to-date template | Template updates available | Template incompatible with Joomla |
| **Unused Modules** | No unpublished modules detected | Many unpublished modules consuming resources | - |
| **Update Sites** | All update sources accessible | Some update sites unreachable | Critical update sites failing |

***

## Performance

Checks for caching, compression, and optimization settings.

| Check Name | 🟢 Good | 🟡 Warning | 🔴 Critical |
|------------|---------|------------|-------------|
| **Browser Caching** | Browser caching headers configured | No browser caching headers | - |
| **Database Query Cache** | Query caching enabled | Query caching disabled | - |
| **Gzip Compression** | Gzip compression enabled | Gzip disabled | - |
| **Image Optimization** | Images optimized for web | Large unoptimized images detected | - |
| **Lazy Loading** | Lazy loading enabled for images | Lazy loading disabled | - |
| **Media Manager Thumbnails** | Thumbnail generation enabled | Thumbnail generation disabled | - |
| **Page Cache** | Page caching enabled | Page caching disabled (consider enabling) | - |
| **Redirects** | No excessive redirect chains | Multiple redirect chains detected | - |
| **Smart Search Index** | Smart Search index up to date | Index outdated | Smart Search broken/not configured |
| **System Cache** | System cache enabled | System cache disabled | - |

***

## Security

Checks for security vulnerabilities, configuration issues, and attack vectors.

| Check Name | 🟢 Good | 🟡 Warning | 🔴 Critical |
|------------|---------|------------|-------------|
| **Action Logs Enabled** | Action logs enabled & recording | Action logs disabled | - |
| **Admin Username** | No "admin" or "administrator" accounts | Super admin using common username | - |
| **API Authentication** | API tokens properly configured | API authentication weak/missing | API publicly accessible without auth |
| **configuration.php Permissions** | File permissions 444 (read-only) | File writable (644/666) | File publicly writable (777) |
| **Content Security Policy** | CSP headers configured | No CSP headers | - |
| **CORS Configuration** | CORS properly restricted | CORS allows all origins (\*) | - |
| **Debug Mode** | Debug disabled | Debug enabled (exposes sensitive info) | - |
| **Default Secret** | Unique secret configured | Using default/weak secret | - |
| **Error Reporting** | Error reporting disabled for public | PHP errors displayed to visitors | - |
| **Force SSL** | SSL required for admin & site | SSL not enforced | Site accessible without HTTPS |
| **.htaccess Protection** | .htaccess protecting sensitive folders | Missing .htaccess protections | Sensitive folders publicly accessible |
| **HTTPS Redirect** | All HTTP redirects to HTTPS | Mixed HTTP/HTTPS | No HTTPS redirect |
| **Mail Security** | Secure mail configuration (SMTP/TLS) | Using PHP mail() function | Mail credentials in plain text |
| **Password Policy** | Strong password policy enforced | Weak password requirements | No password policy |
| **Privacy Dashboard** | Privacy tools configured | Privacy features disabled | - |
| **reCAPTCHA** | reCAPTCHA enabled on forms | No CAPTCHA protection | - |
| **Session Handler** | Using database/Redis for sessions | Using file-based sessions | Session files world-readable |
| **Session Lifetime** | Session timeout 15-60 minutes | Timeout <15min OR >60min | Sessions never expire |
| **Two-Factor Auth** | 2FA required for admin accounts | 2FA optional/disabled | No 2FA configured |
| **User Actions Log** | User activity logging enabled | User Actions Log plugin disabled | - |
| **X-Frame-Options (Deprecated)** | Clickjacking protection enabled | No X-Frame-Options header (consider CSP frame-ancestors instead) | - |

***

## SEO

Checks for search engine optimization and discoverability.

| Check Name | 🟢 Good | 🟡 Warning | 🔴 Critical |
|------------|---------|------------|-------------|
| **Alt Text** | All images have alt attributes | >10% of images missing alt text | - |
| **Broken Links** | No broken internal links | Broken internal links detected | Many broken links (>50) |
| **Canonical URLs** | Canonical URLs configured | Duplicate content without canonicals | - |
| **Facebook Open Graph** | Essential OG tags present (og:title, og:description, og:image, og:url) | Missing Facebook Open Graph tags | - |
| **Meta Keywords** | Not using deprecated meta keywords tag | Using meta keywords (ignored by search engines) | - |
| **Open Graph Plugin** | Open Graph plugin installed & enabled | No Open Graph plugin detected | - |
| **robots.txt** | robots.txt exists and properly configured | robots.txt missing or blocking important content | robots.txt blocks all crawlers |
| **SEF URLs** | Search engine friendly URLs enabled | SEF URLs disabled | - |
| **Site Meta Description** | Global meta description configured | Missing global meta description | - |
| **Sitemap** | XML sitemap exists and accessible | Sitemap missing or outdated | Sitemap contains errors |
| **Structured Data** | Schema.org markup present | No structured data markup | - |
| **X/Twitter Cards** | Twitter Card meta tags present (or OG fallbacks) | Missing Twitter Card tags | - |

***

## System & Hosting

Checks for server configuration, PHP settings, and required extensions.

| Check Name | 🟢 Good | 🟡 Warning | 🔴 Critical |
|------------|---------|------------|-------------|
| **Apache Modules** | Required modules loaded | Optional modules missing | Required modules missing |
| **cURL Extension** | cURL installed and working | cURL missing (some features unavailable) | - |
| **Disk Space** | >5GB free space | 1-5GB free | <1GB free space |
| **DOM Extension** | DOM extension installed | - | DOM extension missing |
| **EXIF Extension** | EXIF installed | EXIF missing (image metadata unavailable) | - |
| **Failed Tasks** | No failed scheduled tasks | Some tasks failing | Critical tasks failing repeatedly |
| **FileInfo Extension** | FileInfo installed | - | FileInfo missing |
| **GD or Imagick** | Image library installed | - | No image library available |
| **Intl Extension** | Intl extension installed | Intl missing (i18n issues) | - |
| **JSON Extension** | JSON installed | - | JSON extension missing |
| **Log File Size** | Log directory ≤100MB | Log directory 100-500MB | Log directory >500MB |
| **Mail Function** | PHP mail() function working | mail() not available | - |
| **max\_execution\_time** | ≥60 seconds | 30-60 seconds | <30 seconds |
| **max\_input\_time** | ≥60 seconds | 30-60 seconds | <30 seconds |
| **max\_input\_vars** | ≥2500 | 1000-2500 | <1000 |
| **Mbstring Extension** | Mbstring installed | - | Mbstring missing |
| **memory\_limit** | ≥256MB | 128-256MB | <128MB |
| **OPcache** | OPcache enabled and configured | OPcache disabled | - |
| **OpenSSL Extension** | OpenSSL installed | - | OpenSSL missing |
| **Output Buffering** | Output buffering properly configured | Problematic buffer settings | - |
| **Overdue Tasks** | No overdue scheduled tasks | Tasks overdue by <24h | Tasks overdue by >24h |
| **PDO MySQL** | PDO MySQL extension installed | - | PDO MySQL missing |
| **PHP End-of-Life** | PHP under active support | PHP in security-only support or nearing EOL | PHP past end-of-life date |
| **PHP SAPI** | Running under recommended SAPI | Using CGI/FastCGI | Using deprecated SAPI |
| **PHP Version** | PHP 8.2+ | PHP 8.1 (below recommended) | PHP <8.1 (unsupported) |
| **post\_max\_size** | ≥64MB | 8-64MB | <8MB |
| **Realpath Cache** | Adequate realpath cache | Cache too small | - |
| **Server Time** | Server time matches expected timezone | Time offset detected | Major time difference (>1 hour) |
| **Session Save Path** | Session save path writable | Session path not writable | - |
| **SimpleXML Extension** | SimpleXML installed | - | SimpleXML missing |
| **Temp Directory** | Temp directory writable | Temp directory issues | Temp directory not writable |
| **upload\_max\_filesize** | ≥64MB | 2-64MB | <2MB |
| **ZIP Extension** | ZIP extension installed | - | ZIP extension missing |

***

## Users

Checks for user account security and configuration.

| Check Name | 🟢 Good | 🟡 Warning | 🔴 Critical |
|------------|---------|------------|-------------|
| **Admin Email** | Unique email for each admin | Multiple admins sharing email | - |
| **Blocked Users** | No unexpectedly blocked users | >10 blocked user accounts | - |
| **Default User Group** | Appropriate default registration group | Default group has elevated permissions | Default group is Super Admin |
| **Duplicate Emails** | All users have unique emails | Multiple users sharing email addresses | - |
| **Inactive Users** | <10% users inactive >1 year | >10% users inactive >1 year | - |
| **Last Login** | Admin accounts logged in recently | Admin accounts inactive >90 days | Admin accounts never used |
| **Password Expiry** | Password expiry policy enforced | No password expiration | Passwords never expire |
| **Super Admin Count** | 2-5 super admin accounts | 1 super admin OR >5 super admins | - |
| **User Fields** | Custom user fields configured properly | Issues with custom fields | - |
| **User Groups** | User groups properly configured | Unusual group permissions | Public group has admin access |
| **User Notes** | User notes used appropriately | - | - |
| **User Registration** | Registration settings appropriate | Registration open without controls | - |

***

## Need More Checks?

Health Checker for Joomla is extensible! Developers can add custom health checks through the plugin API.

* **Extension Developers**: Add checks specific to your extension
* **Third-party Integrations**: Monitor external services (Akeeba Backup, Admin Tools, etc.)
* **Custom Requirements**: Build checks for your organization's specific needs

[Learn how to create custom health checks →](/developers/creating-checks)

***

## Running Health Checks

To run all health checks:

1. Navigate to **Components → Health Checker**
2. Click **Run Health Check** in the toolbar
3. Review results organized by category and status
4. Take action on Critical and Warning items

[Learn more about running health checks →](/getting-started)
