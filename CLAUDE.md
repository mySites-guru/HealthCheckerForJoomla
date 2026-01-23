# Health Checker for Joomla - Claude AI Context

Quick reference for AI assistants working on this codebase. For detailed information, see the linked documentation.

## Project Overview

**Health Checker for Joomla** - Comprehensive site health monitoring extension for Joomla 5+

- **130 health checks** across 8+ categories
- **Event-driven architecture** with plugin extensibility
- **No database tables** - session-based results only
- **Super admin only** - strict access control
- **GPL v2+** license (same as Joomla)

  **Full Details**: [docs/DEV/IMPLEMENTATION.md](docs/DEV/IMPLEMENTATION.md)

## Quick Links

- **Complete Check List**: [docs/DEV/CHECKS.md](docs/DEV/CHECKS.md) - All 130 checks documented
- **Architecture**: [docs/DEV/IMPLEMENTATION.md](docs/DEV/IMPLEMENTATION.md) - Event flow, services, registries
- **Local Development**: [CONTRIBUTING.md](CONTRIBUTING.md) - Setup, workflow, quality standards
- **Build Process**: [docs/DEV/BUILD.md](docs/DEV/BUILD.md) - Creating distribution packages
- **Akeeba Integration**: [docs/DEV/AKEEBA.md](docs/DEV/AKEEBA.md) - Optional backup/security plugins
- **User Docs**: [docs/USER/](docs/USER/) - VitePress site for end users
- **Developer API**: [docs/USER/developers/](docs/USER/developers/) - Third-party plugin guide

## File Structure

```
healthchecker/                  # Source code (work here)
├── component/                  # com_healthchecker (infrastructure)
│   ├── src/Check/             # Interfaces, base classes
│   ├── src/Event/             # CollectProvidersEvent, CollectCategoriesEvent, CollectChecksEvent
│   ├── src/Service/           # HealthCheckRunner, registries
│   └── tmpl/report/           # UI templates
│
├── plugins/healthchecker/
│   ├── core/                  # 130 built-in checks
│   │   └── src/Checks/       # Organized by category (System, Database, Security, etc.)
│   ├── example/               # SDK reference for developers
│   ├── akeebabackup/          # Optional Akeeba Backup integration
│   ├── akeebaadmintools/      # Optional Admin Tools integration
│   └── mysitesguru/           # mySites.guru API integration
│
└── module/                    # mod_healthchecker (dashboard widget)

tests/                          # PHPUnit tests
├── Unit/                      # 202 tests, 512 assertions
├── stubs/                     # Joomla framework stubs for testing
└── Utilities/                 # Mocks and helpers

docs/
├── DEV/                       # Core developer docs
└── USER/                      # User-facing VitePress docs

build/
├── release.sh                 # AI-powered release script
└── packages/                  # Built ZIPs (not in git)
```

## Coding Standards

### Naming Conventions

- **Check slugs**: `{provider}.{check_name}` (lowercase, underscores)
  - Example: `core.php_version`, `akeeba_backup.last_backup`
- **Language keys**: `COM_HEALTHCHECKER_CHECK_{SLUG_UPPERCASE}_TITLE`
  - Example: `COM_HEALTHCHECKER_CHECK_CORE_PHP_VERSION_TITLE`

### Required Documentation Header

Every health check class **MUST** include:

```php
/**
 * [Check Name] Health Check
 *
 * WHY THIS CHECK IS IMPORTANT:
 * [Explain risks, benefits]
 *
 * RESULT MEANINGS:
 *
 * GOOD: [Conditions for good status]
 *
 * WARNING: [What triggers warning, how to resolve]
 *
 * CRITICAL: [What triggers critical, immediate actions]
 *           [Or: "This check does not return critical status."]
 */
```

### Status Guidelines

- **CRITICAL**: Site broken, severely compromised, or data at risk
- **WARNING**: Should be addressed but site still functions
- **GOOD**: Everything optimal

