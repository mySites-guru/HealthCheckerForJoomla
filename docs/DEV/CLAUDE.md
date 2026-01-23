# Health Checker for Joomla - Claude AI Context

This file provides context for AI assistants (like Claude) working on the Health Checker for Joomla codebase.

## Project Overview

**Health Checker for Joomla** is a comprehensive site health monitoring extension for Joomla 5+. It provides **130 health checks** across **8+ categories** with an extensible plugin architecture.

**License**: GPL v2+ (same as Joomla)
**Requirements**: Joomla 5.0+, PHP 8.1+
**Repository**: https://github.com/mySites-guru/HealthCheckerForJoomla
**Current Version**: 3.0.23

## Actual Directory Structure

The project uses a **source + build** pattern:

```
/Users/phil/Sites/health-checker-for-joomla/
├── healthchecker/              # SOURCE (development happens here)
│   ├── component/              # com_healthchecker source
│   ├── module/                 # mod_healthchecker source
│   └── plugins/                # All plugin sources
│       ├── core/               # 130 check files
│       ├── example/            # SDK reference for developers
│       ├── mysitesguru/        # mySites.guru API integration
│       ├── akeebabackup/       # Akeeba Backup monitoring
│       └── akeebaadmintools/   # Akeeba Admin Tools monitoring
│
├── joomla/                     # Joomla installation (symlinked to /healthchecker)
│   ├── administrator/
│   │   ├── components/com_healthchecker → /healthchecker/component
│   │   └── modules/mod_healthchecker → /healthchecker/module
│   └── plugins/healthchecker/
│       ├── core → /healthchecker/plugins/core
│       ├── example → /healthchecker/plugins/example
│       ├── mysitesguru → /healthchecker/plugins/mysitesguru
│       ├── akeebabackup → /healthchecker/plugins/akeebabackup
│       └── akeebaadmintools → /healthchecker/plugins/akeebaadmintools
│
├── build/                      # Build scripts and output
│   ├── build.sh                # Main build script
│   ├── setupSymlinks.sh        # Creates symlinks joomla/ → healthchecker/
│   └── dist/                   # Built packages (not in git)
│       ├── pkg_healthchecker-{VERSION}.zip
│       ├── com_healthchecker-{VERSION}.zip
│       ├── mod_healthchecker-{VERSION}.zip
│       ├── plg_healthchecker_core-{VERSION}.zip
│       ├── plg_healthchecker_example-{VERSION}.zip
│       ├── plg_healthchecker_mysitesguru-{VERSION}.zip
│       ├── plg_healthchecker_akeebabackup-{VERSION}.zip
│       └── plg_healthchecker_akeebaadmintools-{VERSION}.zip
│
├── vendor/                     # Composer dependencies
├── composer.json               # ECS, Rector for code quality
├── rector.php                  # PHP modernization config
└── docs/                       # Documentation
    ├── USER/                   # End-user docs (VitePress)
    └── DEV/                    # Developer docs (you are here)
```

## Architecture

### Core Components

**Component (com_healthchecker)** - Infrastructure
- Event dispatcher (3 events)
- `HealthCheckRunner` service
- AJAX controller (5 endpoints)
- Caching layer
- HTML report view
- JSON API responses

**Module (mod_healthchecker)** - Dashboard Widget
- Administrator module only
- AJAX-loaded stats
- Configurable cache TTL
- Toggle visibility per status type

**Core Plugin (plg_healthchecker_core)** - 130 PHP files
- Auto-discovery via directory scan
- 130 checks across 8+ categories
- Each check = 1 PHP file extending `AbstractHealthCheck`

**Example Plugin (plg_healthchecker_example)** - SDK Reference
- Demonstrates custom categories
- Shows provider metadata
- Two example checks

**MySites.guru Plugin (plg_healthchecker_mysitesguru)** - API Integration
- Connection check to monitoring service
- Custom category with branding
- Provider logo demonstration

**Akeeba Plugins** - Optional Integrations
- `akeebabackup` - Monitors backup status/age
- `akeebaadmintools` - Security monitoring
- Auto-enabled only if dependencies installed

## Key Design Principles

1. **Event-Driven Discovery** - Plugins register via Symfony events, not hardcoded
2. **Auto-Discovery** - Core plugin scans `/Checks/{Category}/` directories
3. **No Database Tables** - Results cached in session/transient only
4. **Super Admin Only** - Strict ACL enforcement
5. **One Check = One File** - Easy to add/remove checks
6. **Provider Attribution** - Third-party checks show branding

## Development Workflow

