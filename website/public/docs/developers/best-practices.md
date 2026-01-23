---
url: /docs/developers/best-practices.md
---
# Best Practices

Code conventions, guidelines, and best practices for Health Checker plugin development.

## Code Organization

### File Structure

```
plugins/healthchecker/yourplugin/
├── language/
│   └── en-GB/
│       ├── plg_healthchecker_yourplugin.ini
│       └── plg_healthchecker_yourplugin.sys.ini
├── media/
│   ├── logo.svg
│   └── css/
│       └── styles.css
├── services/
│   └── provider.php
├── src/
│   ├── Extension/
│   │   └── YourPlugin.php
│   └── Checks/
│       ├── FirstCheck.php
│       ├── SecondCheck.php
│       └── ThirdCheck.php
└── yourplugin.xml
```

### Naming Conventions

**Check Classes**:

```php
// ✅ Good
class ApiConnectionCheck
class BackupStatusCheck
class CacheSizeCheck

// ❌ Avoid
class Check1
class MyCheck
class TestingCheck
```

**Check Slugs**:

```php
// ✅ Good
'yourplugin.api_connection'
'yourplugin.backup_status'
'yourplugin.cache_size'

// ❌ Avoid
'yourplugin.check1'
'api_connection' // Missing provider prefix
'yourplugin.api-connection' // Use underscores, not hyphens
```

## Check Design

### Single Responsibility

Each check should test ONE thing:

```php
// ✅ Good - Single purpose
class ApiKeyConfiguredCheck extends AbstractHealthCheck
{
    protected function performCheck(): HealthCheckResult
    {
        $apiKey = $this->getConfigValue('api_key');
        return $apiKey
            ? $this->good('API key configured')
            : $this->critical('API key not configured');
    }
}

// ❌ Bad - Multiple responsibilities
class ApiCheck extends AbstractHealthCheck
{
    protected function performCheck(): HealthCheckResult
    {
        // Checks key, connectivity, AND permissions
        // Split into 3 separate checks
    }
}
```

### Fast Execution

Target: Under 1 second per check

```php
// ✅ Good - Fast
protected function performCheck(): HealthCheckResult
{
    $exists = file_exists('/path/to/file');
    return $exists ? $this->good('File exists') : $this->critical('Missing');
}

// ❌ Bad - Slow
protected function performCheck(): HealthCheckResult
{
    sleep(10); // Never block!
    $this->scanEntireFilesystem(); // Too slow!
    $this->downloadLargeFile(); // Don't do this!
}
```

### Read-Only Operations

NEVER modify anything in a health check:

```php
// ✅ Good - Read only
protected function performCheck(): HealthCheckResult
{
    $cacheSize = $this->getCacheDirectorySize();
    return $cacheSize > 1000000
        ? $this->warning('Cache is large. Consider clearing.')
        : $this->good('Cache size is reasonable.');
}

// ❌ Bad - Modifies data
protected function performCheck(): HealthCheckResult
{
    $this->clearCache(); // NEVER modify!
    $this->deleteFiles(); // NEVER delete!
    $this->updateDatabase(); // NEVER update!
    return $this->good('Cache cleared');
}
```

## Result Messages

### Be Specific

```php
// ✅ Good - Specific
return $this->critical(
    'PHP memory limit is 128M but 256M is recommended for optimal performance. ' .
    'Current usage: 96M. Contact your hosting provider to increase this limit.'
);

// ❌ Bad - Vague
return $this->critical('Memory is low');
```

### Be Actionable

```php
// ✅ Good - Actionable
return $this->warning(
    'Page caching is disabled. Enable it at: ' .
    'System → Global Configuration → System → Cache: ON'
);

// ❌ Bad - Not actionable
return $this->warning('Caching could be better');
```

### Include Context

```php
// ✅ Good - With context
return $this->warning(sprintf(
    'Last backup is %d days old. Recommended: daily backups. ' .
    'Visit Components → Akeeba Backup to run a backup.',
    $daysSinceBackup
));

// ❌ Bad - No context
return $this->warning('Old backup');
```

## Error Handling

### Let Base Class Handle

```php
// ✅ Good - Automatic error handling
protected function performCheck(): HealthCheckResult
{
    // Exceptions automatically caught and returned as critical
    $db = $this->getDatabase();
    $result = $db->loadResult(); // May throw
    return $this->good('Success');
}
```

### Handle Specific Cases

```php
// ✅ Good - Handle expected exceptions
protected function performCheck(): HealthCheckResult
{
    try {
        $response = $this->makeApiCall();
        return $this->good('API accessible');

    } catch (TimeoutException $e) {
        return $this->warning('API timed out. Retry later.');

    } catch (AuthException $e) {
        return $this->critical('Invalid API credentials');

    } catch (\Exception $e) {
        // Let base class handle unexpected errors
        throw $e;
    }
}
```

## Performance

### Cache When Appropriate

```php
// ✅ Good - Cache expensive operations
protected function performCheck(): HealthCheckResult
{
    static $result = null;

    if ($result === null) {
        $result = $this->expensiveOperation();
    }

    return $result ? $this->good('OK') : $this->warning('Issue');
}
```

### Lazy Loading

```php
// ✅ Good - Only load when needed
protected function performCheck(): HealthCheckResult
{
    // Quick check first
    if (!$this->isFeatureEnabled()) {
        return $this->good('Feature disabled (expected)');
    }

    // Expensive check only if needed
    return $this->performDetailedCheck();
}
```

## Testing

### Test All Status Levels

Ensure your check can return all three statuses:

