# Local Development Setup

This guide explains how to set up a local development environment for working on Health Checker for Joomla.

## Prerequisites

- **Joomla 5.0+** installed and running
- **PHP 8.1+**
- **MySQL 8.0+ or MariaDB 10.4+**
- **Git**
- **Composer** (for code quality tools)

## Project Structure

```
/Users/phil/Sites/health-checker-for-joomla/
├── healthchecker/          # SOURCE CODE (edit here)
│   ├── component/          # com_healthchecker
│   ├── module/             # mod_healthchecker
│   └── plugins/            # All plugins
│       ├── core/           # 130 check files
│       ├── example/        # SDK reference
│       ├── mysitesguru/    # mySites.guru integration
│       ├── akeebabackup/   # Akeeba Backup monitoring
│       └── akeebaadmintools/ # Akeeba Admin Tools monitoring
│
├── joomla/                 # JOOMLA INSTALLATION (test here)
│   └── administrator/
│       ├── components/com_healthchecker → /healthchecker/component
│       ├── modules/mod_healthchecker → /healthchecker/module
│       └── ...
│
├── build/                  # BUILD SCRIPTS
│   ├── build.sh            # Create installable packages
│   ├── setupSymlinks.sh    # Link joomla/ → healthchecker/
│   └── dist/               # Built packages (not in git)
│
├── vendor/                 # Composer dependencies
├── composer.json           # Code quality tools (ECS, Rector)
└── docs/                   # Documentation
```

## Quick Start

### 1. Clone Repository

```bash
git clone https://github.com/mySites-guru/HealthCheckerForJoomla.git
cd health-checker-for-joomla
```

### 2. Install Dependencies

```bash
# Install Composer dependencies (code quality tools)
composer install
```

### 3. Setup Development Symlinks

This links `/joomla/` to `/healthchecker/` so changes reflect immediately:

```bash
./build/setupSymlinks.sh
```

**What it does**:
- Checks for conflicts (existing Health Checker installation)
- Creates symlinks: `/joomla/{location}` → `/healthchecker/{source}`
- Validates all symlinks created successfully

If conflicts found, the script shows how to backup and remove existing files.

### 4. Access Joomla Admin

Open your Joomla admin panel:

```
http://yourjoomla.local/administrator
```

Navigate to: **Components → Health Checker**

### 5. Start Developing

Edit files in `/healthchecker/` directory:

```bash
# Example: Edit a check
vim healthchecker/plugins/core/src/Checks/Security/DebugModeCheck.php

# Changes reflect immediately via symlinks
# Just refresh your browser!
```

## Development Workflow

### Making Changes

1. **Edit source files** in `/healthchecker/` directory
2. **Refresh browser** - changes appear immediately via symlinks
3. **Clear Joomla cache** if needed (System → Clear Cache)
4. **Test thoroughly** before committing

### No Build Step Required

During development, **you don't need to build packages**. The symlink setup means:

- Edit `/healthchecker/plugins/core/src/Checks/System/NewCheck.php`
- Immediately available at `/joomla/plugins/healthchecker/core/src/Checks/System/NewCheck.php`
- Refresh admin panel to see changes

### Adding New Checks

Example: Add a new security check

```bash
# 1. Create check file
vim healthchecker/plugins/core/src/Checks/Security/NewSecurityCheck.php

# 2. Add language key
vim healthchecker/plugins/core/language/en-GB/plg_healthchecker_core.ini
# Add: COM_HEALTHCHECKER_CHECK_CORE_NEW_SECURITY_CHECK_TITLE="My New Check"

# 3. Test (auto-discovered via directory scan)
# Navigate to: Components → Health Checker → Run Health Check
# Your check appears automatically!

# 4. No registration needed - auto-discovery handles it
```

### File Structure for Checks

```
healthchecker/plugins/core/src/Checks/
├── System/
│   ├── PhpVersionCheck.php
│   ├── MemoryLimitCheck.php
│   └── ... (32 checks)
├── Database/
│   ├── ConnectionCheck.php
│   ├── CharsetCheck.php
│   └── ... (18 checks)
├── Security/
│   ├── DebugModeCheck.php
│   ├── HttpsCheck.php
│   └── ... (22 checks)
└── ... (8 categories total)
```

**Naming convention**: `{CheckName}Check.php`

**Must extend**: `AbstractHealthCheck`

### Check Template

