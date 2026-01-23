---
url: /docs/public/downloads/quickstart-skeleton/README.md
---
# Health Checker Quick Start Skeleton

This is a minimal working example of a Health Checker plugin for Joomla.

## What's Included

* `myplugin.xml` - Plugin manifest
* `services/provider.php` - Dependency injection container
* `src/Extension/MyPluginPlugin.php` - Main plugin class
* `src/Checks/MyCustomCheck.php` - Example health check
* `language/en-GB/*.ini` - Language files

## Installation Steps

1. **Customize the files:**
   * Replace `YourCompany` namespace with your company name
   * Replace `MyPlugin` with your plugin name
   * Replace `myplugin` slug with your plugin slug
   * Update copyright, author, email, URL in XML manifest

2. **Implement your check logic:**
   * Edit `src/Checks/MyCustomCheck.php`
   * Replace the example logic in `performCheck()` method
   * Add your actual check conditions

3. **Add language strings:**
   * Edit `language/en-GB/plg_healthchecker_myplugin.ini`
   * Add translations for check titles

4. **Package the plugin:**
   ```bash
   cd /path/to/your/plugin
   zip -r plg_healthchecker_myplugin.zip .
   ```

5. **Install via Joomla:**
   * Go to Extensions → Install
   * Upload the ZIP file
   * Enable the plugin

## File Structure

```
plg_healthchecker_myplugin/
├── myplugin.xml                    # Plugin manifest
├── services/
│   └── provider.php                # DI container
├── src/
│   ├── Extension/
│   │   └── MyPluginPlugin.php      # Main plugin class
│   └── Checks/
│       └── MyCustomCheck.php       # Your health check
└── language/
    └── en-GB/
        ├── plg_healthchecker_myplugin.ini
        └── plg_healthchecker_myplugin.sys.ini
```

## Adding More Checks

To add additional checks:

1. Create new check file: `src/Checks/AnotherCheck.php`
2. Extend `AbstractHealthCheck`
3. Implement required methods
4. Register in `MyPluginPlugin::onHealthCheckerCollectChecks()`
5. Add language key to `.ini` file

Example:

```php
// src/Checks/AnotherCheck.php
final class AnotherCheck extends AbstractHealthCheck
{
    public function getSlug(): string
    {
        return 'myplugin.another_check';
    }

    public function getCategory(): string
    {
        return 'system';
    }

    public function getProvider(): string
    {
        return 'myplugin';
    }

    protected function performCheck(): HealthCheckResult
    {
        // Your logic here
        return $this->good('All good!');
    }
}
```

Then register it:

```php
// MyPluginPlugin.php
public function onHealthCheckerCollectChecks(CollectChecksEvent $event): void
{
    $check1 = new MyCustomCheck();
    $check1->setDatabase($this->getDatabase());
    $event->addResult($check1);

    $check2 = new AnotherCheck();
    $check2->setDatabase($this->getDatabase());
    $event->addResult($check2);
}
```

## Need Help?

* Full documentation: https://www.joomlahealthchecker.com/docs/developers/
* GitHub repository: https://github.com/mySites-guru/HealthCheckerForJoomla
* Example plugin: See `plg_healthchecker_example` in the main repository
