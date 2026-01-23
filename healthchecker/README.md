# Health Checker for Joomla - Source Code

This directory contains the source code for the Health Checker for Joomla extension, organized by extension type.

## Directory Structure

```
/healthchecker/
├── component/          # com_healthchecker (main component)
├── module/             # mod_healthchecker (dashboard widget)
└── plugins/
    ├── core/          # Core health checks (150+ checks)
    ├── example/       # Example plugin for SDK reference
    ├── akeebabackup/  # Akeeba Backup integration
    ├── akeebaadmintools/  # Akeeba Admin Tools integration
    └── mysitesguru/   # mySites.guru API integration
```

## Development Workflow

### 1. Docker Volume Mounts

The project uses Docker with volume mounts configured in `docker-compose.yml`.

Volume mounts automatically map `/healthchecker/*` → container's `/app/joomla/*` locations.

**No symlink setup needed** - Docker handles the mounting.

### 2. Edit Files

Edit files in this `/healthchecker` directory. Changes will automatically reflect in the Docker container via volume mounts.

### 3. Test in Joomla

Access your Joomla installation at https://healthchecker.local and test:
- Component: Components > Health Checker
- Module: System > Manage > Administrator Modules > Health Checker
- Plugins: System > Manage > Plugins (search "healthchecker")

### 4. Build Packages

When ready to create installable ZIP packages:

```bash
cd /Users/phil/Sites/health-checker-for-joomla/build
./build.sh 1.0.1  # Replace with your version number
```

This creates:
- Individual packages in `build/dist/`
- Unified package: `pkg_healthchecker-1.0.1.zip` (installs everything)

## Extension Overview

### Component (com_healthchecker)
- **Purpose:** Main component providing health check infrastructure
- **Features:**
  - Event-driven architecture for plugin registration
  - Health check runner with caching support
  - Provider and category registries
  - Report views (HTML & JSON)
  - AJAX endpoints for dashboard module

### Module (mod_healthchecker)
- **Purpose:** Dashboard widget showing health status summary
- **Features:**
  - Real-time AJAX loading
  - Cached results (configurable duration)
  - Click-through to full report
  - Manual refresh button

### Plugins

#### Core Plugin (plg_healthchecker_core)
- **Purpose:** Provides 150+ built-in health checks
- **Categories:**
  - Content (10 checks)
  - Database (17 checks)
  - Extensions (13 checks)
  - Performance (11 checks)
  - Security (24 checks)
  - SEO (11 checks)
  - System (26 checks)
  - Users (12 checks)

#### Example Plugin (plg_healthchecker_example)
- **Purpose:** SDK reference for creating custom health checks
- **Includes:** 2 example checks with documentation

#### Akeeba Backup Plugin (plg_healthchecker_akeebabackup)
- **Purpose:** Integration with Akeeba Backup
- **Dependencies:** Requires com_akeebabackup installed
- **Auto-enabled:** Only if Akeeba Backup is present

#### Akeeba Admin Tools Plugin (plg_healthchecker_akeebaadmintools)
- **Purpose:** Integration with Akeeba Admin Tools
- **Dependencies:** Requires com_admintools installed
- **Auto-enabled:** Only if Admin Tools is present

#### mySites.guru Plugin (plg_healthchecker_mysitesguru)
- **Purpose:** API integration with mySites.guru monitoring service
- **Dependencies:** None
- **Auto-enabled:** Always

## Creating Custom Health Checks

See the Example plugin for reference. Basic structure:

```php
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;

class MyCustomCheck extends AbstractHealthCheck
{
    public function run(): HealthCheckResult
    {
        // Your check logic here
        $isHealthy = true;

        return new HealthCheckResult(
            healthStatus: $isHealthy ? HealthStatus::Good : HealthStatus::Critical,
            title: 'My Custom Check',
            description: 'Description of the result',
            slug: 'my-custom-check',
            category: 'system',
            provider: 'my-plugin'
        );
    }
}
```

## Git Workflow

- **Source files:** Edit in `/healthchecker` directory
- **Docker mounts:** Volume mounts configured in `docker-compose.yml`
- **joomla:** Contains full Joomla installation (health checker files mounted via Docker)
- **Commits:** Git tracks the files in `/healthchecker`

## Build Notes

- Source directory: `/healthchecker`
- Build script reads from `/healthchecker`
- Docker volume mounts used for local Joomla testing
- CI/CD can build directly from `/healthchecker` (no Docker needed)

## Troubleshooting

### Changes not reflecting in Joomla?
1. Verify Docker container is running: `docker ps | grep healthchecker`
2. Restart containers: `docker compose restart`
3. Check volume mounts in `docker-compose.yml`
4. Clear Joomla cache if needed

### Docker mounts not working?
1. Stop containers: `docker compose down`
2. Start containers: `docker compose up -d`
3. Check logs: `docker compose logs -f frankenphp`

### Build failing?
1. Ensure `/healthchecker` directory has all files
2. Check permissions on build scripts: `chmod +x build/*.sh`
3. Verify no syntax errors in XML manifests