```php
<?php
namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Security;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

defined('_JEXEC') or die;

/**
 * [Check Name] Health Check
 *
 * WHY THIS CHECK IS IMPORTANT:
 * [Explanation]
 *
 * RESULT MEANINGS:
 * GOOD: [What this means]
 * WARNING: [What this means]
 * CRITICAL: [What this means]
 */
final class MyNewCheck extends AbstractHealthCheck
{
    public function getSlug(): string
    {
        return 'core.my_new_check';
    }

    public function getCategory(): string
    {
        return 'security';
    }

    public function getProvider(): string
    {
        return 'core';
    }

    protected function performCheck(): HealthCheckResult
    {
        // Your check logic
        $db = $this->getDatabase();

        // Return result
        return $this->good('All OK');
        // or: return $this->warning('Needs attention');
        // or: return $this->critical('Serious issue');
    }
}
```

## Code Quality Tools

### PHP Code Sniffer (ECS)

Fix code style issues:

```bash
# Fix all code style issues
composer run cs:fix

# Check without fixing
composer run cs:check
```

### Rector (PHP Modernization)

Modernize PHP code:

```bash
# Modernize code
composer run rector

# Dry run (show what would change)
composer run rector -- --dry-run
```

### Before Committing

Always run:

```bash
composer run cs:fix
```

This ensures consistent code style.

## Testing

### Manual Testing Checklist

Before committing changes:

- [ ] Run health check - all checks execute
- [ ] Test all result statuses (good/warning/critical)
- [ ] Filter by status - Critical/Warning/Good/All work
- [ ] Filter by category - All 8 categories work
- [ ] Combined filters work (status + category)
- [ ] Export JSON - valid JSON output
- [ ] Export HTML - renders correctly
- [ ] Dashboard widget - shows correct counts
- [ ] Language strings - no missing translations
- [ ] Provider badges - third-party checks show branding

### Testing New Checks

When adding a check:

1. **Run the check** - Verify it appears in correct category
2. **Test all statuses**:
   - Modify code to return `critical()` - verify red display
   - Modify code to return `warning()` - verify yellow display
   - Modify code to return `good()` - verify green display
3. **Test error handling** - Throw exception, verify it's caught
4. **Test filtering** - Filter by category, filter by status
5. **Test export** - Verify check appears in JSON/HTML

### Testing Example

```php
// Temporarily modify check for testing
protected function performCheck(): HealthCheckResult
{
    // Test critical
    return $this->critical('Testing critical status');

    // Test warning
    return $this->warning('Testing warning status');

    // Test good
    return $this->good('Testing good status');

    // Test exception handling
    throw new \Exception('Testing error handling');
}
```

Each should display correctly in the UI.

## Debugging

### Enable Joomla Debug Mode

For development, enable debug:

1. **System → Global Configuration**
2. **System** tab
3. Set **Debug System** to **Yes**
4. Set **Error Reporting** to **Maximum**
5. **Save & Close**

This shows useful debugging information.

### View PHP Errors

Check PHP error logs:

```bash
# macOS (MAMP)
tail -f /Applications/MAMP/logs/php_error.log

# Linux
tail -f /var/log/php/error.log

# Joomla error log
tail -f joomla/administrator/logs/error.php
```

### Debugging Tips

**Check executed or not**:
- Add `var_dump()` in `performCheck()` method
- Check Joomla debug console for output

**Check auto-discovered**:
- Enable debug mode
- Run health check
- Check that your check appears in the list

**Language key missing**:
- Check language file: `healthchecker/plugins/core/language/en-GB/plg_healthchecker_core.ini`
- Key format: `COM_HEALTHCHECKER_CHECK_{SLUG_UPPERCASE}_TITLE`
- For slug `core.my_check` → Key: `COM_HEALTHCHECKER_CHECK_CORE_MY_CHECK_TITLE`

### Clear Joomla Cache

If changes not appearing:

**Via Admin Panel**:
1. **System → Clear Cache**
2. Select all caches
3. Click **Delete**

**Via Command Line**:

```bash
rm -rf joomla/administrator/cache/*
rm -rf joomla/cache/*
```

## Building Packages

When ready to create installable ZIPs:

### Build All Packages

```bash
./build/build.sh 1.0.2
```

**Output**: `/build/dist/` directory

### What Gets Built

- `pkg_healthchecker-1.0.2.zip` - Unified installer (341 KB)
- `com_healthchecker-1.0.2.zip` - Component (63 KB)
- `mod_healthchecker-1.0.2.zip` - Module (8.4 KB)
- `plg_healthchecker_core-1.0.2.zip` - Core plugin (193 KB)
- `plg_healthchecker_example-1.0.2.zip` - Example plugin (7.8 KB)
- `plg_healthchecker_mysitesguru-1.0.2.zip` - MySites.guru plugin (35 KB)
- `plg_healthchecker_akeebabackup-1.0.2.zip` - Akeeba Backup plugin (15 KB)
- `plg_healthchecker_akeebaadmintools-1.0.2.zip` - Akeeba Admin Tools plugin (15 KB)

See [BUILD.md](BUILD.md) for complete build documentation.

## Git Workflow

