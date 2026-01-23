# CI/CD Setup & Workflows

Complete GitHub Actions CI/CD pipeline for Health Checker for Joomla.

## Quick Start

### Run All Quality Checks Locally

```bash
# Run everything (recommended before pushing)
composer check

# Or run individually
composer test          # PHPUnit tests
composer phpstan       # Static analysis
composer cs:fix        # Fix code style
composer rector        # Code quality check
```

### Fix Issues Automatically

```bash
composer cs:fix        # Auto-fix code style (ECS + Rector)
```

## Workflow Overview

Four parallel jobs run on every push/PR to `main` and `develop`:

| Job | Purpose | PHP Version(s) | Fails On |
|-----|---------|----------------|----------|
| **PHPUnit** | Test suite | 8.1, 8.2, 8.3, 8.4, 8.5 (matrix) | Test failures |
| **PHPStan** | Static analysis | 8.2 | Type errors, bugs |
| **ECS** | Code style | 8.2 | Style violations |
| **Rector** | Code quality | 8.2 | Quality issues |

**Expected Runtime**: ~1-2 minutes (all jobs run in parallel)

## Workflow Details

### PHPUnit Tests (Matrix Strategy)

- **Runs on**: PHP 8.1, 8.2, 8.3, 8.4, 8.5
- **Purpose**: Ensure code works across all supported PHP versions
- **Coverage**: Generates code coverage report (Xdebug)
- **Uploads**: Coverage to Codecov (PHP 8.2 only)
- **Current Status**: 202 tests, 512 assertions - All passing ✅

**What it does:**
```bash
composer validate --strict
composer install --prefer-dist --no-progress
vendor/bin/phpunit --coverage-clover coverage.xml
```

**Runtime**: ~10-20 seconds per PHP version

### PHPStan Static Analysis

- **Runs on**: PHP 8.2
- **Level**: 8 (strictest)
- **Purpose**: Catch type errors, bugs, and code smells
- **Fails if**: Any PHPStan errors found

**What it does:**
```bash
composer install --prefer-dist --no-progress
vendor/bin/phpstan analyse --error-format=github --no-progress
```

**Runtime**: ~15-30 seconds

### Easy Coding Standard (ECS)

- **Runs on**: PHP 8.2
- **Purpose**: Enforce PSR-12 coding standards
- **Fails if**: Code style violations found

**What it does:**
```bash
composer install --prefer-dist --no-progress
composer ecs  # vendor/bin/ecs check --ansi
```

**Runtime**: ~5-10 seconds

### Rector Code Quality

- **Runs on**: PHP 8.2
- **Purpose**: Check for outdated code patterns and refactoring opportunities
- **Fails if**: Code quality issues found

**What it does:**
```bash
composer install --prefer-dist --no-progress
composer rector  # vendor/bin/rector process --dry-run --ansi
```

**Runtime**: ~10-15 seconds

## Workflow Triggers

### Push Events
- `main` branch
- `develop` branch

### Pull Request Events
- Pull requests targeting `main`
- Pull requests targeting `develop`

## Status Checks

For a pull request to be merged, all jobs must pass:

- ✅ PHPUnit Tests (PHP 8.1)
- ✅ PHPUnit Tests (PHP 8.2)
- ✅ PHPUnit Tests (PHP 8.3)
- ✅ PHPUnit Tests (PHP 8.4)
- ✅ PHPUnit Tests (PHP 8.5)
- ✅ PHPStan Static Analysis
- ✅ Easy Coding Standard
- ✅ Rector Code Quality

## Test Coverage

### Current Coverage

- **Core Classes**: 100% coverage
  - HealthStatus
  - HealthCheckResult
  - AbstractHealthCheck
  - HealthCategory
  - ProviderMetadata
  - ProviderRegistry
  - CategoryRegistry
  - CollectProvidersEvent

- **Overall**: 202 tests, 512 assertions

### Codecov Integration

Coverage reports are uploaded to Codecov for the PHP 8.2 job.

**Setup:**
1. Sign up at https://codecov.io with GitHub account
2. Enable the repository
3. Add `CODECOV_TOKEN` to repository secrets (Settings → Secrets → Actions)

## Caching Strategy

All jobs cache Composer dependencies for faster execution:

```yaml
- uses: actions/cache@v3
  with:
    path: vendor
    key: ${{ runner.os }}-php-${{ matrix.php-version }}-composer-${{ hashFiles('**/composer.lock') }}
```

**Benefits:**
- Faster workflow execution
- Reduced Composer API calls
- Consistent dependency versions

## Local Development

### Before Committing

```bash
# 1. Run all quality checks
composer check

# 2. Fix any issues
composer cs:fix

# 3. Run tests
composer test

# 4. Commit and push
git add .
git commit -m "Your message"
git push
```

### Pull Request Process

1. **Create feature branch**
   ```bash
   git checkout -b feature/your-feature
   ```

2. **Make changes and test locally**
   ```bash
   composer check
   ```

3. **Push and create PR**
   ```bash
   git push origin feature/your-feature
   ```

4. **CI runs automatically** - All checks must pass

5. **Request review** - If branch protection enabled

6. **Merge** - After approval and passing checks

## Troubleshooting

### CI Failing, Locally Passing

**Cause**: Different PHP versions or dependencies

**Fix:**
```bash
# Clear vendor and reinstall
rm -rf vendor composer.lock
composer install

# Run checks again
composer check
```

### PHPUnit Failures

```bash
# Run tests locally
composer test

# Run with verbose output
vendor/bin/phpunit --testdox

# Run specific test
vendor/bin/phpunit tests/Unit/Component/Check/HealthStatusTest.php

# Generate coverage report
composer test:coverage
# Opens in browser: coverage/index.html
```

### PHPStan Errors

**Common issues:**
- Missing type hints
- Undefined methods/properties
- Incorrect return types

**Fix:**
```bash
# Run locally to see errors
composer phpstan

# Check specific file
vendor/bin/phpstan analyse healthchecker/component/src/Check/HealthStatus.php

# Generate baseline (for temporary ignores)
vendor/bin/phpstan analyse --generate-baseline
```

### ECS Violations

**Auto-fix most issues:**
```bash
composer cs:fix
```

**Manual review:**
```bash
composer ecs
```

**Common violations:**
- Missing or incorrect header comments
- Incorrect array syntax (use `[]` not `array()`)
- Unused imports
- Incorrect indentation

### Rector Issues

**Apply suggested changes:**
```bash
composer cs:fix  # Includes rector:fix
```

**Review without applying:**
```bash
composer rector
```

**Common issues:**
- Outdated PHP patterns
- Dead code
- Unnecessary type declarations
- Simplifiable conditions

### Cache Issues

If dependencies seem stale, clear the cache:
1. Go to repository **Settings** → **Actions** → **Caches**
2. Delete outdated caches
3. Re-run the workflow

## Tool Versions

| Tool | Version | PHP Requirement | Notes |
|------|---------|----------------|-------|
| PHPUnit | 10.5.60 | PHP 8.1+ | Latest for PHP 8.1 support |
| PHPStan | 1.12+ | PHP 8.1+ | Level 8 (strictest) |
| ECS | 12.3+ | PHP 8.1+ | PSR-12 + Symplify |
| Rector | 1.2+ | PHP 8.1+ | Code quality |

### Why PHPUnit 10?

- **PHPUnit 11** requires PHP 8.2+ (incompatible with 8.1)
- **PHPUnit 12** requires PHP 8.3+ (drops 8.1 and 8.2)
- **PHPUnit 10** supports PHP 8.1+ (maintains broad compatibility)

**Decision**: Keep PHPUnit 10.5.60 for maximum compatibility with Joomla 5 (which supports PHP 8.1+)

## Configuration Files

All configurations are set up and maintained:

| File | Purpose | Status |
|------|---------|--------|
| `.github/workflows/ci.yml` | GitHub Actions workflow | ✅ Active |
| `phpunit.xml` | PHPUnit configuration | ✅ Configured |
| `phpstan.neon` | PHPStan rules (level 8) | ✅ Configured |
| `ecs.php` | Easy Coding Standard | ✅ Configured |
| `rector.php` | Rector rules | ✅ Configured |
| `phpstan-bootstrap.php` | PHPStan Joomla mocks | ✅ Configured |
| `tests/bootstrap.php` | PHPUnit Joomla mocks | ✅ Configured |

