---
description: "Working code examples for Health Checker plugins. Complete plugin templates and common health check patterns."
---

# Examples

Complete working examples of Health Checker plugins.

## Minimal Plugin

The simplest possible health check plugin:

**Plugin structure**:
```
plugins/healthchecker/minimal/
├── services/
│   └── provider.php
├── src/
│   ├── Extension/
│   │   └── Minimal.php
│   └── Checks/
│       └── SimpleCheck.php
├── language/
│   └── en-GB/
│       └── plg_healthchecker_minimal.ini
└── minimal.xml
```

**minimal.xml**:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<extension type="plugin" group="healthchecker" method="upgrade">
    <name>PLG_HEALTHCHECKER_MINIMAL</name>
    <version>1.0.0</version>
    <description>PLG_HEALTHCHECKER_MINIMAL_DESCRIPTION</description>
    <namespace path="src">MyCompany\Plugin\HealthChecker\Minimal</namespace>
    <files>
        <folder>services</folder>
        <folder>src</folder>
        <folder>language</folder>
    </files>
</extension>
```

**services/provider.php**:
```php
<?php
defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use MyCompany\Plugin\HealthChecker\Minimal\Extension\Minimal;

return new class implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $plugin = new Minimal(
                    $container->get(DispatcherInterface::class),
                    (array) PluginHelper::getPlugin('healthchecker', 'minimal')
                );
                $plugin->setApplication(Factory::getApplication());
                $plugin->setDatabase(Factory::getContainer()->get('DatabaseDriver'));

                return $plugin;
            }
        );
    }
};
```

**src/Extension/Minimal.php**:
```php
<?php
namespace MyCompany\Plugin\HealthChecker\Minimal\Extension;

use Joomla\CMS\Plugin\CMSPlugin;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectChecksEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectProvidersEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Provider\ProviderMetadata;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;
use MyCompany\Plugin\HealthChecker\Minimal\Checks\SimpleCheck;

defined('_JEXEC') or die;

final class Minimal extends CMSPlugin implements SubscriberInterface
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
        $check = new SimpleCheck();
        $check->setDatabase($this->getDatabase());
        $event->addResult($check);
    }

    public function onCollectProviders(CollectProvidersEvent $event): void
    {
        $event->addResult(new ProviderMetadata(
            slug: 'minimal',
            name: 'Minimal Example Plugin'
        ));
    }
}
```

**src/Checks/SimpleCheck.php**:
```php
<?php
namespace MyCompany\Plugin\HealthChecker\Minimal\Checks;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;

defined('_JEXEC') or die;

final class SimpleCheck extends AbstractHealthCheck
{
    public function getSlug(): string
    {
        return 'minimal.simple_check';
    }

    public function getCategory(): string
    {
        return 'extensions';
    }

    public function getProvider(): string
    {
        return 'minimal';
    }

    /**
     * Optional: Link to documentation (displays "Docs" button)
     */
    public function getDocsUrl(): ?string
    {
        return 'https://example.com/docs/simple-check';
    }

    /**
     * Optional: Link to settings page (displays "Explore" button)
     * Only show when there's an issue to fix
     */
    public function getActionUrl(?HealthStatus $status = null): ?string
    {
        if ($status === HealthStatus::Good) {
            return null;
        }
        return '/administrator/index.php?option=com_minimal&view=settings';
    }

    protected function performCheck(): HealthCheckResult
    {
        // Your check logic here
        $isOk = true;

        return $isOk
            ? $this->good('Everything is working correctly.')
            : $this->warning('Something needs attention.');
    }
}
```

**language/en-GB/plg_healthchecker_minimal.ini**:
```ini
PLG_HEALTHCHECKER_MINIMAL="Health Checker - Minimal Example"
PLG_HEALTHCHECKER_MINIMAL_DESCRIPTION="Minimal example health check plugin"
PLG_HEALTHCHECKER_MINIMAL_CHECK_MINIMAL_SIMPLE_CHECK_TITLE="Simple Check"
```

## Included Examples

### Example Plugin

Health Checker ships with a complete example plugin at:
```
plugins/healthchecker/example/
```

This demonstrates:
- Custom category creation
- Provider metadata with logo
- Multiple health checks
- Database usage
- Configuration checks

### Core Plugin

The core plugin at `plugins/healthchecker/core/` contains 129 production-ready checks showing:
- All check patterns
- Best practices
- Error handling
- Performance optimization

### Akeeba Integrations

See `plugins/healthchecker/akeebabackup/` and `akeebaadmintools/` for real-world examples of integration plugins.

## Common Patterns

For more code examples and patterns, see:

- [Creating Checks](./creating-checks.md) - Check implementation patterns
- [Custom Categories](./custom-categories.md) - Category creation
- [Provider Metadata](./provider-metadata.md) - Branding examples
- [API Reference](./api-reference.md) - Complete API with examples

## Testing Your Plugin

1. Create plugin files
2. Install via Extensions → Install
3. Enable at Extensions → Plugins
4. Navigate to Components → Health Checker
5. Click "Run Health Check"
6. Verify your checks appear

## Next Steps

- [Quick Start](./quick-start.md) - Step-by-step tutorial
- [Best Practices](./best-practices.md) - Code guidelines
- [API Reference](./api-reference.md) - Complete API docs