### Feature Branch

```bash
# Create feature branch
git checkout -b feature/new-security-check

# Make changes in /healthchecker
vim healthchecker/plugins/core/src/Checks/Security/NewCheck.php

# Test thoroughly

# Run code quality
composer run cs:fix

# Commit
git add .
git commit -m "Add new security check for HTTPS enforcement"

# Push
git push origin feature/new-security-check

# Create Pull Request on GitHub
```

### Commit Messages

Use clear, descriptive commit messages:

✅ **Good**:
- `Add HTTPS enforcement check`
- `Fix database charset check false positive`
- `Update language strings for SEO category`

❌ **Bad**:
- `Update`
- `Fix bug`
- `Changes`

## IDE Setup

### PhpStorm

Recommended settings:

1. **Code Style**: Use Joomla code style
2. **PHP Inspections**: Enable all
3. **Composer**: Point to `/vendor`
4. **File Watchers**: Run ECS on save (optional)

### VS Code

Recommended extensions:

- **PHP Intelephense** - PHP language support
- **PHP Debug** - XDebug integration
- **EditorConfig** - Code style enforcement
- **Composer** - Composer integration

Create `.vscode/settings.json`:

```json
{
  "php.validate.executablePath": "/usr/local/bin/php",
  "php.suggest.basic": false,
  "intelephense.environment.phpVersion": "8.1"
}
```

## Troubleshooting

### Symlinks not working

**Problem**: Changes in `/healthchecker` not appearing in `/joomla`

**Solution**:

```bash
# Re-create symlinks
./build/setupSymlinks.sh

# Verify symlinks
ls -la joomla/administrator/components/
# Should show: com_healthchecker -> /Users/phil/.../healthchecker/component
```

### "Source directory not found"

**Problem**: Build script can't find `/healthchecker`

**Solution**:

```bash
# Verify directory exists
ls -la healthchecker/

# Run from project root
cd /Users/phil/Sites/health-checker-for-joomla
./build/build.sh
```

### Checks not auto-discovered

**Problem**: New check doesn't appear in health check list

**Checklist**:
1. File in correct location? `/healthchecker/plugins/core/src/Checks/{Category}/`
2. File name ends with `Check.php`?
3. Class extends `AbstractHealthCheck`?
4. Correct namespace? `MySitesGuru\HealthChecker\Plugin\Core\Checks\{Category}`
5. Clear Joomla cache

### Language key not showing

**Problem**: Check title shows as language key

**Solution**:

```bash
# Add to language file
vim healthchecker/plugins/core/language/en-GB/plg_healthchecker_core.ini

# Add key (all uppercase slug):
COM_HEALTHCHECKER_CHECK_CORE_MY_CHECK_TITLE="My Check Title"

# Clear cache
rm -rf joomla/administrator/cache/*

# Refresh browser
```

## Performance Tips

### Development Speed

- **Use symlinks** - No build step during development
- **Enable OPcache** - Faster PHP execution
- **Disable XDebug** - Unless actively debugging
- **Use local database** - Faster than remote MySQL

### Testing Speed

- **Test individual checks** - Use AJAX endpoint:
  ```
  /administrator/index.php?option=com_healthchecker&task=ajax.check&slug=core.debug_mode
  ```
- **Cache results** - Enable caching in component config
- **Filter results** - Test specific categories only

## Useful Commands

### View Symlinks

```bash
# List all Health Checker symlinks
ls -la joomla/administrator/components/com_healthchecker
ls -la joomla/administrator/modules/mod_healthchecker
ls -la joomla/plugins/healthchecker/
```

### Count Check Files

```bash
# Count core checks
find healthchecker/plugins/core/src/Checks -name "*Check.php" | wc -l
# Should be 130
```

### Search Checks

```bash
# Find checks containing "debug"
grep -r "debug" healthchecker/plugins/core/src/Checks/ --include="*.php"

# Find checks in Security category
ls healthchecker/plugins/core/src/Checks/Security/
```

### Clear Everything

```bash
# Clear all Joomla caches
rm -rf joomla/administrator/cache/*
rm -rf joomla/cache/*
rm -rf joomla/tmp/*

# Rebuild symlinks
./build/setupSymlinks.sh
```

## Next Steps

- Read [CLAUDE.md](CLAUDE.md) for complete AI assistant context
- Read [BUILD.md](BUILD.md) for build process details
- Read [IMPLEMENTATION.md](IMPLEMENTATION.md) for architecture
- See [CHECKS.md](CHECKS.md) for list of all checks

## Getting Help

- **GitHub Issues**: https://github.com/mySites-guru/HealthCheckerForJoomla/issues
- **Joomla Docs**: https://docs.joomla.org/
- **PHP Docs**: https://www.php.net/docs.php
