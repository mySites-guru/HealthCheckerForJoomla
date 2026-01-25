# Health Checker for Joomla - Development Documentation

This directory contains technical documentation for **developing Health Checker itself** (not for third-party developers extending it).

## 📁 Documentation Structure

### For Third-Party Developers
If you want to **add health checks to your own extension**, see the USER documentation:
- `docs/USER/developers/` - Complete developer guide for extending Health Checker
- **This includes**: Plugin creation, custom checks, API reference, examples

### For Health Checker Core Development
This DEV folder contains:
- Architecture decisions and implementation details
- Local development environment setup
- Build scripts and release processes
- Internal code conventions
- Claude AI assistant context

## 📚 Contents

- **[CLAUDE.md](CLAUDE.md)** - **START HERE** - Complete context for AI assistants working on this codebase
- **[DEVELOPMENT.md](DEVELOPMENT.md)** - Local development setup with symlinks
- **[BUILD.md](BUILD.md)** - Build process for creating distribution packages
- **[IMPLEMENTATION.md](IMPLEMENTATION.md)** - Technical architecture and implementation plan
- **[CHECKS.md](CHECKS.md)** - Canonical list of all 129 health checks
- **[AKEEBA.md](AKEEBA.md)** - Akeeba integration plugin details

## 🎯 Quick Links

**Setting up local dev environment?**
→ See [DEVELOPMENT.md](DEVELOPMENT.md)

**Need to understand the current implementation?**
→ See [CLAUDE.md](CLAUDE.md) for complete overview

**Building a release package?**
→ See [BUILD.md](BUILD.md)

**Adding new checks to the core plugin?**
→ See [CHECKS.md](CHECKS.md) for the canonical list

**AI assistant working on this code?**
→ See [CLAUDE.md](CLAUDE.md) - most comprehensive context

## 🔧 Development Workflow

### Quick Start

```bash
# 1. Clone repository
git clone https://github.com/mySites-guru/HealthCheckerForJoomla.git
cd health-checker-for-joomla

# 2. Install Composer dependencies
composer install

# 3. Setup symlinks (joomla/ → healthchecker/)
./build/setupSymlinks.sh

# 4. Edit files in healthchecker/ directory
vim healthchecker/plugins/core/src/Checks/Security/NewCheck.php

# 5. Test immediately (changes reflect via symlinks)
open http://yourjoomla.local/administrator

# 6. Build packages when ready
./build/build.sh 1.0.2
```

## 🏗️ Architecture Overview

```
Health Checker for Joomla
├── Component (com_healthchecker)           - Infrastructure, events, AJAX API
├── Module (mod_healthchecker)              - Dashboard widget
├── Core Plugin (plg_healthchecker_core)    - 129 check files
└── Optional Integrations
    ├── Example Plugin                      - SDK reference
    ├── MySites.guru Plugin                 - API integration
    ├── Akeeba Backup Plugin                - Backup monitoring
    └── Akeeba Admin Tools Plugin           - Security monitoring
```

**Key Principles**:
- Event-driven auto-discovery of checks
- Source + build pattern (`/healthchecker/` → `/build/dist/`)
- Symlinks for development (`/joomla/` → `/healthchecker/`)
- No database tables (session-based results only)
- Super Admin access only
- GPL v2+ license

## 📂 Actual Directory Structure

```
/Users/phil/Sites/health-checker-for-joomla/
├── healthchecker/                  # SOURCE (development)
│   ├── component/                  # com_healthchecker
│   ├── module/                     # mod_healthchecker
│   └── plugins/
│       ├── core/                   # 129 check files
│       ├── example/                # SDK reference
│       ├── mysitesguru/            # Integration
│       ├── akeebabackup/           # Backup monitoring
│       └── akeebaadmintools/       # Security monitoring
│
├── joomla/                         # Joomla install (symlinked)
│   └── administrator/
│       ├── components/com_healthchecker → /healthchecker/component
│       ├── modules/mod_healthchecker → /healthchecker/module
│       └── plugins/healthchecker/* → /healthchecker/plugins/*
│
├── build/
│   ├── build.sh                    # Build all packages
│   ├── setupSymlinks.sh            # Create symlinks
│   └── dist/                       # Built packages (not in git)
│
└── docs/
    ├── USER/                       # End-user documentation (VitePress)
    └── DEV/                        # You are here
```

## 📝 Current Implementation Status

| Component | Status | Details |
|-----------|--------|---------|
| Core Infrastructure | ✅ Complete | Component, events, runner, caching, AJAX API |
| Core Checks | ✅ Complete | 129 checks across 8+ categories |
| Dashboard Module | ✅ Complete | AJAX-driven admin widget |
| Example Plugin | ✅ Complete | SDK reference for developers |
| MySites.guru Plugin | ✅ Complete | API integration with branding |
| Akeeba Integrations | ✅ Complete | Backup + Admin Tools monitoring |
| Build System | ✅ Complete | Automated build with smart installer |
| Tests | ✅ Complete | 309 tests, 811 assertions |
| Docker Environment | ❌ None | No docker-compose.yml |

## 🚀 Common Tasks

### Add New Core Check

```bash
# 1. Create check file (auto-discovered)
vim healthchecker/plugins/core/src/Checks/Security/HttpsCheck.php

# 2. Add language key
vim healthchecker/plugins/core/language/en-GB/plg_healthchecker_core.ini
# Add: COM_HEALTHCHECKER_CHECK_CORE_HTTPS_CHECK_TITLE="HTTPS Enabled"

# 3. Test (no registration needed - auto-discovery)
open http://yourjoomla.local/administrator
# Navigate to: Components → Health Checker → Run Health Check

# 4. Build when ready
./build/build.sh 1.0.2
```

### Code Quality

```bash
# Fix code style
composer run cs:fix

# Modernize PHP code
composer run rector

# Check for issues
composer run cs:check
```

## 📖 Documentation Guide

### When to Update Which Docs

| Change Type | Update Documentation |
|-------------|---------------------|
| Add new check | `CHECKS.md`, language file |
| Change architecture | `CLAUDE.md`, `IMPLEMENTATION.md` |
| Change build process | `BUILD.md` |
| Change dev setup | `DEVELOPMENT.md` |
| Add third-party feature | `USER/developers/` |
| Add user feature | `USER/` docs |

### Documentation Hierarchy

1. **CLAUDE.md** - Single source of truth for AI assistants
2. **DEVELOPMENT.md** - How to set up and develop
3. **BUILD.md** - How to build packages
4. **IMPLEMENTATION.md** - Architecture details
5. **CHECKS.md** - List of all checks
6. **AKEEBA.md** - Integration details

## ⚠️ Important Notes

### What's NOT Implemented

- ❌ Frontend component (admin-only currently)
- ❌ Email notifications
- ❌ Scheduled checks (manual execution only)
- ❌ Historical tracking
- ❌ Docker development environment

### Directory Structure

**Actual structure**:
- `/healthchecker/` - Source code (edit here)
- `/joomla/` - Joomla installation (symlinked to healthchecker/)
- `/build/` - Build scripts and output

## 🔗 External Resources

- **Repository**: https://github.com/mySites-guru/HealthCheckerForJoomla
- **Joomla Docs**: https://docs.joomla.org/
- **PHP Docs**: https://www.php.net/docs.php

## 📞 Getting Help

- **GitHub Issues**: [Report bugs or request features](https://github.com/mySites-guru/HealthCheckerForJoomla/issues)
- **Documentation**: Browse this DEV folder
- **User Docs**: See `docs/USER/` for end-user documentation

## 📄 License

GPL v2 or later - same as Joomla itself.

All code must include proper GPL headers.
