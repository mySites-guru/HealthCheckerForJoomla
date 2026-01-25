# Health Checker for Joomla - Implementation Plan

## Overview

Build a Joomla 5+ component (`com_healthchecker`) with 129 health checks using event-driven auto-discovery architecture. Each check is an isolated file that can be added/removed without code changes elsewhere.

---

## Architecture Summary

```
com_healthchecker (Component)     - Admin UI, models, controllers, events
plg_healthchecker_core (Plugin)   - 129 built-in checks, auto-discovered
mod_healthchecker (Module)        - Dashboard widget
```

**Key Design Decisions:**
- Checks discovered via Symfony Event Dispatcher (`onHealthCheckerCollectChecks`)
- Each check is one PHP file extending `AbstractHealthCheck`
- Core checks live in a plugin (same pattern as third-party extensions)
- No database tables - session-based results only
- Super Admin access only

---

## Development Approach

- **Build in `healthchecker/`** for development (symlinked to `joomla/` for testing)
- **Build script** extracts files to create installable ZIP
- **Full UI from start** - collapsible categories, filters, export
- **Single core plugin** - all 129 checks in `plg_healthchecker_core`

---

## File Structure

```
/
├── docker-compose.yml                       # Development environment
├── .env                                     # Environment config
├── README.md                                # Setup instructions
├── build/
│   └── docker/
│       └── Caddyfile                        # FrankenPHP/Caddy config

healthchecker/
├── component/                                   # com_healthchecker
│   ├── healthchecker.xml                    # Manifest
│   ├── access.xml                           # ACL
│   ├── config.xml                           # Configuration form
│   ├── services/provider.php                # DI container
│   ├── presets/healthchecker.xml            # Dashboard preset
│   ├── src/
│   │   ├── Extension/HealthCheckerComponent.php
│   │   ├── Controller/DisplayController.php
│   │   ├── Model/ReportModel.php
│   │   ├── View/Report/HtmlView.php
│   │   ├── Service/
│   │   │   ├── HealthCheckRunner.php
│   │   │   └── CategoryRegistry.php
│   │   ├── Event/
│   │   │   ├── CollectChecksEvent.php
│   │   │   ├── CollectCategoriesEvent.php
│   │   │   └── CollectProvidersEvent.php
│   │   ├── Check/
│   │   │   ├── HealthCheckInterface.php
│   │   │   ├── AbstractHealthCheck.php
│   │   │   ├── HealthCheckResult.php
│   │   │   └── HealthStatus.php
│   │   ├── Category/HealthCategory.php
│   │   ├── Provider/
│   │   │   ├── ProviderMetadata.php
│   │   │   └── ProviderRegistry.php
│   │   └── Collection/HealthCheckCollection.php
│   ├── tmpl/report/default.php
│   └── language/en-GB/
│       ├── com_healthchecker.ini
│       └── com_healthchecker.sys.ini
│
├── plugins/healthchecker/core/
│   ├── core.xml                             # Plugin manifest
│   ├── services/provider.php
│   ├── src/
│   │   ├── Extension/CorePlugin.php         # Event subscriber
│   │   ├── Categories/CoreCategories.php
│   │   └── Checks/
│   │       ├── System/                      # 33 checks
│   │       ├── Database/                    # 18 checks
│   │       ├── Security/                    # 21 checks
│   │       ├── Users/                       # 12 checks
│   │       ├── Extensions/                  # 13 checks
│   │       ├── Performance/                 # 10 checks
│   │       ├── Seo/                         # 12 checks
│   │       └── Content/                     # 11 checks
│   └── language/en-GB/plg_healthchecker_core.ini
│
├── plugins/healthchecker/example/
│   ├── example.xml                          # Plugin manifest
│   ├── services/provider.php
│   ├── src/
│   │   ├── Extension/ExamplePlugin.php      # Event subscriber
│   │   └── Checks/
│   │       ├── CustomCategory/
│   │       │   ├── ExampleCheck.php
│   │       │   └── AnotherExampleCheck.php
│   │       └── System/
│   │           └── CustomSystemCheck.php
│   ├── assets/logo.svg
│   └── language/en-GB/plg_healthchecker_example.ini
│
├── administrator/modules/mod_healthchecker/
│   ├── mod_healthchecker.xml
│   ├── services/provider.php
│   ├── src/
│   │   ├── Dispatcher/Dispatcher.php
│   │   └── Helper/HealthCheckerHelper.php
│   ├── tmpl/default.php
│   └── language/en-GB/mod_healthchecker.ini
│
build/
├── build.php                                # Build script to create ZIP
└── pkg_healthchecker.xml                    # Package manifest template

docs/
└── DEVELOPERS.md                            # Third-party developer guide
```

---

## Parallel Development Workstreams

### Workstream 0: Docker Development Environment
**Files touched:** `docker-compose.yml`, `build/docker/Caddyfile`, `.env`
**No dependencies** - MUST complete first before any other work

