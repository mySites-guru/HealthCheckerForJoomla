# Build Process

This document explains how to build installable packages for Health Checker for Joomla.

## Overview

The project uses a **source + build** pattern:
- **Source code**: `/healthchecker/` directory
- **Build script**: `/build/build.sh`
- **Build output**: `/build/dist/` directory
- **Development testing**: `/joomla/` (symlinked to source)

## Build Script

### Location
`/build/build.sh`

### Usage

```bash
# Build with default version (1.0.0)
./build/build.sh

# Build with specific version
./build/build.sh 1.0.2

# Build from project root
cd /Users/phil/Sites/health-checker-for-joomla
./build/build.sh 1.0.2
```

### What It Does

1. **Validates source directory** exists (`/healthchecker/`)
2. **Cleans build directory** (`/build/dist/`)
3. **Creates temporary staging area** (`/build/dist/tmp/`)
4. **Copies source files** from `/healthchecker/` to staging
5. **Updates version numbers** in all XML manifests
6. **Creates individual ZIP packages**:
   - Component: `com_healthchecker-{VERSION}.zip`
   - Module: `mod_healthchecker-{VERSION}.zip`
   - Core Plugin: `plg_healthchecker_core-{VERSION}.zip`
   - Example Plugin: `plg_healthchecker_example-{VERSION}.zip`
   - MySites.guru Plugin: `plg_healthchecker_mysitesguru-{VERSION}.zip`
   - Akeeba Backup Plugin: `plg_healthchecker_akeebabackup-{VERSION}.zip`
   - Akeeba Admin Tools Plugin: `plg_healthchecker_akeebaadmintools-{VERSION}.zip`
7. **Creates unified package** with install script: `pkg_healthchecker-{VERSION}.zip`
8. **Cleans up temporary files**
9. **Displays summary** with package sizes

### Output Structure

```
build/dist/
├── pkg_healthchecker-{VERSION}.zip              ← Install this
├── com_healthchecker-{VERSION}.zip
├── mod_healthchecker-{VERSION}.zip
├── plg_healthchecker_core-{VERSION}.zip         ← Largest (129 checks)
├── plg_healthchecker_example-{VERSION}.zip
├── plg_healthchecker_mysitesguru-{VERSION}.zip
├── plg_healthchecker_akeebabackup-{VERSION}.zip
└── plg_healthchecker_akeebaadmintools-{VERSION}.zip
```

## Package Install Script

The unified package (`pkg_healthchecker-{VERSION}.zip`) includes an install script (`script.php`) that:

### Pre-Flight Checks

- **Joomla Version**: Requires 5.0+
- **PHP Version**: Requires 8.1+

Aborts installation if requirements not met.

### Post-Install Actions

**Always Enabled**:
- ✅ Core Plugin (`plg_healthchecker_core`) - Required for all checks
- ✅ Example Plugin (`plg_healthchecker_example`) - Developer reference
- ✅ MySites.guru Plugin (`plg_healthchecker_mysitesguru`) - No dependencies

**Conditionally Enabled**:
- ⚠️ Akeeba Backup Plugin - Only if `com_akeebabackup` installed & enabled
- ⚠️ Akeeba Admin Tools Plugin - Only if `com_admintools` installed & enabled

### Success Message

Shows green success message: "Health Checker installed successfully! Access it from Components > Health Checker. The dashboard module is also available."

## Development Symlinks

For development, use symlinks instead of copying files:

### Setup Script

```bash
./build/setupSymlinks.sh
```

### What It Does

1. **Checks for conflicts** - Verifies no existing Health Checker installation in `/joomla/`
2. **Creates symlinks**:
   - `/joomla/administrator/components/com_healthchecker` → `/healthchecker/component`
   - `/joomla/administrator/modules/mod_healthchecker` → `/healthchecker/module`
   - `/joomla/plugins/healthchecker/core` → `/healthchecker/plugins/core`
   - `/joomla/plugins/healthchecker/example` → `/healthchecker/plugins/example`
   - `/joomla/plugins/healthchecker/mysitesguru` → `/healthchecker/plugins/mysitesguru`
   - `/joomla/plugins/healthchecker/akeebabackup` → `/healthchecker/plugins/akeebabackup`
   - `/joomla/plugins/healthchecker/akeebaadmintools` → `/healthchecker/plugins/akeebaadmintools`
   - Media folders similarly symlinked
