# Quick Start

This guide will walk you through creating your first Health Checker plugin with a custom health check.

## Quick Start Skeleton

**Want to skip the manual setup?** Download our ready-to-use plugin skeleton:

📦 **[Download Quick Start Skeleton](/downloads/quickstart-skeleton.zip)** (Complete working example with all files)

The skeleton includes:
- Complete plugin structure
- Working example check
- Language files
- Full documentation
- Ready to customize and install

Just extract, customize the namespace and check logic, ZIP it up, and install in Joomla!

---

## Prerequisites

- Joomla 5.0+ installed
- Health Checker for Joomla installed and working
- Basic understanding of Joomla plugin development
- PHP 8.1+ knowledge

## Manual Setup Instructions

If you prefer to build from scratch or want to understand every step, follow this guide.

## Step 1: Create Plugin Structure

Create a new plugin in the `healthchecker` group:

```
plugins/healthchecker/myplugin/
├── myplugin.xml
├── services/
│   └── provider.php
├── src/
│   ├── Extension/
│   │   └── MyPlugin.php
│   └── Checks/
│       └── MyCustomCheck.php
└── language/
    └── en-GB/
        └── plg_healthchecker_myplugin.ini
```

## Step 2: Plugin Manifest (myplugin.xml)

```xml
<?xml version="1.0" encoding="utf-8"?>
<extension type="plugin" group="healthchecker" method="upgrade">
    <name>plg_healthchecker_myplugin</name>
    <author>Your Name</author>
    <version>1.0.0</version>
    <description>PLG_HEALTHCHECKER_MYPLUGIN_XML_DESCRIPTION</description>
    <namespace path="src">MySitesGuru\HealthChecker\Plugin\MyPlugin</namespace>
    <files>
        <folder plugin="myplugin">services</folder>
        <folder>src</folder>
        <folder>language</folder>
    </files>
</extension>
```

## Step 3: Service Provider (services/provider.php)

```php
<?php

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use MySitesGuru\HealthChecker\Plugin\MyPlugin\Extension\MyPlugin;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $dispatcher = $container->get(DispatcherInterface::class);
                $plugin = new MyPlugin(
                    $dispatcher,
                    (array) PluginHelper::getPlugin('healthchecker', 'myplugin')
                );
                $plugin->setApplication(Factory::getApplication());
                $plugin->setDatabase($container->get(DatabaseInterface::class));

                return $plugin;
            }
        );
    }
};
```

## Step 4: Plugin Class (src/Extension/MyPlugin.php)

```php
<?php

namespace MySitesGuru\HealthChecker\Plugin\MyPlugin\Extension;

use Joomla\CMS\Plugin\CMSPlugin;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectChecksEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectProvidersEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Provider\ProviderMetadata;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;
use MySitesGuru\HealthChecker\Plugin\MyPlugin\Checks\MyCustomCheck;

defined('_JEXEC') or die;

final class MyPlugin extends CMSPlugin implements SubscriberInterface
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
        $check = new MyCustomCheck();
        $check->setDatabase($this->getDatabase());
        $event->addResult($check);
    }

    public function onCollectProviders(CollectProvidersEvent $event): void
    {
        $event->addResult(new ProviderMetadata(
            slug: 'myplugin',
            name: 'My Plugin',
            description: 'Custom health checks for my extension',
            url: 'https://example.com',
            version: '1.0.0',
        ));
    }
}
```

## Step 5: Health Check Class (src/Checks/MyCustomCheck.php)