Sets up local development environment using FrankenPHP and MySQL (based on prime-dev patterns).

- [ ] Create `docker-compose.yml` with frankenphp + mysql services
- [ ] Create `build/docker/Caddyfile` for web server config
- [ ] Create `.env` with default values
- [ ] Configure OrbStack domain labels
- [ ] Configure custom network for service communication
- [ ] Configure MySQL with persistent volume and healthcheck
- [ ] Test Joomla accessible at https://healthchecker.local
- [ ] Document setup in README.md

#### docker-compose.yml Structure
```yaml
services:
  frankenphp:
    image: philetaylor/frankenphp-base:latest
    container_name: healthchecker-frankenphp
    labels:
      dev.orbstack.domains: healthchecker.local
    environment:
      SERVER_NAME: ":80"
      APP_ENV: "dev"
    volumes:
      - ./build/docker/Caddyfile:/etc/caddy/Caddyfile
      - .:/app
    extra_hosts:
      - "db.healthchecker.local:host-gateway"
    networks:
      healthchecker:
        ipv4_address: 10.2.0.2
    restart: unless-stopped
    depends_on:
      mysql:
        condition: service_healthy

  mysql:
    image: mysql:8.0
    container_name: healthchecker-mysql
    labels:
      dev.orbstack.domains: db.healthchecker.local
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: joomla
      MYSQL_USER: joomla
      MYSQL_PASSWORD: joomla
    volumes:
      - mysql_data:/var/lib/mysql
    networks:
      healthchecker:
        ipv4_address: 10.2.0.3
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 5s
      timeout: 5s
      retries: 5
    restart: unless-stopped

networks:
  healthchecker:
    driver: bridge
    ipam:
      config:
        - subnet: 10.2.0.0/24

volumes:
  mysql_data:
```

#### build/docker/Caddyfile
```
{
    frankenphp
}

healthchecker.local, localhost {
    root * /app/Joomla_6

    tls internal

    encode zstd br gzip

    php_server
    file_server

    header {
        X-Frame-Options "SAMEORIGIN"
        X-XSS-Protection "1; mode=block"
        X-Content-Type-Options "nosniff"
    }
}

:80 {
    root * /app/Joomla_6
    encode zstd br gzip
    php_server
    file_server
}
```

---

### Workstream 1: Core Component Foundation
**Files touched:** `com_healthchecker/*`
**No dependencies** - can start immediately

- [ ] Create component manifest (`healthchecker.xml`)
- [ ] Create `access.xml` with Super Admin ACL
- [ ] Create `config.xml` (minimal config)
- [ ] Create service provider with DI registrations
- [ ] Create `HealthCheckerComponent` extension class
- [ ] Create `DisplayController`
- [ ] Create `ReportModel`
- [ ] Create `Report/HtmlView.php`
- [ ] Create `tmpl/report/default.php` template
- [ ] Create language files
- [ ] Create dashboard preset

### Workstream 2: Check Infrastructure (Interfaces & Events)
**Files touched:** `com_healthchecker/src/Check/*`, `com_healthchecker/src/Event/*`, `com_healthchecker/src/Provider/*`
**No dependencies** - can start immediately

- [ ] Create `HealthStatus` enum
- [ ] Create `HealthCheckResult` value object (includes `provider` field)
- [ ] Create `HealthCheckInterface` (includes `getProvider()` method)
- [ ] Create `AbstractHealthCheck` base class
- [ ] Create `HealthCategory` value object
- [ ] Create `HealthCheckCollection`
- [ ] Create `CollectChecksEvent`
- [ ] Create `CollectCategoriesEvent`
- [ ] Create `CollectProvidersEvent`
- [ ] Create `ProviderMetadata` value object
- [ ] Create `ProviderRegistry` service
- [ ] Create `HealthCheckRunner` service
- [ ] Create `CategoryRegistry` service

### Workstream 3: Core Plugin & Auto-Discovery
**Files touched:** `plugins/healthchecker/core/*`
**Depends on:** Workstream 2 (interfaces)

- [ ] Create plugin manifest (`core.xml`)
- [ ] Create plugin service provider
- [ ] Create `CorePlugin` with event subscriber
- [ ] Create `CoreCategories` with 8 categories
- [ ] Implement auto-discovery in `CorePlugin::discoverChecks()`
- [ ] Create plugin language file

### Workstream 4: System Checks (27 checks)
**Files touched:** `plugins/healthchecker/core/src/Checks/System/*`
**Depends on:** Workstream 2 (AbstractHealthCheck)