### Setup Development Environment

```bash
# Clone repository
cd /Users/phil/Sites/health-checker-for-joomla

# Setup symlinks (joomla/ → healthchecker/)
./build/setupSymlinks.sh

# Edit files in healthchecker/
vim healthchecker/plugins/core/src/Checks/Security/DebugModeCheck.php

# Changes reflect immediately in joomla/ via symlinks
# Test at: http://yourjoomla.local/administrator
```

### Making Code Changes

**Work in**: `healthchecker/` directory
**Test in**: `joomla/` (symlinked automatically)
**Build from**: `./build/build.sh 1.0.2`

### Adding a New Check

1. **Choose category**: Use existing or create new in plugin
2. **Create file**: `healthchecker/plugins/core/src/Checks/{Category}/{CheckName}.php`
3. **Extend base class**: `AbstractHealthCheck`
4. **Implement methods**:
   - `getSlug()` - Unique identifier
   - `getCategory()` - Category slug
   - `getProvider()` - Provider slug (usually 'core')
   - `performCheck()` - Check logic, returns `HealthCheckResult`
5. **Add language key**: `healthchecker/plugins/core/language/en-GB/plg_healthchecker_core.ini`
6. **Test**: Refresh admin panel, run health check

**No registration needed** - auto-discovery handles it.

## Auto-Discovery System

**How it works** (`CorePlugin::discoverChecks()`):

```php
1. Scan: /healthchecker/plugins/core/src/Checks/
2. Find subdirectories: Security/, Database/, System/, etc.
3. For each directory:
   - Find all *Check.php files
   - Convert path to class name via PSR-4
   - Instantiate: new $className()
   - Inject database if setDatabase() exists
4. Return array of check instances
```

**Example**:
```
File: Checks/Security/DebugModeCheck.php
→ Namespace: MySitesGuru\HealthChecker\Plugin\Core\Checks\Security
→ Class: DebugModeCheck
→ Auto-instantiated and registered
```

## Event System

Three events dispatched by component:

### 1. `onHealthCheckerCollectProviders`
**Purpose**: Register provider metadata (branding)

```php
$event->addResult(new ProviderMetadata(
    slug: 'myplugin',
    name: 'My Plugin',
    description: 'Custom health checks',
    url: 'https://example.com',
    icon: 'fa-puzzle-piece',
    logoUrl: 'plugins/healthchecker/myplugin/media/logo.svg',
    version: '1.0.0'
));
```

### 2. `onHealthCheckerCollectCategories`
**Purpose**: Register custom categories

```php
$event->addResult(new HealthCategory(
    slug: 'customcat',
    label: 'COM_HEALTHCHECKER_CATEGORY_CUSTOMCAT',
    icon: 'fa-cog',
    sortOrder: 100
));
```

### 3. `onHealthCheckerCollectChecks`
**Purpose**: Register health check instances

```php
$check = new MyCustomCheck();
$check->setDatabase($this->getDatabase());
$event->addResult($check);
```

## AJAX API Endpoints

Component provides 5 AJAX endpoints:

| Endpoint | Purpose | Returns |
|----------|---------|---------|
| `ajax.metadata` | Get check metadata without running | JSON: checks, categories, providers |
| `ajax.check&slug=XXX` | Run single check by slug | JSON: single check result |
| `ajax.stats` | Get summary counts (cached) | JSON: critical/warning/good counts |
| `ajax.run` | Run ALL checks, get full results | JSON: complete results array |
| `ajax.clearCache` | Clear cached results | JSON: success message |

**URL Pattern**: `/administrator/index.php?option=com_healthchecker&task=ajax.{endpoint}`

## Build Process

### Build Script

**Command**: `./build/build.sh [VERSION]`

**What it does**:
1. Validates `healthchecker/` source exists
2. Cleans `build/dist/` directory
3. Copies files from `healthchecker/` → `build/dist/tmp/`
4. Updates version numbers in XML manifests
5. Creates individual ZIPs:
   - Component
   - Module
   - 5 plugins (core, example, mysitesguru, akeebabackup, akeebaadmintools)
6. Creates unified package `pkg_healthchecker-{VERSION}.zip` with install script
7. Cleans up temp files

**Output**: 8 installable ZIP files in `build/dist/`

### Package Install Script

The unified package includes `script.php` that:
- Checks Joomla 5.0+ requirement
- Checks PHP 8.1+ requirement
- Auto-enables core plugin
- Auto-enables example plugin
- Auto-enables mysitesguru plugin
- **Conditionally** enables Akeeba plugins if dependencies detected

