# Akeeba Health Checks

Health Checker for Joomla includes two optional plugins for monitoring Akeeba extensions: **Akeeba Backup** and **Akeeba Admin Tools**.

## Overview

These checks are implemented as separate Joomla plugins using the Health Checker SDK integration pattern. This means:

- **Optional Installation** - Only install if you use Akeeba products
- **Self-Contained** - Each plugin registers its own category, checks, and language strings
- **Independent Updates** - Can be updated separately from the core Health Checker

## Installation

The plugins are located in:
- `plugins/healthchecker/akeebabackup/` - Akeeba Backup checks
- `plugins/healthchecker/akeebaadmintools/` - Akeeba Admin Tools checks

After installation, enable the plugins in **Extensions > Plugins**.

---

## Plugin: Health Checker - Akeeba Backup

**Plugin:** `plg_healthchecker_akeebabackup`
**Category:** Akeeba Backup (icon: `fa-archive`, sortOrder: 85)

### Checks (10)

| Check | Slug | Description |
|-------|------|-------------|
| **Akeeba Backup Installed** | `akeeba_backup.installed` | Verifies Akeeba Backup is installed. Warning if not installed. |
| **Last Backup** | `akeeba_backup.last_backup` | Checks age of most recent successful backup. Critical if >7 days, Warning if >3 days. |
| **Backup Success Rate** | `akeeba_backup.success_rate` | Calculates percentage of successful backups in last 30 days. Warning if <90%. |
| **Stuck Backups** | `akeeba_backup.stuck_backups` | Detects backups stuck in progress for >24 hours. Critical if found. |
| **Backup Files Exist** | `akeeba_backup.files_exist` | Verifies backup archive files still exist on disk. Warning if files missing. |
| **Backup Storage** | `akeeba_backup.backup_size` | Reports total storage used by backup archives. |
| **Backup Profile** | `akeeba_backup.profile_exists` | Verifies at least one backup profile is configured. |
| **Profile Configuration** | `akeeba_backup.profile_configured` | Checks default profile has been configured. |
| **Failed Backups** | `akeeba_backup.failed_backups` | Counts failed backups in last 30 days. Warning if any failed. |
| **Backup Frequency** | `akeeba_backup.frequency` | Analyzes backup frequency. Good if 4+ per month, Warning if 1-3, Critical if none. |

### Database Tables Used

- `#__akeebabackup_backups` - Backup history and status
- `#__akeebabackup_profiles` - Backup profile configurations

---

## Plugin: Health Checker - Akeeba Admin Tools

**Plugin:** `plg_healthchecker_akeebaadmintools`
**Category:** Akeeba Admin Tools (icon: `fa-shield-alt`, sortOrder: 86)

### Checks (15)

| Check | Slug | Description |
|-------|------|-------------|
| **Admin Tools Installed** | `akeeba_admintools.installed` | Verifies Admin Tools is installed. Warning if not installed. |
| **WAF Status** | `akeeba_admintools.waf_enabled` | Checks if WAF rules are enabled. Warning if no active rules. |
| **Security Events** | `akeeba_admintools.security_events` | Reports security events logged in last 7 days. |
| **Blocked Attacks** | `akeeba_admintools.blocked_attacks` | Reports attacks blocked in last 24 hours. |
| **Active IP Bans** | `akeeba_admintools.active_bans` | Reports currently active auto-banned IPs. |
| **File Integrity Scan** | `akeeba_admintools.scan_age` | Checks age of last file integrity scan. Warning if >7 days, Critical if >30 days. |
| **File Integrity Alerts** | `akeeba_admintools.file_alerts` | Checks for unacknowledged high-threat file alerts. Critical if found. |
| **Temporary Super Users** | `akeeba_admintools.temp_superusers` | Checks for expired temporary super user privileges. Warning if found. |
| **IP Whitelist** | `akeeba_admintools.ip_whitelist` | Reports number of IPs in admin whitelist. |
| **WAF Rules** | `akeeba_admintools.waf_rules` | Reports enabled vs total WAF rules count. |
| **Login Failures** | `akeeba_admintools.login_failures` | Counts failed login attempts in last 24 hours. Warning if >10. |
| **Geoblocking** | `akeeba_admintools.geoblocking` | Reports requests blocked by geoblocking in last 7 days. |
| **SQL Injection Blocks** | `akeeba_admintools.sqli_blocks` | Reports SQL injection attempts blocked in last 7 days. |
| **XSS Blocks** | `akeeba_admintools.xss_blocks` | Reports XSS attempts blocked in last 7 days. |
| **Admin Access Log** | `akeeba_admintools.admin_access` | Reports admin panel access events logged in last 7 days. |

### Database Tables Used

- `#__admintools_log` - Security event log
- `#__admintools_ipautoban` - Active automatic IP bans
- `#__admintools_ipallow` - IP whitelist/allowlist
- `#__admintools_wafblacklists` - WAF rules configuration
- `#__admintools_scans` - File integrity scan history
- `#__admintools_scanalerts` - File integrity alerts
- `#__admintools_tempsupers` - Temporary super user privileges

### Security Event Types

The `#__admintools_log` table tracks various security event types including:

| Event | Description |
|-------|-------------|
| `loginfailure` | Failed login attempts |
| `sqlishield` | SQL injection attempts |
| `xssshield` | Cross-site scripting attempts |
| `geoblocking` | Blocked by geographic restrictions |
| `admindir` | Admin panel access attempts |

---

## SDK Integration Pattern

Both plugins use the Health Checker SDK pattern, implementing the `SubscriberInterface` to listen for events:

```php
public static function getSubscribedEvents(): array
{
    return [
        'onHealthCheckerCollectCategories' => 'onCollectCategories',
        'onHealthCheckerCollectChecks' => 'onCollectChecks',
        'onHealthCheckerCollectProviders' => 'onCollectProviders',
    ];
}
```

### Graceful Degradation

All checks handle missing extensions gracefully:

```php
// Check if Akeeba Backup is installed
$prefix = $db->getPrefix();
$tables = $db->setQuery(
    "SHOW TABLES LIKE " . $db->quote($prefix . 'akeebabackup_backups')
)->loadColumn();

if (empty($tables)) {
    return $this->warning(
        'Akeeba Backup is not installed. Consider installing it for reliable backups.'
    );
}
```

---

## Recommended Thresholds

### Backup Thresholds

| Metric | Good | Warning | Critical |
|--------|------|---------|----------|
| Last backup age | <3 days | 3-7 days | >7 days |
| Success rate | >90% | <90% | - |
| Monthly frequency | 4+ backups | 1-3 backups | 0 backups |
| Stuck backup age | - | - | >24 hours |

### Security Thresholds

| Metric | Good | Warning | Critical |
|--------|------|---------|----------|
| File scan age | <7 days | 7-30 days | >30 days |
| Login failures (24h) | <10 | >10 | - |
| Unacknowledged alerts | 0 | Low threat | High threat (score >=50) |
| Expired temp superusers | 0 | >0 | - |

---

## Creating Your Own Integration Plugin

Use these plugins as a reference for integrating your own extensions with Health Checker. Key steps:

1. Create a plugin in the `healthchecker` group
2. Implement `SubscriberInterface`
3. Subscribe to `onHealthCheckerCollectCategories`, `onHealthCheckerCollectChecks`, and `onHealthCheckerCollectProviders`
4. Register your category, checks, and provider metadata
5. Use anonymous classes or separate check classes extending `AbstractHealthCheck`

See the example plugin at `plugins/system/examplehealthcheck/` for a minimal implementation.
