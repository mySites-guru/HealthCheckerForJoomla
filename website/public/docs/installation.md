---
url: /docs/installation.md
---
# Installation

Installing Health Checker for Joomla is straightforward using Joomla's standard extension installation process.

## System Requirements

Before installing, ensure your system meets these requirements:

* **Joomla Version**: 5.0 or higher
* **PHP Version**: 8.1 or higher
* **MySQL/MariaDB**: MySQL 8.0+ or MariaDB 10.4+
* **User Access**: Super Administrator privileges

## Download

Download the latest version from one of these sources:

* **GitHub Releases**: [github.com/mySites-guru/HealthCheckerForJoomla/releases](https://github.com/mySites-guru/HealthCheckerForJoomla/releases)
* **Joomla Extensions Directory**: *(Coming soon)*

You'll download a file named `pkg_healthchecker_vX.X.X.zip`

## Installation Steps

### 1. Access Extension Manager

1. Log into your Joomla administrator panel
2. Navigate to **System → Install → Extensions**

### 2. Upload Package

1. Click the **Upload Package File** tab
2. Click **Browse** and select the `pkg_healthchecker_vX.X.X.zip` file
3. The package will upload and install automatically

### 3. Verify Installation

After installation completes, you should see a success message listing the installed components:

* ✅ Component: Health Checker (com\_healthchecker)
* ✅ Plugin: Health Checker - Core Checks (plg\_healthchecker\_core)
* ✅ Module: Health Checker Dashboard Widget (mod\_healthchecker)

### 4. Enable the Core Plugin

**Important**: The core plugin must be enabled for health checks to work.

1. Navigate to **System → Manage → Plugins**
2. Search for "Health Checker - Core"
3. Click to enable the plugin (green checkmark)

### 5. Add Dashboard Widget (Optional)

To add the Health Checker widget to your admin dashboard:

1. Navigate to **Home Dashboard**
2. Click **Customize This Page** (top right)
3. Find **Health Checker** in the available modules
4. Drag it to your preferred position on the dashboard
5. Click **Save & Close**

## What Gets Installed

### Component (com\_healthchecker)

The main component provides:

* Health check report page
* Event system for collecting checks
* Export functionality
* UI components

**Location**: `administrator/components/com_healthchecker/`

### Core Plugin (plg\_healthchecker\_core)

Contains 130 health checks organized in 8+ categories.

**Location**: `plugins/healthchecker/core/`

### Dashboard Module (mod\_healthchecker)

Displays health check summary on the admin dashboard.

**Location**: `administrator/modules/mod_healthchecker/`

## Optional Integrations

If you use Akeeba extensions, you can install optional integration plugins:

### Akeeba Backup Integration

Monitors backup status, frequency, and success rate.

**Download**: Included in main package as `plg_healthchecker_akeebabackup.zip`

### Akeeba Admin Tools Integration

Tracks security events, WAF status, and file integrity.

**Download**: Included in main package as `plg_healthchecker_akeebaadmintools.zip`

To install these:

1. Extract them from the main package ZIP
2. Install each via **System → Install → Extensions**
3. Enable in **System → Manage → Plugins**

## Verifying Installation

### Run Your First Health Check

1. Navigate to **Components → Health Checker**
2. Click the **Run Health Check** button
3. Wait for all checks to complete
4. Review the results

You should see checks organized into 8+ categories with Critical/Warning/Good statuses.

### Check Dashboard Widget

If you added the dashboard widget:

1. Go to **Home Dashboard**
2. Verify the Health Checker widget shows summary counts
3. Click the widget to navigate to the full report

## Troubleshooting Installation

### "Package Installation Failed"

**Cause**: Usually a permissions issue or PHP configuration limit.

**Solutions**:

* Verify the `/tmp` directory is writable
* Check PHP `upload_max_filesize` and `post_max_size` settings
* Ensure adequate disk space

### Core Plugin Not Showing

**Cause**: Plugin group filter may be hiding it.

**Solutions**:

* In **System → Manage → Plugins**, clear all filters
* Search for "healthchecker" in the search box
* Select "Health Checker" from the plugin type dropdown

### No Checks Appear When Running

**Cause**: Core plugin is not enabled.

**Solution**:

* Go to **System → Manage → Plugins**
* Find "Health Checker - Core Checks"
* Click to enable it (should show green checkmark)
* Return to Health Checker and run again

## Uninstallation

To completely remove Health Checker:

1. Navigate to **System → Manage → Extensions**
2. Search for "Health Checker"
3. Select all Health Checker extensions
4. Click **Uninstall**

This removes:

* Component
* All plugins
* Dashboard module
* All files and folders

**Note**: Health Checker doesn't create database tables, so there's no data to clean up.

## Updating

To update to a newer version:

1. Download the latest package
2. Install it using **System → Install → Extensions**
3. Joomla will automatically upgrade the existing installation

Your settings and enabled plugins are preserved during updates.

## Professional Multi-Site Management

:::tip Managing Multiple Joomla Sites?


While this free Health Checker extension is perfect for monitoring a single site, professional site managers need centralized monitoring across all their Joomla installations.

**[mySites.guru](https://mysites.guru)** - The Original Joomla Monitoring Platform (since 2012)

**One Dashboard for All Your Sites:**

* 🏥 Automated health checks on unlimited Joomla sites
* 🔔 Near Realtime Email Alerting for issues
* 📊 Historical health tracking and trend analysis
* 🎯 Client-ready reports with white-label branding
* ⚡ Bulk update management - update multiple sites in one click
* 🔒 Security monitoring, uptime checks, and SSL certificate tracking
* 👥 Team collaboration with role-based access

**Perfect for:**

* Agencies managing client sites
* Freelancers with multiple projects
* IT departments with Joomla portfolios
* Site owners with multiple properties

mySites.guru = The Original Joomla Health Checker Since 2012. [Start monitoring now →](https://mysites.guru)
:::

## Next Steps

Now that Health Checker is installed, continue to [Getting Started](/getting-started) to learn how to use it.