3. **Validates all symlinks** created successfully

### Benefits

- **Instant testing** - Edit files in `/healthchecker/`, changes reflect in `/joomla/` immediately
- **No rebuilding** - Skip build step during development
- **Easy cleanup** - Remove symlinks, not duplicated files

### Conflict Resolution

If conflicts found (existing Health Checker files):

```bash
# Backup existing installation
cd /Users/phil/Sites/health-checker-for-joomla/joomla
tar -czf ~/healthchecker-backup-$(date +%Y%m%d-%H%M%S).tar.gz \
  administrator/components/com_healthchecker \
  administrator/modules/mod_healthchecker \
  plugins/healthchecker \
  media/com_healthchecker \
  media/plg_healthchecker_* 2>/dev/null

# Remove existing directories
rm -rf administrator/components/com_healthchecker
rm -rf administrator/modules/mod_healthchecker
rm -rf plugins/healthchecker
rm -rf media/com_healthchecker
rm -rf media/plg_healthchecker_*

# Re-run symlink setup
cd /Users/phil/Sites/health-checker-for-joomla
./build/setupSymlinks.sh
```

## Version Management

### Updating Version Numbers

The build script automatically updates version numbers in:

- `healthchecker/component/healthchecker.xml`
- `healthchecker/module/mod_healthchecker.xml`
- `healthchecker/plugins/core/core.xml`
- `healthchecker/plugins/example/example.xml`
- `healthchecker/plugins/mysitesguru/mysitesguru.xml`
- `healthchecker/plugins/akeebabackup/akeebabackup.xml`
- `healthchecker/plugins/akeebaadmintools/akeebaadmintools.xml`
- `pkg_healthchecker.xml` (generated)

### Version Format

Use semantic versioning: `MAJOR.MINOR.PATCH`

- **MAJOR**: Breaking changes, incompatible API changes
- **MINOR**: New features, backward-compatible
- **PATCH**: Bug fixes, backward-compatible

Examples:
- `1.0.0` - Initial release
- `1.0.1` - Bug fix
- `1.1.0` - New feature (e.g., new category of checks)
- `2.0.0` - Breaking change (e.g., API redesign)

## Build Workflow

### Standard Build Process

```bash
# 1. Make changes in /healthchecker directory
vim healthchecker/plugins/core/src/Checks/Security/NewCheck.php

# 2. Test via symlinks in /joomla
open http://yourjoomla.local/administrator

# 3. Run code quality checks
composer run cs:fix

# 4. Build packages with new version
./build/build.sh 1.0.2

# 5. Verify build output
ls -lh build/dist/

# 6. Test installation in clean Joomla
# (Upload pkg_healthchecker-1.0.2.zip)

# 7. Commit and tag
git add .
git commit -m "Release v1.0.2"
git tag v1.0.2
git push origin main --tags
```

### Quick Development Iteration

```bash
# Edit source
vim healthchecker/plugins/core/src/Checks/System/DiskSpaceCheck.php

# Refresh browser (changes reflect via symlinks)
# No build needed!
```

### Pre-Release Checklist

Before building a release:

- [ ] All checks execute without errors
- [ ] Code style passes: `composer run cs:check`
- [ ] Language strings complete (no missing keys)
- [ ] Version number decided (semantic versioning)
- [ ] CHANGELOG.md updated (if exists)
- [ ] Documentation updated
- [ ] Test in clean Joomla installation
- [ ] Test upgrade from previous version

## Package Contents

### Component Package
- Admin component files
- Language files (en-GB)
- Services (DI container)
- Events, models, views, controllers

### Module Package
- Module files (admin)
- Dispatcher, helper classes
- Language files
- Layouts

### Core Plugin Package (Largest)
- 129 check files
- Category definitions
- Auto-discovery logic
- Language file with 130+ keys

### Example Plugin Package
- Reference implementation
- 2 example checks
- Custom category demo
- Provider metadata demo

### MySites.guru Plugin Package
- API connection check
- Custom category with logo
- Provider branding demo

### Akeeba Plugins
- Conditional dependency checks
- Integration with Akeeba extensions
- Backup/security monitoring

### Unified Package
- All 7 individual packages
- Package manifest XML
- Install script (script.php)
- Smart plugin enabling logic

## Documentation Build

### VitePress User Documentation

The user-facing documentation is built using VitePress and located in `docs/USER/`.

