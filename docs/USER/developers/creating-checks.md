---
description: "How to create custom health checks for Joomla. Extend AbstractHealthCheck, implement performCheck(), and return results."
---

# Creating Health Checks

Learn how to create custom health checks for your Joomla extension.

## Overview

A health check is a PHP class that:
1. Extends `AbstractHealthCheck`
2. Implements `performCheck()` method
3. Returns a `HealthCheckResult` with status and description

## Basic Health Check Structure

```php
<?php
namespace YourVendor\Plugin\HealthChecker\YourPlugin\Checks;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

defined('_JEXEC') or die;

final class ExampleCheck extends AbstractHealthCheck
{
    public function getSlug(): string
    {
        return 'yourplugin.example_check';
    }

    public function getCategory(): string
    {
        return 'extensions'; // or your custom category
    }

    public function getProvider(): string
    {
        return 'yourplugin'; // matches your ProviderMetadata slug
    }

    protected function performCheck(): HealthCheckResult
    {
        // Your check logic here

        if (/* everything is good */) {
            return $this->good('Check passed successfully.');
        }

        if (/* needs attention */) {
            return $this->warning('Issue found that should be addressed.');
        }

        return $this->critical('Critical issue requires immediate attention.');
    }
}
```

## Documentation and Action URLs

Health checks can provide two optional URLs that display as buttons on the right side of each result row:

### Documentation URL (`getDocsUrl`)

When implemented, displays a **"Docs"** button on the right side of the result row. Clicking the button opens the documentation URL in a new browser tab.

```php
public function getDocsUrl(): ?string
{
    return 'https://docs.yoursite.com/checks/example-check';
}
```

**Use cases**:
- Link to detailed documentation explaining the check
- Link to troubleshooting guides
- Link to the source code on GitHub

### Action URL (`getActionUrl`)

When implemented, displays an **"Explore"** button on the right side of the result row. Clicking the button navigates to the action URL in the same window.

The method receives an optional `HealthStatus` parameter, allowing you to conditionally show the action button based on the check result. This is useful when an action is only needed for failed checks.

```php
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;

public function getActionUrl(?HealthStatus $status = null): ?string
{
    return '/administrator/index.php?option=com_yourplugin&view=settings';
}
```

**Conditional Action URLs**:

You can return different URLs (or `null`) based on the check status:

```php
public function getActionUrl(?HealthStatus $status = null): ?string
{
    // Only show action button for failed checks, not for Good status
    if ($status === HealthStatus::Good) {
        return null;
    }

    return '/administrator/index.php?option=com_yourplugin&view=settings';
}
```

**Use cases**:
- Link to the configuration page where users can fix the issue
- Link to the Joomla component that needs attention
- Link to the relevant admin panel section
- Hide the action button when no action is needed (Good status)

**Example with both URLs**:

```php
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;

final class ApiConfigCheck extends AbstractHealthCheck
{
    public function getSlug(): string
    {
        return 'yourplugin.api_config';
    }

    public function getCategory(): string
    {
        return 'extensions';
    }

    public function getProvider(): string
    {
        return 'yourplugin';
    }

    public function getDocsUrl(): ?string
    {
        return 'https://docs.yoursite.com/configuration/api-settings';
    }

    public function getActionUrl(?HealthStatus $status = null): ?string
    {
        // Only show Explore button when there's an issue to fix
        if ($status === HealthStatus::Good) {
            return null;
        }

        return '/administrator/index.php?option=com_yourplugin&view=config';
    }

    protected function performCheck(): HealthCheckResult
    {
        // Check logic here
        return $this->good('API is configured correctly.');
    }
}
```