- [ ] PhpVersionCheck
- [ ] MemoryLimitCheck
- [ ] MaxExecutionTimeCheck
- [ ] MaxInputTimeCheck
- [ ] MaxInputVariablesCheck
- [ ] PostMaxSizeCheck
- [ ] UploadMaxFilesizeCheck
- [ ] OutputBufferingCheck
- [ ] JsonExtensionCheck
- [ ] SimpleXmlExtensionCheck
- [ ] DomExtensionCheck
- [ ] PdoMysqlExtensionCheck
- [ ] GdOrImagickCheck
- [ ] ZipExtensionCheck
- [ ] MbstringExtensionCheck
- [ ] IntlExtensionCheck
- [ ] CurlExtensionCheck
- [ ] OpenSslExtensionCheck
- [ ] FileinfoExtensionCheck
- [ ] DiskSpaceCheck
- [ ] TempDirectoryWritableCheck
- [ ] ServerTimeSyncCheck
- [ ] PhpSapiCheck
- [ ] OpcacheStatusCheck
- [ ] RealpathCacheCheck
- [ ] OverdueScheduledTasksCheck
- [ ] FailedScheduledTasksCheck

### Workstream 5: Database Checks (13 checks)
**Files touched:** `plugins/healthchecker/core/src/Checks/Database/*`
**Depends on:** Workstream 2 (AbstractHealthCheck)

- [ ] ConnectionCheck
- [ ] ServerVersionCheck
- [ ] UserPrivilegesCheck
- [ ] ConnectionCharsetCheck
- [ ] TableEngineConsistencyCheck
- [ ] TableCharsetCollationCheck
- [ ] TableStatusCheck
- [ ] AutoIncrementHeadroomCheck
- [ ] OrphanedTablesCheck
- [ ] SqlModeCheck
- [ ] MaxAllowedPacketCheck
- [ ] WaitTimeoutCheck
- [ ] TablePrefixCheck

### Workstream 6: Security Checks (20 checks)
**Files touched:** `plugins/healthchecker/core/src/Checks/Security/*`
**Depends on:** Workstream 2 (AbstractHealthCheck)

- [ ] XFrameOptionsCheck
- [ ] XContentTypeOptionsCheck
- [ ] XXssProtectionCheck
- [ ] ContentSecurityPolicyCheck
- [ ] ReferrerPolicyCheck
- [ ] PermissionsPolicyCheck
- [ ] ConfigPermissionsCheck
- [ ] ConfigLocationCheck
- [ ] WritableDirectoriesCheck
- [ ] IndexHtmlInDirectoriesCheck
- [ ] HtaccessPresentCheck
- [ ] InstallationDirectoryCheck
- [ ] BackupFilesExposedCheck
- [ ] DebugModeCheck
- [ ] ErrorReportingCheck
- [ ] FtpLayerCheck
- [ ] SecretKeyCheck
- [ ] DatabasePasswordStrengthCheck
- [ ] TwoFactorAuthCheck
- [ ] PasswordPolicyCheck

### Workstream 7: Users Checks (14 checks)
**Files touched:** `plugins/healthchecker/core/src/Checks/Users/*`
**Depends on:** Workstream 2 (AbstractHealthCheck)

- [ ] SuperAdminCountCheck
- [ ] DefaultUsernameCheck
- [ ] SuperAdminEmailUniqueCheck
- [ ] SuperAdminLastLoginCheck
- [ ] InactiveAdminAccountsCheck
- [ ] BlockedUsersCountCheck
- [ ] UnactivatedUsersCheck
- [ ] DuplicateEmailsCheck
- [ ] UserRegistrationCheck
- [ ] NewUserGroupCheck
- [ ] GuestUserGroupCheck
- [ ] CaptchaOnRegistrationCheck
- [ ] SessionLifetimeCheck
- [ ] SharedSessionsCheck

### Workstream 8: Extensions Checks (16 checks)
**Files touched:** `plugins/healthchecker/core/src/Checks/Extensions/*`
**Depends on:** Workstream 2 (AbstractHealthCheck)

- [ ] JoomlaCoreVersionCheck
- [ ] UpdateServerAccessibleCheck
- [ ] UpdateChannelCheck
- [ ] ExtensionsWithUpdatesCheck
- [ ] DisabledExtensionsCheck
- [ ] MissingExtensionFilesCheck
- [ ] ExtensionUpdateSitesCheck
- [ ] UnsignedExtensionsCheck
- [ ] PhpCompatibilityCheck
- [ ] JoomlaCompatibilityCheck
- [ ] DeprecatedExtensionsCheck
- [ ] KnownVulnerableExtensionsCheck
- [ ] SystemPluginsOrderCheck
- [ ] ConflictingPluginsCheck
- [ ] OrphanedPluginFilesCheck
- [ ] OverridesNeedingReviewCheck

### Workstream 9: Performance Checks (14 checks)
**Files touched:** `plugins/healthchecker/core/src/Checks/Performance/*`
**Depends on:** Workstream 2 (AbstractHealthCheck)

- [ ] SystemCacheCheck
- [ ] CacheHandlerCheck
- [ ] PageCacheCheck
- [ ] CacheTimeCheck
- [ ] CacheDirectoryWritableCheck
- [ ] GzipCompressionCheck
- [ ] CssJsCompressionCheck
- [ ] ImageOptimizationCheck
- [ ] LazyLoadingCheck
- [ ] DebugPluginCheck
- [ ] DebugLanguageCheck
- [ ] SefUrlsCheck
- [ ] UrlRewritingCheck
- [ ] PhpOpcacheCheck

