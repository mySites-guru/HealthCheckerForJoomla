---
description: "Complete API reference for Health Checker for Joomla: interfaces, events, services, result types, and helper methods."
---

# API Reference

Complete API reference for Health Checker extension development.

## Core Interfaces

### HealthCheckInterface

The main interface that all health checks must implement.

```php
interface HealthCheckInterface
{
    /**
     * Get unique slug identifier (e.g., "core.php_version")
     * Format: {provider}.{check_name}
     * Must use lowercase, numbers, and underscores only
     */
    public function getSlug(): string;

    /**
     * Get human-readable title (localized)
     * Default implementation derives from language key:
     * COM_HEALTHCHECKER_CHECK_{SLUG_UPPERCASE}_TITLE
     */
    public function getTitle(): string;

    /**
     * Get category slug (e.g., "system", "security")
     * Standard categories: system, database, security, users,
     * extensions, performance, seo, content
     * Or use custom category registered via CollectCategoriesEvent
     */
    public function getCategory(): string;

    /**
     * Get provider slug (e.g., "core", "akeeba_backup")
     * Identifies which plugin/component created this check
     */
    public function getProvider(): string;

    /**
     * Execute the health check and return result
     * Called by HealthCheckRunner during check execution
     * Should be fast (< 1 second target)
     */
    public function run(): HealthCheckResult;
}
```

**Contract requirements**:
- `getSlug()` **MUST** return unique identifier in format `{provider}.{check_name}`
- `getTitle()` **MUST** return translatable string (AbstractHealthCheck auto-implements this)
- `getCategory()` **MUST** return valid category slug (registered via event or built-in)
- `getProvider()` **MUST** match a provider registered via `CollectProvidersEvent`
- `run()` **MUST** return `HealthCheckResult` (never null, never throw unhandled exceptions if using AbstractHealthCheck)

### AbstractHealthCheck

Base class that implements HealthCheckInterface with common functionality.

```php
abstract class AbstractHealthCheck implements HealthCheckInterface
{
    use DatabaseAwareTrait;

    abstract protected function performCheck(): HealthCheckResult;

    protected function critical(string $description): HealthCheckResult;
    protected function warning(string $description): HealthCheckResult;
    protected function good(string $description): HealthCheckResult;

    public function getTitle(): string;
    public function getProvider(): string;
    public function run(): HealthCheckResult;

    // Optional URL methods (return null by default)
    public function getDocsUrl(): ?string;                        // Override to add "Docs" button
    public function getActionUrl(?HealthStatus $status = null): ?string; // Override to add "Explore" button
}
```

**Methods to implement**:
- `performCheck()` - Your check logic goes here

**Helper methods**:
- `critical($desc)` - Return critical status
- `warning($desc)` - Return warning status
- `good($desc)` - Return good status
- `getDatabase()` - Access Joomla database (from DatabaseAwareTrait)

**Auto-implemented**:
- `getTitle()` - Loads from language file
- `getProvider()` - Returns 'core' by default (override if needed)
- `run()` - Wraps performCheck() with error handling
- `getDocsUrl()` - Returns null by default (override to add "Docs" button)
- `getActionUrl($status)` - Returns null by default (override to add "Explore" button, receives check status to allow conditional display)

### Error Handling

**Automatic exception handling**: The `run()` method in `AbstractHealthCheck` wraps your `performCheck()` implementation in a try/catch block:

```php
final public function run(): HealthCheckResult
{
    try {
        return $this->performCheck();
    } catch (\Throwable $throwable) {
        return $this->warning(
            Text::sprintf('COM_HEALTHCHECKER_CHECK_ERROR', $throwable->getMessage())
        );
    }
}
```

**What this means for you**:
- Any uncaught exception in `performCheck()` automatically returns a **WARNING** result
- The exception message is included in the warning description
- Your check never crashes the entire health check run
- Database errors, network timeouts, file access issues are all handled gracefully

**When to handle exceptions yourself**:
```php
protected function performCheck(): HealthCheckResult
{
    try {
        $result = $this->riskyOperation();
        return $this->good('Operation successful');
    } catch (SpecificException $e) {
        // Custom handling for specific errors
        return $this->critical('Known critical issue: ' . $e->getMessage());
    }
    // All other exceptions caught by AbstractHealthCheck
}
```

### Database Access

**Null-safe database access**:

```php
// Option 1: Null-safe getter (returns null if not injected)
$db = $this->getDatabase();
if ($db === null) {
    return $this->warning('Database not available');
}

// Option 2: Require database (throws exception if not injected)
$db = $this->requireDatabase(); // Throws RuntimeException if null
```

**Database injection**: The `HealthCheckRunner` automatically injects the database before calling `run()`:

```php
foreach ($checks as $check) {
    if ($check instanceof AbstractHealthCheck) {
        $check->setDatabase($this->database);
    }
    $this->results[] = $check->run();
}
```

### Check Execution Order

**There is NO guaranteed execution order**. Checks are:
1. Collected from all plugins via `CollectChecksEvent`
2. Stored in an array in the order plugins respond
3. Executed sequentially in that arbitrary order
4. Sorted by status (critical first) and category AFTER execution

**Implications**:
- Don't depend on another check running first
- Each check must be self-contained
- Checks run in series, not parallel (one at a time)
- Total run time = sum of all check times

### Timeout Handling

**Current behavior**:
- No per-check timeout limit
- No global timeout limit
- Checks run until PHP's `max_execution_time` (typically 30-300 seconds)
- Slow checks block the entire health check run

**Best practices**:
- Target < 1 second per check
- Use timeouts for external HTTP requests
- Avoid expensive operations (large file scans, complex queries)
- Test with real data volumes

**Example with timeout**:
```php
use Joomla\CMS\Http\HttpFactory;

protected function performCheck(): HealthCheckResult
{
    $http = HttpFactory::getHttp([], ['timeout' => 5]); // 5 second timeout
    try {
        $response = $http->get('https://api.example.com/status');
        return $this->good('API accessible');
    } catch (\RuntimeException $e) {
        return $this->warning('API timeout or unreachable');
    }
}
```

## Data Classes

### HealthCheckResult

Immutable result object returned by checks.

```php
final readonly class HealthCheckResult
{
    public function __construct(
        public HealthStatus $healthStatus,
        public string $title,
        public string $description,
        public string $slug,
        public string $category,
        public string $provider = 'core',
        public ?string $docsUrl = null,    // @since 3.0.36
        public ?string $actionUrl = null   // @since 3.0.36
    );

    public function toArray(): array;
    public static function fromArray(array $data): self;
}
```

**Properties**:
- `healthStatus` - One of: HealthStatus::Good, Warning, or Critical
- `title` - Check title
- `description` - Result description
- `slug` - Check slug
- `category` - Category slug
- `provider` - Provider slug
- `docsUrl` - URL for documentation link (displays ? icon) *@since 3.0.36*
- `actionUrl` - URL to navigate when row is clicked *@since 3.0.36*

### HealthStatus

Enum defining possible check statuses.

```php
enum HealthStatus: string
{
    case Critical = 'critical';
    case Warning = 'warning';
    case Good = 'good';
}
```

### ProviderMetadata

Provider information for attribution.

```php
final readonly class ProviderMetadata
{
    public function __construct(
        public string $slug,
        public string $name,
        public string $description = '',
        public ?string $url = null,
        public ?string $icon = null,
        public ?string $logoUrl = null,
        public ?string $version = null
    );
}
```

**Required**:
- `slug` - Unique provider identifier
- `name` - Display name

**Optional**:
- `description` - Brief description
- `url` - Website URL
- `icon` - FontAwesome icon class
- `logoUrl` - Path to logo image
- `version` - Extension version

### HealthCategory

Category definition for grouping checks.

```php
final readonly class HealthCategory
{
    public function __construct(
        public string $slug,
        public string $label,
        public string $icon,
        public int $sortOrder = 50
    );
}
```

**Properties**:
- `slug` - Unique category identifier
- `label` - Language key for display name
- `icon` - FontAwesome icon class
- `sortOrder` - Display order (lower first)

## Events

### CollectChecksEvent

Dispatched to collect health check instances.

```php
final class CollectChecksEvent extends AbstractImmutableEvent
{
    public function addResult(HealthCheckInterface $check): void;
    public function getChecks(): array;
}
```

**Usage**:
```php
public function onCollectChecks(CollectChecksEvent $event): void
{
    $check = new YourCheck();
    $check->setDatabase($this->getDatabase());
    $event->addResult($check);
}
```

### CollectCategoriesEvent

Dispatched to collect custom categories.

