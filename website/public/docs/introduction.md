---
url: /docs/introduction.md
---
# Introduction

Health Checker for Joomla is a comprehensive monitoring extension that helps you maintain a healthy Joomla website by running automated checks across multiple categories.

![Health Checker Admin Interface](/images/admin-console.png)

## What Does It Do?

Health Checker examines your Joomla site and reports on:

* **System & Hosting** - PHP configuration, required extensions, disk space, scheduler health
* **Database** - Connection health, table status, character encoding
* **Security** - File permissions, security headers, authentication settings
* **Users** - Admin account security, inactive users, session settings
* **Extensions** - Updates available, compatibility issues, template overrides
* **Performance** - Caching configuration, compression, OPcache status
* **SEO** - Meta descriptions, URL structure, broken links
* **Content Quality** - Orphaned articles, broken menu items, empty categories

## How It Works

Health Checker is **not** a monitoring service. It doesn't run in the background or send alerts. Instead:

1. You visit the Health Checker page in your Joomla admin panel
2. Click "Run Health Check" to execute all checks
3. View the results immediately - Critical, Warning, or Good status for each check
4. Take action on any issues identified
5. Export reports if needed (JSON or HTML format)

Results are **not stored** - each time you run the health check, you get fresh results based on the current state of your site.

## Who Should Use It?

### Site Administrators

* Identify security vulnerabilities before they're exploited
* Catch configuration problems early
* Ensure optimal performance settings
* Verify SEO best practices

### Developers & Agencies

* Pre-launch site audits
* Client site health reports
* Troubleshooting configuration issues
* Quality assurance checks

### Hosting Providers

* Verify server configuration meets Joomla requirements
* Identify customer sites with issues
* Generate health reports for support tickets

## Key Features

### 133+ Health Checks

Comprehensive coverage across all critical aspects of your Joomla site.

### Dashboard Widget

Quick status overview right on your admin dashboard - see Critical, Warning, and Good counts at a glance.

### Filtering & Search

Filter results by status (Critical/Warning/Good) and category (System/Database/Security/etc). Combine filters for precise results.

### Export Reports

Download results as JSON (machine-readable) or HTML (formatted, printable report).

### Extensible

Third-party extension developers can add their own health checks through a simple plugin API.

### No Database Storage

Health Checker doesn't create database tables or store historical data. Every check is fresh.

### Super Admin Only

Secure access control - only Super Administrators can run health checks.

## Requirements

* **Joomla**: 5.0 or higher
* **PHP**: 8.1 or higher
* **Access**: Super Administrator privileges

## What Health Checker Doesn't Do

* **No Background Monitoring** - Checks only run when you click the button
* **No Email Alerts** - Results are displayed in the admin panel only
* **No History Tracking** - Results aren't stored (Pro version will add this)
* **No Automated Fixes** - You review results and take action manually (Pro version will add one-click fixes)
* **No SSL/Certificate Checks** - These are infrastructure-level concerns handled by hosting

:::tip Need These Advanced Features?


If you want **background monitoring, email alerts, history tracking, uptime checks, SSL monitoring**, and **multi-site management**, consider **[mySites.guru](https://mysites.guru)** - which does all this and much more.

**Beyond Health Checking:**

* 🔔 **Automated monitoring** - Continuous health checks without manual triggers
* 📧 **Instant alerts** - Near Realtime Email Alerting for issues
* 📊 **Historical tracking** - compare between previous snapshots and audits
* 🔒 **Uptime & SSL monitoring** - Infrastructure-level checks
* 🏥 **Unlimited sites** - Monitor your entire Joomla portfolio from one dashboard - Plus WordPress!
* ⚡ **Bulk updates** - Update multiple sites in one click
* 🎯 **Client reports** - Professional white-label reports

mySites.guru = The Original Joomla Health Checker Since 2012. [Start your free trial →](https://mysites.guru)
:::

## Getting Help

* **Documentation**: You're reading it! Browse the sidebar for detailed guides
* **GitHub Issues**: [Report bugs or request features](https://github.com/mySites-guru/issues)

## Next Steps

Ready to get started? Continue to [Installation](/installation) to install Health Checker on your Joomla site.