### Workstream 10: SEO Checks (12 checks)
**Files touched:** `plugins/healthchecker/core/src/Checks/Seo/*`
**Depends on:** Workstream 2 (AbstractHealthCheck)

- [ ] SiteMetaDescriptionCheck
- [ ] DefaultMetaKeywordsCheck
- [ ] SiteNameConfiguredCheck
- [ ] RobotsMetaTagCheck
- [ ] SefUrlsEnabledCheck
- [ ] UrlRewritingActiveCheck
- [ ] CanonicalUrlsCheck
- [ ] SitemapPresentCheck
- [ ] ArticlesMissingMetaCheck
- [ ] ImagesMissingAltCheck
- [ ] BrokenInternalLinksCheck
- [ ] DuplicateTitleTagsCheck

### Workstream 11: Content Checks (10 checks)
**Files touched:** `plugins/healthchecker/core/src/Checks/Content/*`
**Depends on:** Workstream 2 (AbstractHealthCheck)

- [ ] OrphanedArticlesCheck
- [ ] EmptyCategoriesCheck
- [ ] StaleDraftsCheck
- [ ] BrokenImagesCheck
- [ ] BrokenMenuLinksCheck
- [ ] OrphanedMenuItemsCheck
- [ ] UnpublishedContentLinksCheck
- [ ] DuplicateMenuAliasesCheck
- [ ] UnusedMediaFilesCheck
- [ ] MissingMediaReferencesCheck

### Workstream 12: Dashboard Module
**Files touched:** `mod_healthchecker/*`
**Depends on:** Workstream 1 & 2 (component and interfaces)

- [ ] Create module manifest
- [ ] Create service provider
- [ ] Create `Dispatcher`
- [ ] Create `HealthCheckerHelper`
- [ ] Create `tmpl/default.php` template
- [ ] Create language file

### Workstream 13: Third-Party Developer SDK & Documentation
**Files touched:** `com_healthchecker/src/Extension/*`, documentation files
**No dependencies** - can start in parallel with Workstream 2

This workstream designs the public API for third-party developers to hook in their own health checks.

- [ ] Create `HealthCheckProviderInterface` for third-party plugins
- [ ] Create `ProviderMetadata` value object (name, logo, icon, description, url)
- [ ] Create `CollectProvidersEvent` for provider registration
- [ ] Update `CollectChecksEvent` to include provider context
- [ ] Create example third-party plugin (`plg_healthchecker_example`)
- [ ] Write developer documentation (`DEVELOPERS.md`)
- [ ] Create provider attribution UI in report (shows which plugin added which checks)

#### Third-Party Plugin Structure
```
plugins/healthchecker/myplugin/
├── myplugin.xml                           # Plugin manifest
├── services/provider.php
├── src/
│   ├── Extension/MyPlugin.php             # Implements SubscriberInterface
│   └── Checks/
│       └── MyCustomCheck.php              # Extends AbstractHealthCheck
├── assets/
│   └── logo.svg                           # Provider logo (optional)
└── language/en-GB/plg_healthchecker_myplugin.ini
```

#### Provider Metadata Structure
```php
final class ProviderMetadata
{
    public function __construct(
        public readonly string $slug,           // 'myplugin'
        public readonly string $name,           // 'My Awesome Plugin'
        public readonly string $description,    // 'Adds e-commerce health checks'
        public readonly ?string $url,           // 'https://example.com/myplugin'
        public readonly ?string $icon,          // 'fa-shopping-cart'
        public readonly ?string $logoUrl,       // URL or relative path to logo
        public readonly ?string $version,       // '1.0.0'
    ) {}
}
```

#### Event Hooks for Developers
| Event | Purpose |
|-------|---------|
| `onHealthCheckerCollectProviders` | Register provider metadata |
| `onHealthCheckerCollectCategories` | Add custom categories |
| `onHealthCheckerCollectChecks` | Add health checks |
| `onHealthCheckerBeforeRun` | Pre-run hook (future Pro) |
| `onHealthCheckerAfterRun` | Post-run hook (future Pro) |

### Workstream 14: Package & Installation
**Files touched:** `pkg_healthchecker.xml`, build scripts
**Depends on:** All workstreams complete

- [ ] Create package manifest
- [ ] Create build script (`build/build.php`)
- [ ] Create installation script
- [ ] Test fresh install
- [ ] Test upgrade install
- [ ] Verify all checks execute

---

## UI Design (Full from Start)

**Design Principle**: Use native Joomla 5+ admin UI components exclusively. No custom CSS frameworks or JavaScript libraries. The extension should feel like a core Joomla component.

### Joomla Admin Components to Use