**Notes**:
- Both methods return `?string` - return `null` (or don't override) to hide the button
- `getDocsUrl()` opens in a new tab, `getActionUrl()` opens in the same window
- Action URLs should be relative administrator paths (starting with `/administrator/`)
- Documentation URLs can be absolute URLs to external documentation
- The `$status` parameter is optional for backwards compatibility - existing checks without it will continue to work

## Check Slug Format

**Format**: `{provider}.{check_name}`

**Rules**:
- Lowercase only
- Use underscores for spaces
- Must be unique across all checks
- Provider prefix helps avoid conflicts

**Examples**:
- `core.php_version`
- `akeebabackup.last_backup_age`
- `yourplugin.api_connection`

## Status Levels

### Good (Green)
Everything is optimal. No action needed.

```php
return $this->good('All settings configured correctly.');
```

**Use when**:
- Feature is properly configured
- Values are within optimal range
- No issues detected

### Warning (Yellow)
Should be addressed, but site still functions.

```php
return $this->warning('Configuration could be improved.');
```

**Use when**:
- Non-optimal configuration
- Recommended but not required features missing
- Values outside recommended range but still functional

### Critical (Red)
Immediate attention required. Site may be broken or at serious risk.

```php
return $this->critical('Required feature is not configured.');
```

**Use when**:
- Required features not configured
- Site functionality broken
- Security vulnerability present
- Data loss risk

## Accessing Database

Health checks can access the Joomla database:

```php
final class DatabaseCheck extends AbstractHealthCheck
{
    protected function performCheck(): HealthCheckResult
    {
        $db = $this->getDatabase();

        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__yourextension_table'));

        $db->setQuery($query);
        $count = (int) $db->loadResult();

        if ($count === 0) {
            return $this->warning('No records found in database.');
        }

        return $this->good(sprintf('Found %d records.', $count));
    }
}
```

**Note**: Database is injected by your plugin's event handler:

```php
public function onCollectChecks(CollectChecksEvent $event): void
{
    $check = new YourCheck();
    $check->setDatabase($this->getDatabase()); // Important!
    $event->addResult($check);
}
```

## Accessing Configuration

Check Joomla Global Configuration:

```php
use Joomla\CMS\Factory;

protected function performCheck(): HealthCheckResult
{
    $config = Factory::getApplication()->get('debug');

    if ($config) {
        return $this->critical('Debug mode is enabled in production.');
    }

    return $this->good('Debug mode is disabled.');
}
```

Check extension parameters:

```php
use Joomla\CMS\Component\ComponentHelper;

protected function performCheck(): HealthCheckResult
{
    $params = ComponentHelper::getParams('com_yourextension');
    $apiKey = $params->get('api_key');

    if (empty($apiKey)) {
        return $this->critical('API key not configured.');
    }

    return $this->good('API key is configured.');
}
```

## Checking Files and Directories

```php
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;

protected function performCheck(): HealthCheckResult
{
    $path = JPATH_ROOT . '/path/to/required/file.php';

    if (!File::exists($path)) {
        return $this->critical('Required file is missing.');
    }

    if (!is_readable($path)) {
        return $this->warning('File exists but is not readable.');
    }

    return $this->good('Required file exists and is readable.');
}
```

Check directory writability:

```php
protected function performCheck(): HealthCheckResult
{
    $dir = JPATH_ROOT . '/path/to/directory';

    if (!Folder::exists($dir)) {
        return $this->critical('Required directory does not exist.');
    }

    if (!is_writable($dir)) {
        return $this->warning('Directory is not writable.');
    }

    return $this->good('Directory exists and is writable.');
}
```

## Making HTTP Requests

Check external API connectivity:

```php
use Joomla\CMS\Http\HttpFactory;

protected function performCheck(): HealthCheckResult
{
    try {
        $http = HttpFactory::getHttp();
        $response = $http->get('https://api.example.com/status');

        if ($response->code !== 200) {
            return $this->warning(sprintf(
                'API returned status %d instead of 200.',
                $response->code
            ));
        }

        return $this->good('API is responding correctly.');

    } catch (\Exception $e) {
        return $this->critical(sprintf(
            'Cannot connect to API: %s',
            $e->getMessage()
        ));
    }
}
```

## Error Handling

The base `AbstractHealthCheck` class automatically catches exceptions:

```php
protected function performCheck(): HealthCheckResult
{
    // If this throws an exception, it will be caught automatically
    // and returned as a critical status with the exception message

    $db = $this->getDatabase();
    $result = $db->loadResult(); // Could throw exception

    return $this->good('Query succeeded.');
}
```

You can also handle exceptions explicitly:

```php
protected function performCheck(): HealthCheckResult
{
    try {
        // Risky operation
        $result = $this->doSomethingRisky();

        return $this->good('Operation succeeded.');

    } catch (\RuntimeException $e) {
        return $this->warning(sprintf(
            'Operation failed but recoverable: %s',
            $e->getMessage()
        ));

    } catch (\Exception $e) {
        // Let AbstractHealthCheck handle other exceptions
        throw $e;
    }
}
```

## Language Strings

Add language strings for your check title:

**File**: `plugins/healthchecker/yourplugin/language/en-GB/plg_healthchecker_yourplugin.ini`

```ini
PLG_HEALTHCHECKER_YOURPLUGIN_CHECK_YOURPLUGIN_EXAMPLE_CHECK_TITLE="Example Health Check"
```

**Format**: `PLG_HEALTHCHECKER_{PLUGIN}_{SLUG_UPPERCASE}_TITLE`

**Example**:
- Slug: `yourplugin.api_connection`
- Key: `PLG_HEALTHCHECKER_YOURPLUGIN_YOURPLUGIN_API_CONNECTION_TITLE`
- Value: `API Connection Status`

The `getTitle()` method automatically uses this language key.

## Best Practices

### 1. Fast Execution
```php
// ❌ Slow
protected function performCheck(): HealthCheckResult
{
    sleep(10); // Don't do this!
    return $this->good('Done');
}

// ✅ Fast
protected function performCheck(): HealthCheckResult
{
    // Quick check only
    return $this->good('Done');
}
```

**Target**: Each check should complete in under 1 second.

### 2. Clear Descriptions

Health check descriptions support safe HTML formatting for better readability.

**Allowed HTML Tags:**
- `<br>` - Line breaks
- `<p>` - Paragraphs
- `<strong>`, `<b>` - Bold text
- `<em>`, `<i>` - Italic/emphasis
- `<u>` - Underline
- `<code>`, `<pre>` - Code formatting
- `<ul>`, `<ol>`, `<li>` - Lists

**NOT Allowed (automatically stripped):**
- `<a>` - Links (phishing risk)
- `<script>`, `<style>`, `<iframe>` - Script execution
- Event handlers (`onclick`, `onerror`, etc.)
- `style` attributes

```php
// ❌ Vague
return $this->warning('Something is wrong.');

// ✅ Specific with formatting
return $this->warning(
    '<p>API key is configured but has not been tested.</p>' .
    '<p>Visit <strong>Components → Your Extension → Settings</strong> to test the connection.</p>'
);

// ✅ Using lists for multiple issues
return $this->warning(
    '<p>The following issues were found:</p>' .
    '<ul>' .
    '<li>Cache directory is not writable</li>' .
    '<li>Temporary files older than 30 days exist</li>' .
    '</ul>'
);

// ✅ Using code formatting for commands
return $this->critical(
    '<p>Database connection failed.</p>' .
    '<p>Run: <code>php bin/console doctrine:database:create</code></p>'
);
```

### 3. Actionable Messages
```php
// ❌ Not actionable
return $this->critical('Cache is broken.');

// ✅ Actionable with code formatting
return $this->critical(
    '<p>Cache directory is not writable.</p>' .
    '<pre>chmod 755 cache/</pre>'
);
```

### 4. Appropriate Status
```php
// ❌ Too severe
if ($cacheSize > 1000000) {
    return $this->critical('Cache is large.'); // Not actually critical
}

// ✅ Appropriate
if ($cacheSize > 1000000) {
    return $this->warning(
        sprintf(
            'Cache size is %s. Consider clearing cache.',
            $this->formatBytes($cacheSize)
        )
    );
}
```

### 5. Safe Checks
```php
// ❌ Unsafe
protected function performCheck(): HealthCheckResult
{
    unlink('/some/file'); // NEVER modify anything!
    return $this->good('Deleted file.');
}

// ✅ Safe (read-only)
protected function performCheck(): HealthCheckResult
{
    $exists = file_exists('/some/file');
    return $exists
        ? $this->good('File exists.')
        : $this->warning('File missing.');
}
```

**Rule**: Health checks must be **read-only**. Never modify data, files, or configuration.

## Complete Example

Here's a complete, production-ready health check:

```php
<?php
namespace YourVendor\Plugin\HealthChecker\YourPlugin\Checks;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Http\HttpFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;

defined('_JEXEC') or die;

/**
 * API Connection Health Check
 *
 * Verifies connectivity to external API service and validates API key.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * The API connection is required for all sync operations. If the connection
 * fails, no data will sync and users will see errors.
 *
 * RESULT MEANINGS:
 *
 * GOOD: API key is valid and service is responding correctly.
 *
 * WARNING: API key is configured but untested, or API is slow to respond.
 *
 * CRITICAL: API key is missing, invalid, or service is unreachable.
 */
final class ApiConnectionCheck extends AbstractHealthCheck
{
    private const API_ENDPOINT = 'https://api.example.com/v1/status';
    private const TIMEOUT_SECONDS = 5;

    public function getSlug(): string
    {
        return 'yourplugin.api_connection';
    }

    public function getCategory(): string
    {
        return 'extensions';
    }

    public function getProvider(): string
    {
        return 'yourplugin';
    }

    /**
     * Link to documentation for this check.
     *
     * Displays a "Docs" button that opens this URL in a new tab.
     */
    public function getDocsUrl(): ?string
    {
        return 'https://docs.yoursite.com/health-checks/api-connection';
    }

    /**
     * Link to the settings page where users can fix issues.
     *
     * Displays an "Explore" button that navigates to this URL.
     * Only shown when check fails (Critical or Warning status).
     */
    public function getActionUrl(?HealthStatus $status = null): ?string
    {
        // No action needed when everything is working
        if ($status === HealthStatus::Good) {
            return null;
        }

        return '/administrator/index.php?option=com_yourplugin&view=settings';
    }

    protected function performCheck(): HealthCheckResult
    {
        // Get API key from component config
        $params = ComponentHelper::getParams('com_yourplugin');
        $apiKey = $params->get('api_key', '');

        if (empty($apiKey)) {
            return $this->critical(
                'API key is not configured. ' .
                'Visit Components → Your Plugin → Settings to add your API key.'
            );
        }

        // Test API connection
        try {
            $http = HttpFactory::getHttp();
            $startTime = microtime(true);

            $response = $http->get(
                self::API_ENDPOINT,
                ['Authorization' => 'Bearer ' . $apiKey],
                self::TIMEOUT_SECONDS
            );

            $duration = microtime(true) - $startTime;

            if ($response->code !== 200) {
                return $this->critical(sprintf(
                    'API returned HTTP %d. Check your API key in component settings.',
                    $response->code
                ));
            }

            $body = json_decode($response->body);
            if (!$body || !isset($body->status)) {
                return $this->warning(
                    'API responded but returned invalid data format.'
                );
            }

            if ($duration > 2.0) {
                return $this->warning(sprintf(
                    'API is responding slowly (%.2fs). Service may be experiencing issues.',
                    $duration
                ));
            }

            return $this->good(sprintf(
                'API connection successful (%.2fs response time).',
                $duration
            ));

        } catch (\Exception $e) {
            return $this->critical(sprintf(
                'Cannot connect to API: %s',
                $e->getMessage()
            ));
        }
    }
}
```

## Plugin Configuration (Enable/Disable Checks)

To allow users to enable or disable individual checks, add configuration fields to your plugin XML.

### XML Configuration Structure

Add a `<config>` section to your plugin's XML manifest:

**File**: `plugins/healthchecker/yourplugin/yourplugin.xml`

```xml
<?xml version="1.0" encoding="utf-8"?>
<extension type="plugin" group="healthchecker" method="upgrade">
    <name>plg_healthchecker_yourplugin</name>
    <author>Your Name</author>
    <version>1.0.0</version>
    <description>PLG_HEALTHCHECKER_YOURPLUGIN_XML_DESCRIPTION</description>
    <namespace path="src">YourVendor\Plugin\HealthChecker\YourPlugin</namespace>

    <files>
        <folder plugin="yourplugin">services</folder>
        <folder>src</folder>
        <folder>language</folder>
    </files>

    <!-- Plugin Configuration -->
    <config>
        <fields name="params">
            <fieldset name="checks"
                label="PLG_HEALTHCHECKER_YOURPLUGIN_FIELDSET_CHECKS_LABEL"
                description="PLG_HEALTHCHECKER_YOURPLUGIN_FIELDSET_CHECKS_DESC">

                <!-- Optional: Category Header -->
                <field name="header_yourplugin" type="note"
                    label="PLG_HEALTHCHECKER_YOURPLUGIN_FIELDSET_YOURPLUGIN_LABEL"
                    description="PLG_HEALTHCHECKER_YOURPLUGIN_FIELDSET_YOURPLUGIN_DESC"
                    class="alert alert-info" />

                <!-- Individual Check Toggle -->
                <field name="check_yourplugin_api_connection" type="radio"
                    label="PLG_HEALTHCHECKER_YOURPLUGIN_CHECK_YOURPLUGIN_API_CONNECTION_TITLE"
                    class="btn-group btn-group-yesno"
                    default="1">
                    <option value="1">JENABLED</option>
                    <option value="0">JDISABLED</option>
                </field>

                <field name="check_yourplugin_database_sync" type="radio"
                    label="PLG_HEALTHCHECKER_YOURPLUGIN_CHECK_YOURPLUGIN_DATABASE_SYNC_TITLE"
                    class="btn-group btn-group-yesno"
                    default="1">
                    <option value="1">JENABLED</option>
                    <option value="0">JDISABLED</option>
                </field>

            </fieldset>
        </fields>
    </config>
</extension>
```

### Field Naming Convention

**Format**: `check_{provider}_{check_name}`

**Examples**:
- Check slug: `yourplugin.api_connection`
- Field name: `check_yourplugin_api_connection`

**Rules**:
- Must start with `check_`
- Use underscores (not dots)
- Lowercase only
- Must match your check slug (dots become underscores)

### Checking Configuration in Your Plugin

Read the configuration value in your plugin's event handler:

```php
<?php
namespace YourVendor\Plugin\HealthChecker\YourPlugin\Extension;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectChecksEvent;

final class YourPlugin extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onCollectChecks' => 'onCollectChecks',
        ];
    }

    public function onCollectChecks(CollectChecksEvent $event): void
    {
        // Check if the API connection check is enabled
        $apiCheckEnabled = (bool) $this->params->get('check_yourplugin_api_connection', 1);

        if ($apiCheckEnabled) {
            $check = new \YourVendor\Plugin\HealthChecker\YourPlugin\Checks\ApiConnectionCheck();
            $check->setDatabase($this->getDatabase());
            $event->addResult($check);
        }

        // Check if the database sync check is enabled
        $dbCheckEnabled = (bool) $this->params->get('check_yourplugin_database_sync', 1);

        if ($dbCheckEnabled) {
            $check = new \YourVendor\Plugin\HealthChecker\YourPlugin\Checks\DatabaseSyncCheck();
            $check->setDatabase($this->getDatabase());
            $event->addResult($check);
        }
    }
}
```

### Language Strings

Add language keys for your configuration:

**File**: `language/en-GB/plg_healthchecker_yourplugin.ini`

```ini
; Configuration fieldset
PLG_HEALTHCHECKER_YOURPLUGIN_FIELDSET_CHECKS_LABEL="Health Checks Configuration"
PLG_HEALTHCHECKER_YOURPLUGIN_FIELDSET_CHECKS_DESC="Enable or disable individual health checks."

; Category headers (optional)
PLG_HEALTHCHECKER_YOURPLUGIN_FIELDSET_YOURPLUGIN_LABEL="Your Plugin Checks"
PLG_HEALTHCHECKER_YOURPLUGIN_FIELDSET_YOURPLUGIN_DESC="Health checks for Your Plugin integration."

; Check titles (used in both config and results)
PLG_HEALTHCHECKER_YOURPLUGIN_CHECK_YOURPLUGIN_API_CONNECTION_TITLE="API Connection Status"
PLG_HEALTHCHECKER_YOURPLUGIN_CHECK_YOURPLUGIN_DATABASE_SYNC_TITLE="Database Synchronization"
```

### Organizing Multiple Categories

If your plugin provides checks in multiple categories, organize them with headers:

```xml
<config>
    <fields name="params">
        <fieldset name="checks"
            label="PLG_HEALTHCHECKER_YOURPLUGIN_FIELDSET_CHECKS_LABEL">

            <!-- System Category -->
            <field name="header_system" type="note"
                label="System & Hosting Checks"
                class="alert alert-info" />

            <field name="check_yourplugin_server_config" type="radio"
                label="PLG_HEALTHCHECKER_YOURPLUGIN_CHECK_YOURPLUGIN_SERVER_CONFIG_TITLE"
                class="btn-group btn-group-yesno"
                default="1">
                <option value="1">JENABLED</option>
                <option value="0">JDISABLED</option>
            </field>

            <!-- Security Category -->
            <field name="header_security" type="note"
                label="Security Checks"
                class="alert alert-info" />

            <field name="check_yourplugin_api_security" type="radio"
                label="PLG_HEALTHCHECKER_YOURPLUGIN_CHECK_YOURPLUGIN_API_SECURITY_TITLE"
                class="btn-group btn-group-yesno"
                default="1">
                <option value="1">JENABLED</option>
                <option value="0">JDISABLED</option>
            </field>

        </fieldset>
    </fields>
</config>
```

### Default Value

The `default="1"` attribute sets the check to **enabled by default**.

- `default="1"` - Check enabled on first install
- `default="0"` - Check disabled on first install

**Recommendation**: Keep all checks enabled by default (`default="1"`) to ensure users see all available checks.

### User Experience

After adding configuration:

1. Users navigate to **Extensions → Plugins**
2. Search for your plugin name
3. Click to edit
4. See organized list of all your checks
5. Toggle individual checks on/off
6. Save settings

The next health check run will respect these settings - disabled checks won't execute or appear in results.

## Testing Your Check

1. **Install your plugin**
2. **Navigate to**: Components → Health Checker
3. **Click**: Run Health Check
4. **Verify**:
   - Your check appears in correct category
   - Title displays (not language key)
   - Description is clear and actionable
   - Status is appropriate
   - Provider badge shows your attribution

5. **Test Configuration**:
   - Go to Extensions → Plugins → Your Plugin
   - Disable one of your checks
   - Run Health Check again
   - Verify disabled check doesn't appear

## Next Steps

- [Custom Categories](./custom-categories.md) - Create custom categories
- [Provider Metadata](./provider-metadata.md) - Add branding
- [API Reference](./api-reference.md) - Complete API documentation
- [Examples](./examples.md) - More code examples
