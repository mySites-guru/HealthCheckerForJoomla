---
url: /docs/integrations/akeeba-admin-tools.md
---
# Akeeba Admin Tools Integration

Health Checker includes an optional integration plugin for Akeeba Admin Tools that monitors your security configuration and WAF status.

## Overview

The Akeeba Admin Tools integration plugin adds health checks to verify:

* Web Application Firewall (WAF) status
* Security configuration completeness
* .htaccess Maker configuration
* Admin protection settings

## Installation

The Akeeba Admin Tools integration plugin is included with Health Checker but requires Admin Tools to be installed.

### Automatic Enabling

If Admin Tools is installed when you install Health Checker, the integration plugin is automatically enabled.

### Manual Enabling

If you install Admin Tools after Health Checker:

1. Navigate to **Extensions → Plugins**
2. Search for **"Health Checker - Akeeba Admin Tools"**
3. Click to enable the plugin

## Health Checks

### WAF Status

Verifies the Web Application Firewall is enabled and active.

* **Good**: WAF is enabled and protecting your site
* **Critical**: WAF is disabled

**Why it matters**: The WAF blocks common attacks and vulnerabilities.

### htaccess Maker Applied

Checks if .htaccess Maker configuration has been applied.

* **Good**: .htaccess file generated and active
* **Warning**: .htaccess Maker not run or outdated

**Why it matters**: htaccess Maker adds server-level security hardening.

### Admin Tools Main Password

Verifies if Master Password protection is enabled.

* **Good**: Main Password configured
* **Warning**: Not using Main Password protection

**Why it matters**: Main Password adds an extra layer of admin access protection.

### Admin URL Protection

Checks if admin directory URL protection is enabled.

* **Good**: Admin URL protection active
* **Warning**: Using standard /administrator URL

**Why it matters**: Changing admin URL reduces automated attack attempts.

## Requirements

* Akeeba Admin Tools installed (any edition: Core, Professional)
* Minimum version: Admin Tools 7.0.0
* Joomla 5.0+

## Disabling the Integration

If you don't use Admin Tools or don't want these checks:

1. **Extensions → Plugins**
2. Find **"Health Checker - Akeeba Admin Tools"**
3. Click to disable

The plugin is safe to disable even if Admin Tools is installed.

## Provider Attribution

Checks from this integration appear with the "Akeeba Admin Tools" provider badge, making it clear these checks are specific to Admin Tools functionality.

## Next Steps

* [Akeeba Backup Integration](./akeeba-backup.md) - Backup monitoring
* [Security Checks](../checks/security.md) - Core security checks
* [Understanding Health Checks](../understanding-checks.md) - Learn about check categories