| UI Element | Joomla Component |
|------------|------------------|
| Page layout | `JLayout` with `joomla.content.options_default` |
| Toolbar | `Joomla\CMS\Toolbar\Toolbar` with standard buttons |
| Filters | `Joomla\CMS\Form\Form` with `searchtools` layout |
| Badges | Bootstrap 5 badges (`badge bg-danger`, `bg-warning`, `bg-success`) |
| Accordions | Bootstrap 5 accordion (`accordion`, `accordion-item`) |
| Cards | Bootstrap 5 cards for summary stats |
| Icons | FontAwesome 6 (bundled with Joomla 5+) |
| Alerts | `Joomla\CMS\Layout\LayoutHelper::render('joomla.alert')` |
| Tables | Standard Joomla admin table markup |
| Tooltips | Bootstrap 5 tooltips via `data-bs-toggle="tooltip"` |
| Dropdowns | Bootstrap 5 dropdowns for export menu |

### Provider Attribution (Third-Party Checks)
- **Badge on each check**: Bootstrap `badge bg-secondary` with provider name
- **Tooltip on hover**: Bootstrap tooltip with provider description, version, link
- **Footer credits**: Joomla `card` layout listing active providers

Core checks from `plg_healthchecker_core` show no badge (they're the default).

### Report Page (`tmpl/report/default.php`)

Uses standard Joomla admin layout patterns:

```php
// Standard Joomla admin view structure
<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;

/** @var \MySitesGuru\HealthChecker\Component\Administrator\View\Report\HtmlView $this */

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('bootstrap.collapse');
?>

<form action="..." method="post" name="adminForm" id="adminForm">
    <!-- Joomla searchtools for filters -->
    <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

    <!-- Content area -->
    <div class="row">
        <!-- Summary cards -->
        <!-- Category accordions -->
    </div>
</form>
```

**Layout Structure**:
- **Toolbar**: "Run Health Check" (btn-success), "Export" dropdown (JSON/HTML)
- **Summary Row**: 3 Bootstrap cards showing Critical/Warning/Good counts
- **Filter Bar**: Joomla searchtools with status + category dropdowns
- **Results**: Bootstrap accordion with category sections
  - Category header: Icon + name + badge counts (uses `accordion-button`)
  - Check rows: Standard table inside `accordion-body`
  - Status column: FontAwesome icon + Bootstrap badge
  - Title column: Check name
  - Description column: Result message
  - Provider column: Small badge (third-party only)
- **Timestamp**: `Text::sprintf('COM_HEALTHCHECKER_LAST_CHECKED', $date)`

### Dashboard Widget (`mod_healthchecker`)

Uses Joomla cpanel module pattern (like quickicon modules):

```php
// Similar to mod_quickicon structure
<div class="mod-healthchecker">
    <div class="row g-2">
        <div class="col">
            <div class="card text-bg-danger">
                <div class="card-body text-center">
                    <span class="fs-1"><?php echo $criticalCount; ?></span>
                    <div><?php echo Text::_('COM_HEALTHCHECKER_CRITICAL'); ?></div>
                </div>
            </div>
        </div>
        <!-- Warning and Good cards -->
    </div>
    <div class="mt-2">
        <a href="..." class="btn btn-outline-primary btn-sm">
            <?php echo Text::_('COM_HEALTHCHECKER_VIEW_REPORT'); ?>
        </a>
    </div>
</div>
```

### Color Scheme (Bootstrap 5 Contextual Classes)

| Status | Background | Text | Icon |
|--------|------------|------|------|
| Critical | `bg-danger` | `text-white` | `fa-times-circle` |
| Warning | `bg-warning` | `text-dark` | `fa-exclamation-triangle` |
| Good | `bg-success` | `text-white` | `fa-check-circle` |

### Accessibility
- All interactive elements keyboard accessible (native Bootstrap)
- ARIA labels on icons: `aria-label="<?php echo Text::_('COM_HEALTHCHECKER_STATUS_CRITICAL'); ?>"`
- Color not sole indicator (icons + text always present)
- Respects Joomla's RTL support automatically

---

## Phase 1: Skeleton (Workstreams 1-3)

Start with installable component with FULL UI:

1. **Component** with complete report view, filters, export buttons
2. **Check infrastructure** (interfaces, events, services)
3. **Plugin** with one example check per category (8 checks) to validate all categories render

This validates the full architecture and UI before implementing remaining 118 checks.

---

## Phase 2: Check Implementation (Workstreams 4-11)

Implement all 129 checks in parallel across 8 category workstreams. Each workstream is independent after the interfaces exist.

---

## Phase 3: UI & Polish (Workstream 12-13)

1. Dashboard module
2. Export functionality (JSON/HTML)
3. Filtering UI (by status and category)
4. Package for distribution

---

## Verification Plan

### Phase 0: Docker Environment
1. Run `docker compose up -d`
2. Wait for healthchecks to pass (check with `docker compose ps`)
3. Access https://healthchecker.local - verify Joomla loads (OrbStack auto-DNS)
4. Complete Joomla installation wizard with:
   - Database host: `db.healthchecker.local`
   - Database: `joomla`, User: `joomla`, Password: `joomla`

### Phase 1-3: Component Testing
1. **Install package** in the Docker Joomla instance
2. **Enable plugin** `plg_healthchecker_core`
3. **Navigate** to Components > Health Checker
4. **Click "Run Health Check"** - verify all 129 checks execute
5. **Test filters** - filter by status, filter by category, combined
6. **Test exports** - download JSON, download HTML
7. **Dashboard widget** - add to admin dashboard, verify summary shows
8. **Third-party test** - install example plugin, verify custom check + category appears with provider badge

---

## Critical Reference Files

From Joomla 6 codebase:
- `joomla/administrator/components/com_admin/src/Model/SysinfoModel.php` - System info patterns
- `joomla/plugins/quickicon/joomlaupdate/src/Extension/Joomlaupdate.php` - Event subscriber pattern
- `joomla/administrator/components/com_scheduler/services/provider.php` - Service provider pattern
- `joomla/libraries/src/Event/Result/ResultAware.php` - Event result collection

---

## Implementation Order

```
Phase 0: Environment Setup (FIRST)
└── [Sequential] Workstream 0: Docker dev environment (must complete first)

Phase 1: Foundation (Parallel)
├── [Parallel] Workstream 1: Component skeleton + full UI
├── [Parallel] Workstream 2: Check infrastructure + provider system
├── [Parallel] Workstream 13: Developer SDK + documentation
└── [Sequential] Workstream 3: Core plugin (after W2)

Phase 2: Checks (all parallel after W2 complete)
├── [Parallel] Workstream 4: System (27)
├── [Parallel] Workstream 5: Database (13)
├── [Parallel] Workstream 6: Security (20)
├── [Parallel] Workstream 7: Users (14)
├── [Parallel] Workstream 8: Extensions (16)
├── [Parallel] Workstream 9: Performance (14)
├── [Parallel] Workstream 10: SEO (12)
└── [Parallel] Workstream 11: Content (10)

Phase 3: Polish
├── [Parallel] Workstream 12: Dashboard module
├── [Parallel] Workstream 15: Example plugin (developer reference)
└── [Sequential] Workstream 14: Package & testing
```

---

## Developer Documentation Outline (DEVELOPERS.md)

```markdown
# Health Checker for Joomla - Developer Guide

## Overview
How to extend Health Checker with your own checks.

## Quick Start
1. Create a plugin in `plugins/healthchecker/yourplugin/`
2. Implement `SubscriberInterface`
3. Subscribe to `onHealthCheckerCollectChecks` event
4. Add checks extending `AbstractHealthCheck`

## Provider Registration
Register your plugin's metadata so users know which checks come from you.

### Example: Registering Your Provider
```php
public function onCollectProviders(CollectProvidersEvent $event): void
{
    $event->addResult(new ProviderMetadata(
        slug: 'myplugin',
        name: 'My E-Commerce Plugin',
        description: 'Health checks for online stores',
        url: 'https://example.com/myplugin',
        icon: 'fa-shopping-cart',
        logoUrl: 'plugins/healthchecker/myplugin/assets/logo.svg',
        version: '1.0.0'
    ));
}
```

## Adding Custom Categories
You can register entirely new categories for your checks.

### Example: Adding a Category
```php
public function onCollectCategories(CollectCategoriesEvent $event): void
{
    $event->addResult(new HealthCategory(
        slug: 'ecommerce',
        label: 'PLG_HEALTHCHECKER_MYPLUGIN_CATEGORY_ECOMMERCE',
        icon: 'fa-shopping-cart',
        sortOrder: 90
    ));
}
```

## Creating Health Checks

### The Check Interface
All checks must implement `HealthCheckInterface` or extend `AbstractHealthCheck`.

### Example Check
```php
namespace MySitesGuru\HealthChecker\Plugin\MyPlugin\Checks;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

final class PaymentGatewayCheck extends AbstractHealthCheck
{
    public function getSlug(): string
    {
        return 'ecommerce.payment_gateway';
    }

    public function getCategory(): string
    {
        return 'ecommerce';  // Your custom category
    }

    public function getProvider(): string
    {
        return 'myplugin';  // Links to your provider metadata
    }

    protected function performCheck(): HealthCheckResult
    {
        // Your check logic here
        if ($this->isPaymentConfigured()) {
            return $this->good('Payment gateway is configured correctly.');
        }

        return $this->critical('No payment gateway configured.');
    }
}
```

## Check Result Statuses
- `critical()` - Red, something is broken/dangerous
- `warning()` - Yellow, should be addressed
- `good()` - Green, everything is fine

## Auto-Discovery
Checks are auto-discovered from your plugin's `src/Checks/` directory.
No manual registration needed - just create the file.

## Best Practices
1. Use translation keys for all user-facing strings
2. Keep checks focused on one thing
3. Handle exceptions gracefully (return `warning()` for unknown states)
4. Include actionable descriptions

## Complete Example Plugin
See `docs/examples/plg_healthchecker_example/` for a complete working plugin.
```

---

## Workstream 15: Example Plugin (plg_healthchecker_example)
**Files touched:** `healthchecker/plugins/example/*`, `build/build.sh`
**Depends on:** Workstream 2 & 13 (check infrastructure + provider system)

A complete, working example plugin that demonstrates the extensibility system. Lives in healthchecker/plugins/example/ for development and is extractable by the build script.

### Purpose
- Reference implementation for third-party developers
- Validates the extensibility architecture works
- Provides copy-paste starting point for new plugins
- Documents all extension points with working code

### File Structure
```
healthchecker/plugins/example/
├── example.xml                              # Plugin manifest
├── services/
│   └── provider.php                         # DI service provider
├── src/
│   ├── Extension/
│   │   └── ExamplePlugin.php                # Main subscriber class
│   └── Checks/
│       ├── CustomCategory/
│       │   ├── ExampleCheck.php             # Basic example check
│       │   └── AnotherExampleCheck.php      # Second example
│       └── System/
│           └── CustomSystemCheck.php        # Adding to existing category
├── assets/
│   └── logo.svg                             # Provider logo (64x64)
└── language/
    └── en-GB/
        └── plg_healthchecker_example.ini    # Translations
```

### Implementation Tasks

- [ ] Create plugin manifest (`example.xml`)
  - Namespace: `MySitesGuru\HealthChecker\Plugin\Example`
  - Version: 1.0.0
  - Author: Health Checker for Joomla
  - License: GPL-2.0-or-later

- [ ] Create service provider (`services/provider.php`)
  - Register ExamplePlugin with DI container
  - Inject DatabaseInterface

- [ ] Create `ExamplePlugin.php` event subscriber
  - Implement `SubscriberInterface`
  - Subscribe to all three events:
    - `onHealthCheckerCollectProviders`
    - `onHealthCheckerCollectCategories`
    - `onHealthCheckerCollectChecks`
  - Include detailed comments explaining each method

- [ ] Create `ProviderMetadata` registration
  ```php
  new ProviderMetadata(
      slug: 'example',
      name: 'Example Health Checks',
      description: 'Demonstrates how to extend Health Checker with custom checks',
      url: 'https://github.com/user/health-checker-for-joomla',
      icon: 'fa-graduation-cap',
      logoUrl: 'plugins/healthchecker/example/assets/logo.svg',
      version: '1.0.0'
  )
  ```

- [ ] Create custom category `customcategory`
  ```php
  new HealthCategory(
      slug: 'customcategory',
      label: 'PLG_HEALTHCHECKER_EXAMPLE_CATEGORY_CUSTOM',
      icon: 'fa-flask',
      sortOrder: 100
  )
  ```

- [ ] Create `ExampleCheck.php` - basic example
  - Demonstrates minimal check implementation
  - Returns `good()` with explanation
  - Heavily commented for learning

- [ ] Create `AnotherExampleCheck.php` - advanced example
  - Demonstrates database access
  - Shows conditional logic (good/warning/critical)
  - Demonstrates metadata usage

- [ ] Create `CustomSystemCheck.php` - adding to core category
  - Shows how to add checks to existing categories
  - Category: `system` (core category)
  - Provider: `example` (third-party attribution)

- [ ] Create `logo.svg`
  - Simple icon (graduation cap or flask)
  - 64x64 viewBox
  - Single color for theme compatibility

- [ ] Create language file with all translation keys
  ```ini
  PLG_HEALTHCHECKER_EXAMPLE="Example Health Checks"
  PLG_HEALTHCHECKER_EXAMPLE_DESC="Demonstrates how to extend Health Checker"
  PLG_HEALTHCHECKER_EXAMPLE_CATEGORY_CUSTOM="Custom Category"
  PLG_HEALTHCHECKER_EXAMPLE_CHECK_EXAMPLE_TITLE="Example Check"
  PLG_HEALTHCHECKER_EXAMPLE_CHECK_ANOTHER_TITLE="Another Example"
  PLG_HEALTHCHECKER_EXAMPLE_CHECK_CUSTOMSYSTEM_TITLE="Custom System Check"
  ```

- [ ] Update build script to extract example plugin
  - Add to `build/build.php`
  - Creates `plg_healthchecker_example.zip`
  - Separate from main package (optional install)

### Example Plugin Code

#### services/provider.php
```php
<?php
defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use MySitesGuru\HealthChecker\Plugin\Example\Extension\ExamplePlugin;

return new class implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $plugin = new ExamplePlugin(
                    $container->get(\Joomla\Event\DispatcherInterface::class),
                    (array) PluginHelper::getPlugin('healthchecker', 'example')
                );
                $plugin->setApplication(Factory::getApplication());
                $plugin->setDatabase($container->get(DatabaseInterface::class));

                return $plugin;
            }
        );
    }
};
```

#### Extension/ExamplePlugin.php
```php
<?php
namespace MySitesGuru\HealthChecker\Plugin\Example\Extension;

use Joomla\CMS\Plugin\CMSPlugin;
use MySitesGuru\HealthChecker\Component\Administrator\Category\HealthCategory;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectCategoriesEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectChecksEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectProvidersEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Provider\ProviderMetadata;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;
use MySitesGuru\HealthChecker\Plugin\Example\Checks\CustomCategory\ExampleCheck;
use MySitesGuru\HealthChecker\Plugin\Example\Checks\CustomCategory\AnotherExampleCheck;
use MySitesGuru\HealthChecker\Plugin\Example\Checks\System\CustomSystemCheck;

/**
 * Example plugin demonstrating Health Checker extensibility.
 *
 * This plugin shows how third-party developers can:
 * 1. Register their plugin as a provider (with branding)
 * 2. Add custom categories for their checks
 * 3. Add checks to existing core categories
 * 4. Create fully custom health checks
 */
final class ExamplePlugin extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;

    /**
     * Returns the events this subscriber listens to.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onHealthCheckerCollectProviders'  => 'onCollectProviders',
            'onHealthCheckerCollectCategories' => 'onCollectCategories',
            'onHealthCheckerCollectChecks'     => 'onCollectChecks',
        ];
    }

    /**
     * Register this plugin as a health check provider.
     *
     * Provider metadata is displayed in the report UI to show users
     * which plugin contributed which checks.
     */
    public function onCollectProviders(CollectProvidersEvent $event): void
    {
        $event->addResult(new ProviderMetadata(
            slug: 'example',
            name: 'Example Health Checks',
            description: 'Demonstrates how to extend Health Checker with custom checks',
            url: 'https://github.com/user/health-checker-for-joomla',
            icon: 'fa-graduation-cap',
            logoUrl: 'plugins/healthchecker/example/assets/logo.svg',
            version: '1.0.0'
        ));
    }

    /**
     * Register custom categories for your checks.
     *
     * You can create entirely new categories, or add checks
     * to existing core categories (system, database, etc.)
     */
    public function onCollectCategories(CollectCategoriesEvent $event): void
    {
        $event->addResult(new HealthCategory(
            slug: 'customcategory',
            label: 'PLG_HEALTHCHECKER_EXAMPLE_CATEGORY_CUSTOM',
            icon: 'fa-flask',
            sortOrder: 100  // Higher numbers appear later
        ));
    }

    /**
     * Register your health checks.
     *
     * Checks can be added to your custom categories or to
     * existing core categories like 'system' or 'security'.
     */
    public function onCollectChecks(CollectChecksEvent $event): void
    {
        $db = $this->getDatabase();

        // Checks in our custom category
        $event->addResult(new ExampleCheck());
        $event->addResult(new AnotherExampleCheck($db));

        // Check added to the core 'system' category
        $event->addResult(new CustomSystemCheck());
    }
}
```

#### Checks/CustomCategory/ExampleCheck.php
```php
<?php
namespace MySitesGuru\HealthChecker\Plugin\Example\Checks\CustomCategory;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

/**
 * A minimal example health check.
 *
 * This demonstrates the simplest possible check implementation.
 * Copy this as a starting point for your own checks.
 */
final class ExampleCheck extends AbstractHealthCheck
{
    /**
     * Unique identifier for this check.
     *
     * Convention: category.check_name (lowercase, underscores)
     */
    public function getSlug(): string
    {
        return 'customcategory.example';
    }

    /**
     * Which category this check belongs to.
     *
     * Use your custom category slug or a core category
     * like 'system', 'database', 'security', etc.
     */
    public function getCategory(): string
    {
        return 'customcategory';
    }

    /**
     * Provider slug - links to your ProviderMetadata.
     */
    public function getProvider(): string
    {
        return 'example';
    }

    /**
     * Perform the actual health check.
     *
     * Return one of:
     * - $this->good('Success message')
     * - $this->warning('Warning message')
     * - $this->critical('Critical message')
     */
    protected function performCheck(): HealthCheckResult
    {
        // Your check logic goes here
        // This example always returns good

        return $this->good(
            'This is an example check. It always passes to demonstrate the basic structure.'
        );
    }
}
```

### Verification

1. Install `plg_healthchecker_example.zip` in Joomla
2. Enable the plugin in Extensions > Plugins
3. Navigate to Components > Health Checker
4. Click "Run Health Check"
5. Verify:
   - "Custom Category" section appears with flask icon
   - 3 example checks are visible
   - Provider badge "Example Health Checks" appears on checks
   - Tooltip shows provider description and link
   - Footer credits section includes the example provider

---

## Notes

- **No SSL checks** per PLAN.md - infrastructure concern
- **No database tables** - session-based results only
- **Super Admin only** - ACL enforced
- **GPL v2+ license** - same as Joomla
