# Contributing to Health Checker for Joomla

Thank you for your interest in contributing to Health Checker for Joomla! We welcome contributions from the Joomla community and any other users and developers. 

## Ways to Contribute

### Reporting Bugs

If you find a bug, please create an issue on GitHub with:
- A clear, descriptive title
- Steps to reproduce the issue
- Expected behavior vs actual behavior
- Your environment (Joomla version, PHP version, server setup)
- Screenshots if applicable

### Suggesting Features

We're always looking for ways to improve Health Checker. To suggest a feature:
- Check if the feature has already been requested
- Create a new issue with the "enhancement" label
- Describe the feature and its use case
- Explain why it would be valuable

### Contributing Code

#### Development Setup

1. **Fork and Clone**
   ```bash
   git clone https://github.com/YOUR-USERNAME/health-checker-for-joomla.git
   cd health-checker-for-joomla
   ```

2. **Install Dependencies**
   ```bash
   composer install
   ```

3. **Development Environment**
   - Use Docker with FrankenPHP (see `DEVELOPMENT.md`)
   - Or set up a local Joomla 5+ installation
   - PHP 8.1+ required

4. **Work Directory**
   - All development happens in `healthchecker/` directory
   - Changes reflect via symlinks in `joomla/` for testing

#### Code Quality Standards

We maintain strict code quality standards. All contributions must pass:

- **PHP CS Fixer**: PSR-12 coding standards
- **Rector**: Modern PHP 8.1+ patterns
- **PHPStan**: Level 8 static analysis
- **PHPUnit**: All 200+ tests must pass

Run quality checks before committing:
```bash
composer check        # Run all checks
composer cs:fix       # Fix code style
composer rector       # Apply Rector rules
composer phpstan      # Run static analysis
composer test         # Run PHPUnit tests
```

#### Git Workflow

1. **Create a Feature Branch**
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **Make Your Changes**
   - Follow existing code patterns
   - Add tests for new functionality
   - Update documentation if needed

3. **Commit Your Changes**
   - Write clear, descriptive commit messages
   - Use conventional commit format when possible
   - Pre-commit hooks will run quality checks automatically

4. **Push and Create Pull Request**
   ```bash
   git push origin feature/your-feature-name
   ```
   - Open a PR against the `main` branch
   - Provide a clear description of changes
   - Reference any related issues

### Adding New Health Checks

See the [Developer Guide](https://joomlahealthchecker.com/docs/developers/) for detailed instructions on creating health checks.

Quick overview:
1. Create a new check class in `healthchecker/plugins/core/src/Checks/{Category}/`
2. Extend `AbstractHealthCheck`
3. Implement `performCheck()` method
4. Add language keys to `plg_healthchecker_core.ini`
5. Include comprehensive documentation header (WHY/GOOD/WARNING/CRITICAL)

### Creating Third-Party Plugins

If you want to add health checks for third-party extensions:
- See `healthchecker/plugins/example/` for a complete template
- Reference the [Developer API documentation](https://joomlahealthchecker.com/docs/developers/api-reference)
- Consider publishing your plugin separately for the community

## Code Conventions

### PHP Standards

- **PHP Version**: 8.1+ features encouraged
  - Constructor property promotion
  - Readonly properties
  - Enums (backed string enums)
  - Match expressions
  - Nullsafe operator
  - Named arguments

- **Type Declarations**: Always use strict types
  ```php
  declare(strict_types=1);
  ```

- **Namespacing**: Follow PSR-4
  ```php
  namespace MySitesGuru\HealthChecker\Component\Administrator\Check;
  ```

### Naming Conventions

- **Check Slugs**: `{provider}.{check_name}` (lowercase, underscores)
  - Example: `core.php_version`

- **Language Keys**: `COM_HEALTHCHECKER_CHECK_{SLUG_UPPERCASE}_TITLE`
  - Example: `COM_HEALTHCHECKER_CHECK_CORE_PHP_VERSION_TITLE`

- **Class Names**: PascalCase
  - Example: `PhpVersionCheck`

### Documentation Requirements

Every health check class MUST include a header:

```php
/**
 * [Check Name] Health Check
 *
 * [Brief description]
 *
 * WHY THIS CHECK IS IMPORTANT:
 * [Explain risks and benefits]
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

### File Headers

All PHP files must include:

```php
<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  HealthChecker.Core
 *
 * @copyright   (C) 2026 Health Checker for Joomla
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
```

## Testing

### Running Tests

```bash
composer test              # Run all tests
composer test:unit         # Unit tests only
composer test:coverage     # Generate coverage report
```

### Writing Tests

- Test files go in `tests/` directory
- Use PHPUnit 10+ features
- Follow AAA pattern (Arrange, Act, Assert)
- Mock external dependencies
- Aim for high coverage on business logic

Example:
```php
public function testPhpVersionCheckReturnsGoodForSupportedVersion(): void
{
    // Arrange
    $check = new PhpVersionCheck();

    // Act
    $result = $check->run();

    // Assert
    $this->assertSame(HealthStatus::Good, $result->status);
}
```

## Documentation

### User Documentation

User-facing documentation is in `docs/USER/` using VitePress:

```bash
cd docs/USER
npm install
npm run docs:dev      # Start dev server
npm run docs:build    # Build for production
```

Documentation is automatically rebuilt during releases.

### Code Documentation

- Use PHPDoc blocks for classes and public methods
- Document complex logic with inline comments
- Keep documentation up-to-date with code changes

## License

By contributing, you agree that your contributions will be licensed under the GNU General Public License v2 or later, the same license as the project.

## Questions?

- **Documentation**: https://joomlahealthchecker.com/docs
- **Issues**: https://github.com/mySites-guru/HealthCheckerForJoomla/issues
- **Discussions**: https://github.com/mySites-guru/HealthCheckerForJoomla/discussions

Thank you for contributing to Health Checker for Joomla!