**Build commands**:
```bash
cd docs/USER
npm run docs:build              # Standard build
npm run docs:build:production   # Production build with search index
npm run docs:dev                # Local dev server
```

**Output location**: `website/public/docs/`

### LLM-Friendly Documentation

The documentation build automatically generates LLM-friendly formats using
[vitepress-plugin-llms](https://github.com/okineadev/vitepress-plugin-llms):

**Generated files** (in `website/public/docs/`):
- `llms.txt` - Index file with links to all documentation sections
- `llms-full.txt` - Complete documentation compiled into one file
- Individual `.md` files for each page in LLM-optimized format

These files follow the [llmstxt.org](https://llmstxt.org) standard and make documentation
easily consumable by AI assistants like Claude, ChatGPT, etc.

**Custom tags** (optional):
- `<llm-only>` - Content visible only to LLMs, hidden from web docs
- `<llm-exclude>` - Content hidden from LLMs, visible on web

The documentation also includes "Copy as Markdown" and "Download as Markdown" buttons
on each page for users who want to reference docs in their own tools.

## Build Script Internals

### Temporary Directory Structure

```
build/dist/tmp/
├── com_healthchecker/
│   ├── admin/              (copied from healthchecker/component/)
│   └── healthchecker.xml   (version updated)
├── mod_healthchecker/      (copied from healthchecker/module/)
├── plg_healthchecker_core/ (copied from healthchecker/plugins/core/)
├── plg_healthchecker_example/
├── plg_healthchecker_mysitesguru/
├── plg_healthchecker_akeebabackup/
├── plg_healthchecker_akeebaadmintools/
└── pkg_healthchecker/
    ├── packages/           (all 7 ZIPs)
    ├── pkg_healthchecker.xml
    └── script.php
```

All cleaned up after build completes.

### Version Replacement

Uses `sed` to replace version strings:

```bash
sed -i.bak "s/<version>.*<\/version>/<version>${VERSION}<\/version>/" file.xml
```

Creates `.bak` file temporarily, then removes it.

### ZIP Exclusions

Excludes from all packages:
- `.DS_Store` (macOS metadata)
- `__MACOSX` (macOS ZIP metadata)

## Troubleshooting

### "Source directory not found"

```bash
ERROR: Source directory not found: /healthchecker
```

**Solution**: Ensure `/healthchecker` directory exists with extension files.

### Build fails with permission errors

```bash
# Make build script executable
chmod +x build/build.sh
chmod +x build/setupSymlinks.sh
```

### Symlinks fail to create

Check if existing files block symlink creation:

```bash
# Run setupSymlinks.sh - it will show conflicts
./build/setupSymlinks.sh

# Follow on-screen instructions to backup/remove conflicts
```

### Package sizes seem wrong

After significant changes, check file counts:

```bash
# Count PHP files in core plugin
find healthchecker/plugins/core/src/Checks -name "*.php" | wc -l
# Should be 130

# Check package contents
unzip -l build/dist/plg_healthchecker_core-{VERSION}.zip | grep "\.php"
```

### Version not updating in manifest

Build script updates XML files, but source files remain unchanged.
Version only updates in **build output**, not in **source**.

To permanently update source:

```bash
# Manually edit manifest
vim healthchecker/component/healthchecker.xml
# Change: <version>1.0.2</version>

# Then build
./build/build.sh 1.0.2
```

## Release Process

### GitHub Release

```bash
# 1. Build packages
./build/build.sh 1.0.2

# 2. Create release on GitHub
# - Tag: v1.0.2
# - Title: Release 1.0.2
# - Description: Changelog summary

# 3. Upload packages as assets
# - pkg_healthchecker-1.0.2.zip (main package)
# - Optionally: individual component/plugin ZIPs
```

## Advanced: Custom Build

To add new plugin to build process:

```bash
# Edit build.sh
vim build/build.sh

# Find plugin build loop (around line 69)
for plugin in core example akeebabackup akeebaadmintools mysitesguru; do

# Add your plugin name
for plugin in core example akeebabackup akeebaadmintools mysitesguru mynewplugin; do

# Ensure source exists
mkdir -p healthchecker/plugins/mynewplugin

# Build
./build/build.sh 1.0.0
```

---

**Next Steps**: See [DEVELOPMENT.md](DEVELOPMENT.md) for development environment setup.