```php
// ✅ Complete - All statuses possible
protected function performCheck(): HealthCheckResult
{
    $value = $this->getValue();

    if ($value < 10) {
        return $this->critical('Value critically low');
    }

    if ($value < 50) {
        return $this->warning('Value below recommended');
    }

    return $this->good('Value is optimal');
}

// ❌ Incomplete - Only two statuses
protected function performCheck(): HealthCheckResult
{
    return $this->isEnabled()
        ? $this->good('Enabled')
        : $this->warning('Disabled');
    // What about critical? When would that occur?
}
```

### Test Edge Cases

```php
// ✅ Good - Handles edge cases
protected function performCheck(): HealthCheckResult
{
    $value = $this->getValue();

    // Handle null/empty
    if ($value === null || $value === '') {
        return $this->critical('Value not set');
    }

    // Handle invalid type
    if (!is_numeric($value)) {
        return $this->warning('Value is not numeric');
    }

    // Normal cases
    return (int)$value > 0
        ? $this->good('Value is positive')
        : $this->warning('Value is zero or negative');
}
```

## Documentation

### Class Documentation

Every check must include header documentation:

```php
/**
 * API Connection Health Check
 *
 * Verifies that the API endpoint is accessible and responding correctly.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * The API connection is required for all sync operations. Without a working
 * connection, data sync will fail and users will see errors.
 *
 * RESULT MEANINGS:
 *
 * GOOD: API is accessible and responding with status 200.
 *
 * WARNING: API is accessible but responding slowly (>2 seconds) or with
 *          non-200 status. Check may indicate degraded service.
 *
 * CRITICAL: API is completely unreachable, credentials are invalid, or
 *           network connectivity is blocked. Requires immediate attention.
 */
final class ApiConnectionCheck extends AbstractHealthCheck
{
    // Implementation
}
```

### Code Comments

Comment non-obvious logic:

```php
// ✅ Good - Explains why
protected function performCheck(): HealthCheckResult
{
    // We multiply by 1.5 because MySQL reports compressed size,
    // but we need to account for index overhead
    $estimatedSize = $reportedSize * 1.5;

    return $estimatedSize > $limit
        ? $this->warning('Size approaching limit')
        : $this->good('Size is reasonable');
}

// ❌ Bad - Comments what (obvious from code)
protected function performCheck(): HealthCheckResult
{
    // Get the value
    $value = $this->getValue();

    // Return good if true
    return $value ? $this->good('Good') : $this->warning('Bad');
}
```

## Language Keys

### Consistent Format

```ini
; ✅ Good
PLG_HEALTHCHECKER_YOURPLUGIN_CHECK_YOURPLUGIN_API_CONNECTION_TITLE="API Connection"
PLG_HEALTHCHECKER_YOURPLUGIN_CHECK_YOURPLUGIN_BACKUP_STATUS_TITLE="Backup Status"
PLG_HEALTHCHECKER_YOURPLUGIN_CATEGORY_YOURPLUGIN="Your Plugin"

; ❌ Avoid
PLG_HEALTHCHECKER_YOURPLUGIN_API="API"
PLG_HEALTHCHECKER_YOURPLUGIN_CHECK1="Check 1"
```

### Descriptive Titles

```ini
; ✅ Good - Clear and specific
PLG_HEALTHCHECKER_YOURPLUGIN_CHECK_YOURPLUGIN_CACHE_SIZE_TITLE="Cache Directory Size"
PLG_HEALTHCHECKER_YOURPLUGIN_CHECK_YOURPLUGIN_API_KEY_CONFIGURED_TITLE="API Key Configuration"

; ❌ Bad - Vague
PLG_HEALTHCHECKER_YOURPLUGIN_CHECK_YOURPLUGIN_CHECK1_TITLE="Check 1"
PLG_HEALTHCHECKER_YOURPLUGIN_CHECK_YOURPLUGIN_STATUS_TITLE="Status"
```

## Security

### Sanitize Output

```php
// ✅ Good - Sanitized
protected function performCheck(): HealthCheckResult
{
    $path = $this->getPath();
    $safePath = htmlspecialchars($path, ENT_QUOTES, 'UTF-8');

    return $this->warning("File not found at: {$safePath}");
}

// ❌ Bad - Unsanitized (XSS risk)
protected function performCheck(): HealthCheckResult
{
    $userInput = $_GET['path']; // Never do this!
    return $this->warning("Path: {$userInput}");
}
```

### Don't Expose Secrets

```php
// ✅ Good - Masked
protected function performCheck(): HealthCheckResult
{
    $apiKey = $this->getApiKey();
    $masked = substr($apiKey, 0, 4) . '...' . substr($apiKey, -4);

    return $this->warning("Invalid API key: {$masked}");
}

// ❌ Bad - Exposes secret
protected function performCheck(): HealthCheckResult
{
    $apiKey = $this->getApiKey();
    return $this->warning("Invalid API key: {$apiKey}");
}
```

## Checklist

Before releasing your health check plugin:

* \[ ] All checks extend AbstractHealthCheck
* \[ ] Database is injected when creating checks
* \[ ] Provider metadata registered
* \[ ] All checks have unique slugs with provider prefix
* \[ ] Language keys follow naming convention
* \[ ] Check titles are descriptive
* \[ ] Result messages are specific and actionable
* \[ ] All three statuses (good/warning/critical) are possible
* \[ ] Checks are read-only (no modifications)
* \[ ] Checks execute in under 1 second
* \[ ] Error handling is appropriate
* \[ ] Code is documented (class headers)
* \[ ] No secrets exposed in messages
* \[ ] Tested in Joomla admin interface

## Next Steps

* [Examples](./examples.md) - See complete working examples
* [Quick Start](./quick-start.md) - Build your first plugin
* [API Reference](./api-reference.md) - Complete API documentation
