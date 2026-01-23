# Code Quality

Health Checker for Joomla maintains high code quality standards through automated tooling and comprehensive documentation.

## Tools

All code quality tools are run via Composer scripts and configured in the project root:

| Tool | Purpose | Config File | Level/Standard |
|------|---------|-------------|----------------|
| **PHPStan** | Static analysis | `phpstan.neon` | Level 7 |
| **Rector** | Code modernization | `rector.php` | PHP 8.1+ rules |
| **ECS** | Code style | `ecs.php` | PSR-12 + Joomla |
| **PHPUnit** | Unit testing | `phpunit.xml` | (Coming soon) |

## Running Tools

### Quick Check (All Tools)

```bash
composer check
```

### Individual Tools

```bash
# Static analysis (type safety, bugs)
vendor/bin/phpstan analyze

# Code modernization
vendor/bin/rector process

# Code style fixes
vendor/bin/ecs check --fix
```

## PHPStan - Static Analysis

**Current Level:** 7 out of 9

PHPStan catches bugs before runtime by analyzing code for type safety, null pointer errors, and logical issues.

### What We Check

- **Core Component** (`healthchecker/component/src/`)
- **Core Plugin** (`healthchecker/plugins/core/src/`)
- **Example Plugin** (`healthchecker/plugins/example/src/`)
- **MySitesGuru Plugin** (`healthchecker/plugins/mysitesguru/src/`)

### What We Exclude

- **Service Providers** (`healthchecker/plugins/*/services/`) - Use DI container patterns
- **Akeeba Plugins** (`healthchecker/plugins/akeeba*/`) - Optional third-party integrations

### Configuration Highlights

**Level 7 Strictness:**
- Full type checking
- Null safety validation
- Dead code detection
- Invalid type operations

**Joomla-Specific Exceptions:**
- Database table prefixes (`$db->getPrefix()` returns `string`, not `array`)
- Mixed array types (Joomla uses untyped arrays extensively)
- Magic methods and properties

### Results

```bash
$ vendor/bin/phpstan analyze
 [OK] No errors
   158/158 files analyzed
```

## Rector - Code Modernization

Rector automatically upgrades code to modern PHP standards.

### Applied Rules

- **PHP 8.1+ Features** - Constructor property promotion, readonly properties, etc.
- **Dead Code Removal** - Unused variables, unreachable code
- **Type Declarations** - Add missing types where possible
- **Code Quality** - SimplifyIfElse, RemoveUselessParamTag, etc.

### Example Transformations

**Before Rector:**
```php
public function __construct(DatabaseInterface $db)
{
    $this->db = $db;
}

/**
 * @return void
 */
public function doSomething(): void
{
    // code
}
```

**After Rector:**
```php
public function __construct(
    private DatabaseInterface $db
) {
}

public function doSomething(): void
{
    // code
}
```

### Configuration

```php
// rector.php
$rectorConfig->paths([
    __DIR__ . '/healthchecker/component',
    __DIR__ . '/healthchecker/plugins',
]);

$rectorConfig->sets([
    LevelSetList::UP_TO_PHP_81,
]);
```

## ECS - Easy Coding Standard

Enforces PSR-12 and Joomla coding standards.

### Standards Applied

- **PSR-12** - PHP-FIG coding style
- **Joomla Coding Standards** - File headers, naming conventions
- **Additional Rules:**
  - No superfluous PHPDoc tags (duplicating type hints)
  - Trim consecutive blank lines in PHPDoc
  - Array formatting (trailing commas, alignment)

### Example Fixes

**Before ECS:**
```php
<?php
namespace Foo;

class Bar {
    /**
     * @param string $name
     * @return void
     */
    public function setName(string $name): void {
        $this->name=$name;
    }
}
```

**After ECS:**
```php
<?php

/**
 * @package     Joomla.Component
 * @subpackage  HealthChecker.Administrator
 *
 * @copyright   (C) 2026 Health Checker for Joomla
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Foo;

class Bar
{
    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
```

## PHPUnit - Unit Testing

**Status:** Coming soon

Test coverage will include:
- Health check classes
- Service classes (HealthCheckRunner, registries)
- Event classes
- Utility methods

## Documentation Standards

All code includes comprehensive inline documentation:

### Class-Level Documentation

Every class has:
- Package and subpackage tags
- Copyright and license
- Detailed description

```php
/**
 * PHP Version Health Check
 *
 * Verifies PHP version meets Joomla 5's minimum requirements...
 *
 * WHY THIS CHECK IS IMPORTANT:
 * ...
 *
 * RESULT MEANINGS:
 * GOOD: PHP 8.2.0+
 * WARNING: PHP 8.1.0-8.1.x
 * CRITICAL: Below PHP 8.1
 *
 * @package     Joomla.Plugin
 * @subpackage  HealthChecker.Core
 *
 * @copyright   (C) 2026 Health Checker for Joomla
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * @since       1.0.0
 */
```

### Method-Level Documentation

All methods have:
- `@param` tags with types and descriptions
- `@return` tags with types and descriptions
- `@throws` tags where applicable
- `@since` version tags
- Detailed descriptions

```php
/**
 * Converts PHP ini size notation to bytes.
 *
 * Supports K (kilobytes), M (megabytes), and G (gigabytes) suffixes.
 *
 * Examples:
 * - "8M" → 8388608
 * - "256K" → 262144
 * - "2G" → 2147483648
 *
 * @param string $value The PHP ini value (e.g., "8M", "256K")
 *
 * @return int The value in bytes
 *
 * @since 1.0.0
 */
private function convertToBytes(string $value): int
```

### Inline Comments

Complex logic includes inline comments explaining:
- **Database queries** - What tables, why, what conditions mean
- **Business logic** - Threshold values, edge cases
- **Security considerations** - Why checks are important
- **Performance notes** - Caching, query optimization

## CI/CD Integration (Future)

When GitHub Actions is configured, these tools will run automatically:

```yaml
# .github/workflows/code-quality.yml
- name: PHPStan
  run: vendor/bin/phpstan analyze

- name: ECS
  run: vendor/bin/ecs check

- name: Rector (dry run)
  run: vendor/bin/rector process --dry-run
```

## Quality Metrics

**Current Status:**

| Metric | Value | Target |
|--------|-------|--------|
| PHPStan Level | 7/9 | 7 ✅ |
| ECS Violations | 0 | 0 ✅ |
| Files Analyzed | 158 | All ✅ |
| Documented Classes | 100% | 100% ✅ |
| Documented Methods | 100% | 100% ✅ |
| Test Coverage | 0% | 80% (planned) |

## For Contributors

When contributing code:

1. **Run PHPStan** - No errors allowed at level 7
2. **Run Rector** - Accept modernization suggestions
3. **Run ECS** - Auto-fix with `--fix` flag
4. **Add PHPDoc** - Document all classes and methods
5. **Write Tests** - Cover new health checks (when PHPUnit is configured)

### Pre-Commit Checklist

```bash
# 1. Fix code style
vendor/bin/ecs check --fix

# 2. Modernize code
vendor/bin/rector process

# 3. Verify no static analysis errors
vendor/bin/phpstan analyze

# 4. Run tests (when available)
# vendor/bin/phpunit
```

## Conclusion

Health Checker maintains enterprise-grade code quality through:
- ✅ **Level 7 static analysis** (PHPStan)
- ✅ **Modern PHP 8.1+ code** (Rector)
- ✅ **PSR-12 compliance** (ECS)
- ✅ **100% inline documentation**
- 🔜 **Comprehensive test coverage** (PHPUnit)

This ensures the codebase is maintainable, secure, and follows industry best practices.