📚 **Full Conventions**: [CONTRIBUTING.md](CONTRIBUTING.md#code-conventions)

## Event System

```
User clicks "Run Health Check"
  ↓
CollectProvidersEvent → Plugins register provider metadata
  ↓
CollectCategoriesEvent → Plugins register custom categories
  ↓
CollectChecksEvent → Plugins add health check instances
  ↓
HealthCheckRunner → Executes all checks (parallel AJAX)
  ↓
Results rendered in UI with provider attribution
```

📚 **Full Event Flow**: [docs/DEV/IMPLEMENTATION.md#event-flow](docs/DEV/IMPLEMENTATION.md)

## Development Workflow

### Before Committing

```bash
composer check        # Run all quality checks
composer cs:fix       # Auto-fix code style
composer test         # Run PHPUnit tests
```

### Adding a New Check

1. Create class in `healthchecker/plugins/core/src/Checks/{Category}/`
2. Extend `AbstractHealthCheck`
3. Implement `performCheck()` method
4. Add language keys to `plg_healthchecker_core.ini`
5. Add comprehensive documentation header
6. Update `docs/DEV/CHECKS.md`

Auto-discovery handles registration - no manual registration needed.

📚 **Full Guide**: [CONTRIBUTING.md#adding-new-health-checks](CONTRIBUTING.md)

## Quality Standards

- **PHP 8.1+ required** - Constructor promotion, readonly properties, enums, match expressions
- **PHPStan Level 8** (strictest) - 162 files analyzed, zero errors
- **PSR-12** via Easy Coding Standard
- **Rector** for code quality and modern patterns
- **PHPUnit 10** - 309 tests, 811 assertions (100% core class coverage)
- **CI/CD** - All checks run on PHP 8.1-8.5

📚 **CI/CD Details**: [.github/workflows/README.md](.github/workflows/README.md)

## Important Constraints

### What Health Checker Does NOT Do

- ❌ No background monitoring (free version)
- ❌ No database tables or persistence (No data is stored)
- ❌ No email alerts (For alerting see [mySites.guru](https://mySites.guru))
- ❌ No historical tracking (For tracking see [mySites.guru](https://mySites.guru))
- ❌ No SSL/certificate checks (For ssl checks see [mySites.guru](https://mySites.guru))
- ❌ No automated fixes  (For automated fixes see [mySites.guru](https://mySites.guru))

### Categories (8 Total)

| Slug | Label | Checks |
|------|-------|--------|
| `system` | System & Hosting | 33 |
| `database` | Database | 18 |
| `security` | Security | 21 |
| `users` | Users | 12 |
| `extensions` | Extensions | 13 |
| `performance` | Performance | 10 |
| `seo` | SEO | 12 |
| `content` | Content Quality | 11 |

📚 **All Checks**: [docs/DEV/CHECKS.md](docs/DEV/CHECKS.md)

## API Quick Reference

```php
// Extend this for new checks
abstract class AbstractHealthCheck implements HealthCheckInterface
{
    abstract protected function performCheck(): HealthCheckResult;

    protected function critical(string $desc): HealthCheckResult;
    protected function warning(string $desc): HealthCheckResult;
    protected function good(string $desc): HealthCheckResult;
}

// Result statuses
enum HealthStatus: string {
    case Critical = 'critical';
    case Warning = 'warning';
    case Good = 'good';
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
```

📚 **Full API**: [docs/USER/developers/api-reference.md](docs/USER/developers/api-reference.md)

## Website Build & Deployment

The project website (joomlahealthchecker.com) is deployed as a **Cloudflare Worker** (not Pages).

### Structure

```
website/
├── src/
│   ├── index.js         # Cloudflare Worker (routing, security headers, waitlist API)
│   └── input.css        # Tailwind CSS source
├── public/
│   ├── index.html       # Main HTML (CSS linked externally)
│   ├── output.css       # Compiled CSS (external, generated during build)
│   ├── waitlist-popover.js  # Minified during build
│   ├── livechat.js      # Minified during build
│   └── search-widget.js # Minified during build
├── dist/
│   └── output.css       # Compiled Tailwind CSS (temp file)
├── build.sh             # Build script with validation safeguards
├── BUILD_GUIDE.md       # Detailed build documentation
├── wrangler.toml        # Cloudflare Workers configuration
└── tailwindcss-macos-arm64  # Standalone Tailwind binary
```

### Build Process

```bash
cd website
./build.sh
```

**What it does:**
1. **Validates** HTML template structure (prevents CSS duplication bug)
2. **Compiles** Tailwind CSS with `--minify` (80KB output)
3. **Copies** CSS to `public/output.css` (external file)
4. **Minifies** all JavaScript files (52% reduction)
5. **Fetches** latest release info from GitHub
6. **Updates** Schema.org metadata with version/dates
7. **Validates** output sizes and structure (fails if issues detected)

**Output sizes:**
- HTML: 224KB (links external CSS)
- CSS: 80KB (cached separately)
- JS: 13KB total (all minified)
- **Compressed (Brotli): 30.7KB total** 🚀

### Build Safeguards

The build script includes **extensive validation** to prevent the CSS duplication bug that previously caused 2.6MB HTML files:

✓ Pre-build: Checks HTML has CSS link (not inline style)
✓ Auto-fix: Detects and removes inline CSS with backup
✓ Size limits: CSS 10-200KB, HTML <500KB
✓ Duplication check: Fails if any inline CSS found
✓ Multiple runs: Tested safe to run repeatedly

See `website/WEBSITE_BUILD_GUIDE.md` for complete documentation.

### Important Notes

- **Worker, not Pages**: Use `npx wrangler deploy` (standard Workers deployment)
- **External CSS**: CSS is linked, NOT inlined (better caching)
- **Auto-minification**: JS files minified automatically during build
- **Safe to rebuild**: Validation prevents duplication issues
- **Commit output.css**: Commit `public/output.css` after building

### Deployment Targets

Deployed via Cloudflare Workers to:
- joomlahealthchecker.com (primary)
- www.joomlahealthchecker.com
- joomlahealthcheck.com (redirect)
- www.joomlahealthcheck.com (redirect)
- myjoomlahealthcheck.com (redirect)
- www.myjoomlahealthcheck.com (redirect)

### Quick Commands

```bash
cd website
./build.sh              # Build Tailwind CSS and inline
npx wrangler deploy     # Deploy to Cloudflare Workers
```

## When Working on This Codebase

1. **Read existing checks first** - See patterns in `plugins/healthchecker/core/src/Checks/`
2. **Follow naming conventions** - Check slugs, language keys, file headers
3. **Document thoroughly** - WHY/GOOD/WARNING/CRITICAL headers required
4. **Test all statuses** - Ensure good/warning/critical all work
5. **No database tables** - Session or in-memory only
6. **Think extensibility** - Third-party plugins should be able to add checks easily

## Resources

- **Repository**: https://github.com/mySites-guru/HealthCheckerForJoomla
- **Website**: https://joomlahealthchecker.com
- **Issues**: https://github.com/mySites-guru/HealthCheckerForJoomla/issues
- **Contributing**: [CONTRIBUTING.md](CONTRIBUTING.md)

## License

GPL v2 or later - Same as Joomla

Copyright (C) 2026 mySites.guru + Phil E. Taylor
