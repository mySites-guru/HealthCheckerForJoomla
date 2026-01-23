# Akeeba Backup Integration

Health Checker includes an optional integration plugin for Akeeba Backup that monitors your backup status and configuration.

## Overview

The Akeeba Backup integration plugin adds health checks to verify:
- Backup recency (when was the last successful backup)
- Backup profile configuration
- Backup schedule status
- Backup storage space

## Installation

The Akeeba Backup integration plugin is included with Health Checker but requires Akeeba Backup to be installed.

### Automatic Enabling

If Akeeba Backup is installed when you install Health Checker, the integration plugin is automatically enabled.

### Manual Enabling

If you install Akeeba Backup after Health Checker:

1. Navigate to **Extensions → Plugins**
2. Search for **"Health Checker - Akeeba Backup"**
3. Click to enable the plugin

## Health Checks

### Last Backup Age

Monitors when the last successful backup was completed.

- **Good**: Backup within last 24 hours
- **Warning**: Backup 1-7 days old
- **Critical**: No backup in over 7 days or no backups found

**Why it matters**: Regular backups are essential for disaster recovery.

### Backup Profile Configuration

Verifies that at least one backup profile is configured.

- **Good**: One or more profiles configured
- **Critical**: No backup profiles found

**Why it matters**: Profiles are required to run backups.

### Failed Backups

Checks for recent backup failures.

- **Good**: No recent failures
- **Warning**: 1-2 recent failures
- **Critical**: 3+ recent failures

**Why it matters**: Failed backups mean you may not have a valid backup when needed.

### Backup Storage Space

Monitors available storage space for backups.

- **Good**: Adequate space available
- **Warning**: Storage getting low
- **Critical**: Insufficient storage space

**Why it matters**: Backups will fail if storage is full.

## Requirements

- Akeeba Backup installed (any edition: Core, Professional)
- Minimum version: Akeeba Backup 9.0.0
- Joomla 5.0+

## Disabling the Integration

If you don't use Akeeba Backup or don't want these checks:

1. **Extensions → Plugins**
2. Find **"Health Checker - Akeeba Backup"**
3. Click to disable

The plugin is safe to disable even if Akeeba Backup is installed.

## Provider Attribution

Checks from this integration appear with the "Akeeba Backup" provider badge, making it clear these checks are specific to Akeeba Backup functionality.

## Next Steps

- [Akeeba Admin Tools Integration](./akeeba-admin-tools.md) - Security monitoring
- [Understanding Health Checks](../understanding-checks.md) - Learn about check categories
