# Code Quality Commitment

Health Checker for Joomla maintains the highest standards of code quality through automated testing and continuous integration. This page outlines our commitment to delivering reliable, maintainable, and professional software.

## Static Analysis - PHPStan Level 8

We use [PHPStan](https://phpstan.org/) at **Level 8** (the strictest level) to ensure maximum type safety and code correctness.

### What This Means

PHPStan Level 8 represents the most rigorous static analysis available for PHP:

- **Type Safety**: Every variable, parameter, and return type must be correctly declared and used
- **Null Safety**: Strict checking for potential null pointer exceptions
- **Method Existence**: Verification that all method calls exist on the declared types
- **Property Access**: Validation that all property accesses are type-safe
- **Dead Code Detection**: Identification of unreachable or unused code

### Coverage

Our PHPStan analysis covers:
- ✅ All component source code (`component/src/`)
- ✅ All health check plugins (`plugins/*/src/`)
- ✅ Service provider files (`*/services/`)
- ✅ Module code (`module/src/`)

## Code Style - Easy Coding Standard

We enforce consistent code style using [Easy Coding Standard (ECS)](https://github.com/easy-coding-standard/easy-coding-standard) with PSR-12 compliance.

### Standards Applied

- **PSR-12**: The official PHP coding standard
- **Symfony**: Industry-leading code style conventions
- **Line Length**: Automatic wrapping and formatting for readability
- **Import Organization**: Consistent namespace and use statement ordering
- **Spacing & Indentation**: Uniform formatting across all files

## Code Quality - Rector

[Rector](https://github.com/rectorphp/rector) automatically maintains modern PHP practices and code quality.

### Features

- **Type Declarations**: Automatic strict type hints where possible
- **Code Modernization**: Upgrades to latest PHP 8.1+ features
- **Dead Code Removal**: Eliminates unused code automatically
- **Refactoring**: Consistent patterns and naming conventions
- **Best Practices**: Enforces industry-standard code patterns

## Continuous Integration

Every commit is automatically tested across multiple PHP versions:

### Test Matrix

| PHP Version | Status |
|-------------|--------|
| PHP 8.1     | ✅ Tested |
| PHP 8.2     | ✅ Tested |
| PHP 8.3     | ✅ Tested |
| PHP 8.4     | ✅ Tested |
| PHP 8.5     | ✅ Tested |

### Automated Checks

All pull requests and commits are validated through:

1. **PHPUnit Tests**: Comprehensive unit and integration tests
2. **PHPStan Level 8**: Full static analysis
3. **ECS**: Code style verification
4. **Rector**: Code quality validation
5. **Multi-PHP Testing**: Compatibility across PHP 8.1-8.5

**Every check must pass before code is merged**.

## Type Safety Guarantees

Our use of PHPStan Level 8 provides strong guarantees:

### Database Access

```php
// ❌ Unsafe (returns ?DatabaseInterface)
$db = $this->getDatabase();
$db->quoteName('table'); // Could fail if $db is null

// ✅ Safe (returns DatabaseInterface, never null)
$database = $this->requireDatabase();
$database->quoteName('table'); // Always safe
```

### Health Check Results

All health check results are type-safe enums:

```php
enum HealthStatus: string {
    case Critical = 'critical';
    case Warning = 'warning';
    case Good = 'good';
}
```

No magic strings, no typos, complete IDE autocomplete support.

## Why This Matters

### For Users

- **Reliability**: Fewer bugs, more stable releases
- **Performance**: Optimized code through static analysis
- **Security**: Early detection of potential vulnerabilities
- **Updates**: Confident upgrades with comprehensive test coverage

### For Developers

- **Maintainability**: Consistent, readable codebase
- **Documentation**: Type declarations serve as inline docs
- **IDE Support**: Full autocomplete and type hints
- **Onboarding**: New developers can understand code faster

## Development Workflow

### Before Every Commit

Developers run:
```bash
composer test        # Run all PHPUnit tests
composer phpstan     # PHPStan Level 8 analysis
composer ecs         # Code style check
composer rector      # Code quality check
```

### Automated Enforcement

GitHub Actions runs the same checks on every push, ensuring no regression.

## Comparison with Industry Standards

| Tool | Our Level | Industry Typical |
|------|-----------|------------------|
| PHPStan | **Level 8** | Level 4-5 |
| ECS | **Strict PSR-12** | Basic PSR-12 |
| Rector | **Full suite** | Minimal/None |
| PHP Testing | **8.1-8.5** | Single version |

We exceed typical open-source project standards.

## Open Source Transparency

All our quality tooling is configured in the repository:

- `phpstan.neon` - PHPStan configuration
- `ecs.php` - Code style rules
- `rector.php` - Quality rules
- `.github/workflows/ci.yml` - CI pipeline

**Everyone can verify our quality standards**.

## Continuous Improvement

We regularly:

- ✅ Update dependency versions
- ✅ Add new test coverage
- ✅ Adopt new PHP features
- ✅ Improve type safety
- ✅ Enhance documentation

## Your Confidence

When you install Health Checker for Joomla, you're installing software that:

- Has **zero static analysis errors**
- Follows **strict coding standards**
- Is **tested across 5 PHP versions**
- Uses **modern best practices**
- Is **actively maintained**

We take code quality seriously because **your site's health matters**.