## Code Conventions

### Naming Conventions

**Check Slugs**: `{provider}.{check_name}`
- Format: lowercase, underscores
- Examples: `core.debug_mode`, `core.php_version`, `mysitesguru.connection`

**Language Keys**: `COM_HEALTHCHECKER_CHECK_{SLUG_UPPERCASE}_TITLE`
- Example: `core.debug_mode` → `COM_HEALTHCHECKER_CHECK_CORE_DEBUG_MODE_TITLE`

**Provider Slugs**: Lowercase, single word or underscores
- Examples: `core`, `mysitesguru`, `akeeba_backup`, `example`

**File Names**: `{CheckName}Check.php`
- Must end with `Check.php` for auto-discovery
- Examples: `DebugModeCheck.php`, `PhpVersionCheck.php`

### Check Class Template

```php
<?php
namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Security;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

defined('_JEXEC') or die;

/**
 * [Check Name] Health Check
 *
 * [Brief description of what this checks]
 *
 * WHY THIS CHECK IS IMPORTANT:
 * [Explain risks, benefits, why it matters]
 *
 * RESULT MEANINGS:
 *
 * GOOD: [What conditions produce good status]
 *
 * WARNING: [What triggers warning, how to resolve]
 *
 * CRITICAL: [What triggers critical, immediate actions needed]
 *           [Or: "This check does not return critical status."]
 */
final class MyCheck extends AbstractHealthCheck
{
    public function getSlug(): string
    {
        return 'core.my_check';
    }

    public function getCategory(): string
    {
        return 'security'; // or: system, database, users, etc.
    }

    public function getProvider(): string
    {
        return 'core';
    }

    protected function performCheck(): HealthCheckResult
    {
        // Check logic here
        $db = $this->getDatabase(); // Available via DatabaseAwareTrait

        // Return one of:
        return $this->critical('Site is broken');
        return $this->warning('Should fix this');
        return $this->good('All OK');
    }
}
```

### File Header Template

```php
<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  HealthChecker.Core
 *
 * @copyright   (C) 2026 mySites.guru / Phil E. Taylor
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
```

## Health Check Categories

8 built-in categories (from CoreCategories class):

| Slug | Label | Icon | Sort | Check Count |
|------|-------|------|------|-------------|
| `system` | System & Hosting | `fa-server` | 10 | 33 |
| `database` | Database | `fa-database` | 20 | 18 |
| `security` | Security | `fa-shield-halved` | 30 | 21 |
| `users` | Users | `fa-users` | 40 | 12 |
| `extensions` | Extensions | `fa-puzzle-piece` | 50 | 13 |
| `performance` | Performance | `fa-gauge-high` | 60 | 10 |
| `seo` | SEO | `fa-magnifying-glass` | 70 | 12 |
| `content` | Content Quality | `fa-file-lines` | 80 | 11 |

**Total**: 130 check files (one check per file)

## API Quick Reference

### Core Interfaces

```php
// Check interface
interface HealthCheckInterface {
    public function getSlug(): string;
    public function getTitle(): string;
    public function getCategory(): string;
    public function getProvider(): string;
    public function run(): HealthCheckResult;
}

// Base class (extend this)
abstract class AbstractHealthCheck implements HealthCheckInterface {
    use DatabaseAwareTrait; // Provides getDatabase()

    abstract protected function performCheck(): HealthCheckResult;

    protected function critical(string $desc): HealthCheckResult;
    protected function warning(string $desc): HealthCheckResult;
    protected function good(string $desc): HealthCheckResult;

    public function getTitle(): string; // Auto-generated from language key
    public function getProvider(): string; // Default: 'core'
}

// Result status
enum HealthStatus: string {
    case Critical = 'critical';
    case Warning = 'warning';
    case Good = 'good';
}

// Result object
final readonly class HealthCheckResult {
    public function __construct(
        public HealthStatus $status,
        public string $title,
        public string $description,
        public string $slug,
        public string $category,
        public string $provider = 'core',
    );
}

// Provider metadata
final readonly class ProviderMetadata {
    public function __construct(
        public string $slug,
        public string $name,
        public string $description = '',
        public ?string $url = null,
        public ?string $icon = null,
        public ?string $logoUrl = null,
        public ?string $version = null,
    );
}

// Category
final readonly class HealthCategory {
    public function __construct(
        public string $slug,
        public string $label,
        public string $icon,
        public int $sortOrder = 50
    );
}
```

## Common Tasks

### Add New Core Check

