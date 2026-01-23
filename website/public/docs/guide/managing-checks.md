---
url: /docs/guide/managing-checks.md
---
# Managing Health Checks

Health Checker for Joomla allows you to enable or disable individual health checks through the plugin configuration interface. This gives you complete control over which checks run on your site.

## Why Disable Checks?

You might want to disable certain checks if:

* **They're not relevant to your site** - For example, if you don't use the JSON extension, you can disable that check
* **They generate false positives** - Some checks might flag warnings for configurations that are intentional on your site
* **Performance optimization** - Disabling unused checks reduces execution time
* **Reducing noise** - Focus on the checks that matter most to your specific setup

## How to Manage Checks

### Accessing Plugin Configuration

1. Log into your Joomla administrator panel
2. Navigate to **Extensions → Plugins**
3. Search for **"Health Checker - Core Checks"**
4. Click on the plugin name to edit it

### Understanding the Configuration Screen

The plugin configuration is organized into 8 category fieldsets, matching the Health Checker categories:

* **Content Checks** - Articles, categories, menus (11 checks)
* **Database Checks** - Connection, performance, integrity (17 checks)
* **Extensions Checks** - Joomla extensions and PHP extensions (21 checks)
* **Performance Checks** - Caching, compression, optimization (11 checks)
* **Security Checks** - SSL, permissions, authentication (22 checks)
* **SEO Checks** - Meta tags, URLs, sitemaps (11 checks)
* **System & Hosting Checks** - PHP, server, resources (34 checks)
* **Users Checks** - Accounts, permissions, activity (12 checks)

### Disabling Individual Checks

Each category has a list of checkboxes showing all available checks in that category:

1. **Expand the category** you want to configure (e.g., "Extensions Checks")
2. **Uncheck the boxes** for checks you want to disable
   * For example, uncheck "JSON Extension" if you don't use it
3. **Click Save & Close**

The next time you run Health Checker, the disabled checks will not execute.

### Disabling Entire Categories

To disable all checks in a category:

1. Expand the category fieldset
2. Uncheck **all** the checkboxes in that category
3. Click Save & Close

> **Note:** You cannot disable categories through the component itself - you must disable individual checks through the plugin configuration.

## Default Behavior

**All checks are enabled by default** when you first install Health Checker. You must explicitly disable checks you don't want to run.

If you haven't configured any checks:

* All 130 core checks will run
* This is the recommended starting point for most sites
* You can disable checks as needed after reviewing initial results

## Examples

### Example 1: Disabling PHP Extension Checks

If your server doesn't have certain PHP extensions and you're okay with that:

1. Go to plugin configuration
2. Expand **"Extensions Checks"**
3. Uncheck:
   * JSON Extension
   * Imagick Extension
   * EXIF Extension
   * (or any others you don't need)
4. Save

### Example 2: Disabling Content Checks

If you're running a simple site with minimal content management:

1. Go to plugin configuration
2. Expand **"Content Checks"**
3. Uncheck checks like:
   * Orphaned Articles
   * Empty Articles
   * Draft Articles
4. Save

### Example 3: Focusing Only on Security

If you only care about security monitoring:

1. Keep all **"Security Checks"** enabled
2. Disable checks in other categories as needed
3. This reduces execution time and focuses reports on security issues only

## Impact on Performance

Disabled checks are **completely skipped** during execution:

* They don't run any code
* They don't query the database
* They don't appear in results
* The total check count decreases

This improves performance, especially if you disable many checks.

**Example:** If you disable 20 checks out of 130, the health check will execute faster because those 20 checks are never instantiated or executed.

## Dashboard Module

The [Dashboard Module](/dashboard-widget) automatically respects disabled checks:

* Only enabled checks appear in the widget
* Counts and status badges reflect only active checks
* Cache is based on enabled checks only

## Re-Enabling Checks

To re-enable a previously disabled check:

1. Return to the plugin configuration
2. Expand the relevant category
3. **Check the box** next to the check you want to enable
4. Click Save & Close

The check will appear in results on the next health check run.

## Troubleshooting

### Check titles showing as language keys

If you see `PLG_HEALTHCHECKER_CORE_CHECK_...` instead of check names:

* This is a language file issue, not a configuration issue
* Reinstall the plugin to restore language files
* Or manually add missing language keys to `plg_healthchecker_core.ini`

### All checks disabled accidentally

If you've disabled too many checks and want to reset:

1. Go to plugin configuration
2. Expand each category
3. **Check all boxes** to re-enable everything
4. Or uninstall and reinstall the plugin to reset to defaults

### Changes not taking effect

If disabling checks doesn't work:

1. Verify you clicked **Save & Close**
2. Clear Joomla cache (System → Clear Cache)
3. Run health check again
4. Check that the plugin is **enabled** (not just configured)

## Advanced: Plugin Parameters in Database

Disabled checks are stored as JSON arrays in the plugin parameters. For example:

```json
{
  "enabled_system": ["system.php_version", "system.disk_space", ...],
  "enabled_database": ["database.connection", ...],
  ...
}
```

If a category parameter is empty or not set, **all checks in that category are enabled by default**.

## Best Practices

✅ **DO:**

* Start with all checks enabled to get a baseline
* Disable checks after understanding what they do
* Disable checks that genuinely don't apply to your site
* Document which checks you've disabled and why

❌ **DON'T:**

* Disable security checks just because they show warnings
* Disable checks without understanding them first
* Disable too many checks - you might miss important issues

## Related Documentation

* [Understanding Results](/reading-results) - Learn what each check means
* [Check Reference](/checks/) - Complete list of all checks
* [Getting Started](/getting-started) - Initial setup guide
* [Dashboard Module](/dashboard-widget) - Widget configuration