```php
<?php

namespace MySitesGuru\HealthChecker\Plugin\MyPlugin\Checks;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

defined('_JEXEC') or die;

/**
 * My Custom Health Check
 *
 * This check monitors [what it monitors] to ensure [desired outcome].
 *
 * WHY THIS CHECK IS IMPORTANT:
 * [Explain why monitoring this aspect matters for Joomla sites.
 * What could go wrong if this isn't checked? What benefits does
 * this check provide to site administrators?]
 *
 * RESULT MEANINGS:
 *
 * GOOD: [Describe what conditions result in a good status.
 *       What does this mean for the administrator?]
 *
 * WARNING: [Describe what triggers a warning. What action should
 *          the administrator take to resolve it?]
 *
 * CRITICAL: [Describe what triggers critical status. What immediate
 *           action is required? Or state "This check does not return
 *           critical status." if not applicable.]
 */
final class MyCustomCheck extends AbstractHealthCheck
{
    public function getSlug(): string
    {
        return 'myplugin.my_custom_check';
    }

    public function getCategory(): string
    {
        return 'extensions'; // Use an existing category or register your own
    }

    public function getProvider(): string
    {
        return 'myplugin'; // Must match your ProviderMetadata slug
    }

    protected function performCheck(): HealthCheckResult
    {
        // Your check logic here
        $someCondition = $this->checkSomething();

        if ($someCondition === false) {
            return $this->critical('Something is critically wrong!');
        }

        if ($someCondition === 'warning') {
            return $this->warning('Something needs attention.');
        }

        return $this->good('Everything is working correctly.');
    }

    private function checkSomething(): mixed
    {
        // Use $this->getDatabase() for database queries
        $db = $this->getDatabase();

        // Example database query
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__my_table'));

        return $db->setQuery($query)->loadResult();
    }
}
```

## Step 6: Language File (language/en-GB/plg_healthchecker_myplugin.ini)

```ini
PLG_HEALTHCHECKER_MYPLUGIN="Health Checker - My Plugin"
PLG_HEALTHCHECKER_MYPLUGIN_XML_DESCRIPTION="Custom health checks for My Plugin"

; Check titles use this naming convention:
; COM_HEALTHCHECKER_CHECK_{SLUG_UPPERCASE}_TITLE
COM_HEALTHCHECKER_CHECK_MYPLUGIN_MY_CUSTOM_CHECK_TITLE="My Custom Check"
```

## Step 7: Install and Test

### Package Your Plugin

1. Create a ZIP file containing all plugin files
2. Name it `plg_healthchecker_myplugin.zip`

### Install

1. Go to **System → Install → Extensions**
2. Upload your plugin ZIP file
3. Install it

### Enable

1. Go to **System → Manage → Plugins**
2. Search for "My Plugin"
3. Click to enable it

### Test

1. Go to **Components → Health Checker**
2. Click **Run Health Check**
3. Look for your check in the **Extensions** category
4. Verify it shows your provider badge

## What You Just Created

You now have:

✅ A working Health Checker plugin
✅ A custom health check that runs automatically
✅ Provider attribution (your name/branding appears on the check)
✅ Proper error handling (via AbstractHealthCheck)
✅ Auto-generated title from language key

## Next Steps

Now that you have a basic plugin working, you can:

- **Add custom categories** - Create your own categories (see Custom Categories section above)
- **Add more checks** - Follow the same pattern for additional checks
- **Enhance provider metadata** - Add logos, icons, and detailed branding
- **Study the examples** - Examine `plugins/healthchecker/core/` for production examples

## Common Issues

### Check Doesn't Appear

**Cause**: Plugin not enabled or event not subscribed correctly.

**Solution**:
- Verify plugin is enabled in Plugin Manager
- Check `getSubscribedEvents()` returns correct event names
- Clear Joomla cache

### Title Shows as Language Key

**Cause**: Language file not loaded or key mismatch.

**Solution**:
- Verify language file is in correct location
- Ensure INI key matches the pattern: `COM_HEALTHCHECKER_CHECK_{SLUG_UPPERCASE}_TITLE`
- For slug `myplugin.my_custom_check`, the key should be `COM_HEALTHCHECKER_CHECK_MYPLUGIN_MY_CUSTOM_CHECK_TITLE`

### Provider Badge Not Showing

**Cause**: Provider slug mismatch or provider not registered.

**Solution**:
- Verify `getProvider()` in check returns same slug as `ProviderMetadata`
- Ensure `onCollectProviders` is in `getSubscribedEvents()`
- Check for typos in slug (case-sensitive)

## Example Plugin

For a complete working example, see the example plugin included with Health Checker:

📂 `plugins/healthchecker/example/`

This demonstrates:
- Multiple checks
- Custom category
- Adding checks to existing categories
- Provider metadata with logo
- Proper file headers and documentation