```bash
# 1. Create check file
vim healthchecker/plugins/core/src/Checks/Security/NewCheck.php

# 2. Add language key
vim healthchecker/plugins/core/language/en-GB/plg_healthchecker_core.ini
# Add: COM_HEALTHCHECKER_CHECK_CORE_NEW_CHECK_TITLE="My New Check"

# 3. Test (auto-discovered, no registration needed)
open http://yourjoomla.local/administrator
# Navigate to: Components → Health Checker → Run Health Check

# 4. Build package
./build/build.sh 1.0.2
```

### Create Third-Party Plugin

```bash
# 1. Create plugin structure
mkdir -p healthchecker/plugins/myplugin/src/{Extension,Checks}

# 2. Follow example plugin pattern
cp -r healthchecker/plugins/example/* healthchecker/plugins/myplugin/

# 3. Implement your checks

# 4. Add to build script
vim build/build.sh
# Add 'myplugin' to plugin loop

# 5. Build
./build/build.sh 1.0.0
```

### Run Code Quality Checks

```bash
# Fix code style (ECS)
composer run cs:fix

# Modernize code (Rector)
composer run rector

# Check for issues
composer run cs:check
```

## Testing Checklist

Before committing changes:

- [ ] Run health check - all checks execute
- [ ] Test all statuses - critical/warning/good work
- [ ] Filter by status - all filters work
- [ ] Filter by category - all categories work
- [ ] Export JSON - valid JSON output
- [ ] Export HTML - renders correctly
- [ ] Dashboard widget - shows counts
- [ ] Language strings - no keys displayed
- [ ] Provider badges - third-party checks show branding
- [ ] Code style - `composer run cs:check` passes

## Current Implementation Status

| Component | Status | Files | Notes |
|-----------|--------|-------|-------|
| Core Infrastructure | ✅ Complete | ~30 | Component, events, runner, caching |
| Core Checks | ✅ Complete | 130 | 130 checks across 8+ categories |
| Dashboard Module | ✅ Complete | ~5 | AJAX-driven admin widget |
| Example Plugin | ✅ Complete | ~5 | SDK reference for developers |
| MySites.guru Plugin | ✅ Complete | ~5 | API integration with branding |
| Akeeba Integrations | ✅ Complete | ~10 | Backup + Admin Tools monitoring |
| Build System | ✅ Complete | 2 scripts | Full automation + symlink setup |
| Package Installer | ✅ Complete | script.php | Smart plugin enabling |
| Unit Tests | ✅ Complete | 20 | 309 tests, 811 assertions |
| Frontend Display | ❌ None | 0 | Admin-only currently |
| Email Alerts | ❌ None | 0 | Not implemented |
| Scheduled Checks | ❌ None | 0 | Not implemented |

## What's NOT Implemented

- ❌ **Frontend Component** - Admin-only currently
- ❌ **Email Notifications** - No alerting system
- ❌ **Scheduled Checks** - Manual execution only
- ❌ **Historical Tracking** - No result history
- ❌ **Webhooks** - No external integrations beyond mySites.guru
- ❌ **One-Click Fixes** - Checks report only, no remediation
- ❌ **Docker Environment** - No docker-compose.yml exists
- ❌ **API Authentication** - Relies on Joomla session only

## Resources

- **Source Code**: `/healthchecker/` directory
- **Build Output**: `/build/dist/` directory
- **Joomla Install**: `/joomla/` directory (symlinked)
- **Example Plugin**: `healthchecker/plugins/example/`
- **Core Checks**: `healthchecker/plugins/core/src/Checks/`
- **Build Script**: `build/build.sh`
- **Symlink Setup**: `build/setupSymlinks.sh`

## When Working on This Codebase

1. **Edit files in `healthchecker/`** - Changes reflect via symlinks
2. **Test in `joomla/`** - Symlinks point to source
3. **Build packages** - `./build/build.sh X.X.X`
4. **Follow auto-discovery** - Files in `Checks/{Category}/` are auto-loaded
5. **Include documentation header** - Every check needs WHY/GOOD/WARNING/CRITICAL
6. **Run code quality** - `composer run cs:fix` before committing
7. **Update version** - Pass version to build script

## Questions to Ask User

If uncertain about:
- **New check placement**: "Should this be Security or Extensions category?"
- **Status level**: "Should X condition be warning or critical?"
- **Database queries**: "Is it OK to query table Y? Not a core Joomla table."
- **Third-party API**: "Should we check external service Z? Could add latency."
- **Breaking changes**: "This changes the API. Should we bump major version?"

## License

GPL v2 or later - same as Joomla core.

All files must include GPL header as shown above.