```php
final class CollectCategoriesEvent extends AbstractImmutableEvent
{
    public function addResult(HealthCategory $category): void;
    public function getCategories(): array;
}
```

**Usage**:
```php
public function onCollectCategories(CollectCategoriesEvent $event): void
{
    $event->addResult(new HealthCategory(
        slug: 'custom',
        label: 'YOUR_LANG_KEY',
        icon: 'fa-icon',
        sortOrder: 90
    ));
}
```

### CollectProvidersEvent

Dispatched to collect provider metadata.

```php
final class CollectProvidersEvent extends AbstractImmutableEvent
{
    public function addResult(ProviderMetadata $provider): void;
    public function getProviders(): array;
}
```

**Usage**:
```php
public function onCollectProviders(CollectProvidersEvent $event): void
{
    $event->addResult(new ProviderMetadata(
        slug: 'yourplugin',
        name: 'Your Plugin',
        description: 'Health checks for Your Plugin'
    ));
}
```

## Plugin Structure

### Minimal Plugin

```php
<?php
namespace YourVendor\Plugin\HealthChecker\YourPlugin\Extension;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;

final class YourPlugin extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;

    public static function getSubscribedEvents(): array
    {
        return [
            'onHealthCheckerCollectChecks' => 'onCollectChecks',
            'onHealthCheckerCollectProviders' => 'onCollectProviders',
        ];
    }

    public function onCollectChecks(CollectChecksEvent $event): void
    {
        $check = new YourCheck();
        $check->setDatabase($this->getDatabase());
        $event->addResult($check);
    }

    public function onCollectProviders(CollectProvidersEvent $event): void
    {
        $event->addResult(new ProviderMetadata(
            slug: 'yourplugin',
            name: 'Your Plugin'
        ));
    }
}
```

## Common Patterns

### Database Query

```php
protected function performCheck(): HealthCheckResult
{
    $db = $this->getDatabase();
    $query = $db->getQuery(true)
        ->select('COUNT(*)')
        ->from($db->quoteName('#__your_table'));

    $db->setQuery($query);
    $count = (int) $db->loadResult();

    return $count > 0
        ? $this->good("Found {$count} records")
        : $this->warning('No records found');
}
```

### Configuration Check

```php
use Joomla\CMS\Factory;

protected function performCheck(): HealthCheckResult
{
    $config = Factory::getApplication()->get('setting_name');

    return $config
        ? $this->good('Setting is enabled')
        : $this->warning('Setting is disabled');
}
```

### File/Directory Check

```php
use Joomla\CMS\Filesystem\File;

protected function performCheck(): HealthCheckResult
{
    $path = JPATH_ROOT . '/required/file.txt';

    if (!File::exists($path)) {
        return $this->critical('Required file missing');
    }

    return $this->good('Required file exists');
}
```

### HTTP Request

```php
use Joomla\CMS\Http\HttpFactory;

protected function performCheck(): HealthCheckResult
{
    try {
        $http = HttpFactory::getHttp();
        $response = $http->get('https://api.example.com/status');

        return $response->code === 200
            ? $this->good('API is accessible')
            : $this->warning("API returned {$response->code}");

    } catch (\Exception $e) {
        return $this->critical("API unreachable: {$e->getMessage()}");
    }
}
```

## Constants and Paths

### Joomla Path Constants

```php
JPATH_ROOT              // Site root directory
JPATH_SITE              // Site root (alias)
JPATH_ADMINISTRATOR     // Admin directory
JPATH_LIBRARIES         // Libraries directory
JPATH_PLUGINS           // Plugins directory
JPATH_CACHE             // Cache directory
JPATH_CONFIGURATION     // Configuration directory
```

## Best Practices

1. **Extend AbstractHealthCheck** - Don't implement HealthCheckInterface directly
2. **Inject database** - Always call `$check->setDatabase()` when creating checks
3. **Return early** - Check critical conditions first
4. **Be specific** - Detailed, actionable descriptions
5. **Handle errors** - Let AbstractHealthCheck catch exceptions or handle explicitly
6. **Stay fast** - Target under 1 second per check
7. **Read-only** - Never modify data in checks

## Next Steps

- [Creating Checks](./creating-checks.md) - Build your first check
- [Examples](./examples.md) - See complete working examples
- [Best Practices](./best-practices.md) - Code conventions and guidelines