## Composer Scripts

All quality checks available as composer scripts:

```json
{
  "scripts": {
    "test": "phpunit",
    "test:coverage": "phpunit --coverage-html coverage/",
    "phpstan": "phpstan analyse --ansi --memory-limit=512M",
    "ecs": "ecs check --ansi",
    "ecs:fix": "ecs check --fix --ansi",
    "rector": "rector process --dry-run --ansi",
    "rector:fix": "rector process --ansi",
    "cs": ["@ecs", "@rector", "@ecs"],
    "cs:fix": ["@ecs:fix", "@rector:fix", "@ecs:fix"],
    "check": ["@cs", "@phpstan", "@test"]
  }
}
```

**Recommended**: Use `composer check` before committing to run all quality checks.

## Branch Protection Rules

Recommended branch protection for `main`:

**Settings** → **Branches** → **Branch protection rules**

- ✅ Require status checks to pass before merging
- ✅ Require branches to be up to date before merging
- ✅ Required status checks:
  - PHPUnit Tests (8.1)
  - PHPUnit Tests (8.2)
  - PHPUnit Tests (8.3)
  - PHPUnit Tests (8.4)
  - PHPUnit Tests (8.5)
  - PHPStan Static Analysis
  - Easy Coding Standard
  - Rector Code Quality
- ✅ Require pull request reviews (recommended: 1+)
- ✅ Dismiss stale reviews when new commits are pushed

## Status Badges

Add these badges to README.md:

```markdown
[![CI](https://github.com/mySites-guru/HealthCheckerForJoomla/workflows/CI/badge.svg)](https://github.com/mySites-guru/HealthCheckerForJoomla/actions)
[![codecov](https://codecov.io/gh/PhilETaylor/health-checker-for-joomla/branch/main/graph/badge.svg)](https://codecov.io/gh/PhilETaylor/health-checker-for-joomla)
[![PHPStan Level 8](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg)](https://phpstan.org/)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
```

## Security

### Dependabot (Optional)

Enable Dependabot for automatic dependency updates:

```yaml
# .github/dependabot.yml
version: 2
updates:
  - package-ecosystem: "composer"
    directory: "/"
    schedule:
      interval: "weekly"
```

### Secret Scanning

GitHub automatically scans for exposed secrets. Never commit:
- API keys
- Passwords
- Private tokens
- Database credentials

## Contributing

When contributing, ensure all checks pass locally before pushing:

```bash
# Run all checks
composer check

# Fix issues
composer cs:fix

# Ensure tests pass
composer test

# Then commit and push
git add .
git commit -m "Your descriptive message"
git push
```

See [CONTRIBUTING.md](../../CONTRIBUTING.md) for complete contribution guidelines.

## Next Steps

1. ✅ **CI/CD Active** - Workflow running on all pushes/PRs
2. ✅ **202 Tests Passing** - Comprehensive test coverage
3. ✅ **PHPStan Level 8** - Strictest static analysis
4. ✅ **Code Style Enforced** - PSR-12 compliance
5. ⬜ **Enable Codecov** (optional) - For coverage tracking
6. ⬜ **Add status badges** to README.md
7. ⬜ **Set up branch protection** - Enforce CI checks on PRs
8. ⬜ **Configure Dependabot** (optional) - Auto dependency updates

## Support & Documentation

- **Test Suite**: See `tests/README.md`
- **CI/CD Workflow**: This file
- **Contributing**: See `CONTRIBUTING.md`
- **Failed Checks**: Check the Actions tab on GitHub
- **Local Setup**: Run `composer install` and verify PHP 8.1+

## Workflow Status

**View Workflow Runs**: https://github.com/mySites-guru/HealthCheckerForJoomla/actions

**Current Status**: ✅ Active and passing
**Test Coverage**: 202 tests, 512 assertions
**PHP Versions**: 8.1, 8.2, 8.3, 8.4, 8.5
**Quality Tools**: PHPStan (L8), ECS (PSR-12), Rector

## License

Same as Health Checker for Joomla - GPL v2 or later.

---

**CI/CD Setup Completed**: January 2026
**Maintained By**: Health Checker for Joomla Team
**Last Updated**: January 2026
