---
url: /docs/developers.md
---
# Developer Guide

This guide explains how third-party developers can extend Health Checker for Joomla with custom health checks.

## Overview

Health Checker uses Joomla's event system to allow plugins to register:

* **Health Checks** - Individual diagnostic tests
* **Categories** - Groupings for organizing checks
* **Providers** - Metadata about who provides the checks

## Why Extend Health Checker?

Adding health checks to Health Checker allows you to:

* **Monitor your extension's configuration** - Verify required settings are configured
* **Check integration status** - Ensure connections to external services are working
* **Validate resource availability** - Confirm required files, directories, or database tables exist
* **Provide value to your users** - Help them maintain optimal configuration

## How It Works

Your plugin subscribes to events dispatched by Health Checker:

1. **Health Checker runs** - User clicks "Run Health Check"
2. **Events dispatched** - Health Checker fires collection events
3. **Your plugin responds** - Adds its checks, categories, and provider info
4. **Checks execute** - All checks run in parallel via AJAX
5. **Results displayed** - Your checks appear with provider attribution

## Quick Example

Here's a minimal health check plugin:

```php
// Plugin class (src/Extension/MyPlugin.php)
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
        ));
    }
}
```

```php
// Check class (src/Checks/MyCustomCheck.php)
final class MyCustomCheck extends AbstractHealthCheck
{
    public function getSlug(): string
    {
        return 'myplugin.my_custom_check';
    }

    public function getCategory(): string
    {
        return 'extensions'; // Use existing category
    }

    public function getProvider(): string
    {
        return 'myplugin'; // Must match ProviderMetadata slug
    }

    protected function performCheck(): HealthCheckResult
    {
        // Your check logic here
        if ($this->isEverythingOk()) {
            return $this->good('Everything is working correctly.');
        }

        return $this->warning('Something needs attention.');
    }
}
```

## What You'll Learn

This developer guide covers:

* [Quick Start](/developers/quick-start) - Complete tutorial to create your first health check plugin
* API reference - See the Quick Start guide for complete API documentation
* Best practices - Code conventions and guidelines included in Quick Start
* Working examples - Example plugin included with Health Checker installation

## Available Events

Health Checker dispatches three events:

| Event | Purpose | When to Use |
|-------|---------|-------------|
| `onHealthCheckerCollectChecks` | Register health check instances | Always - this is how you add checks |
| `onHealthCheckerCollectCategories` | Register custom categories | Only if creating new categories |
| `onHealthCheckerCollectProviders` | Register provider metadata | Always - provides attribution |

## Provider Attribution

When you register as a provider, your checks are visually attributed to you:

* **Badge on each check** - Shows your provider name
* **Tooltip on hover** - Displays description, version, and link
* **Footer credits** - Lists all active providers with logos

This helps users understand which extensions are contributing checks and provides visibility for your product.

## Built-in Categories

You can add checks to these existing categories:

| Slug | Label | Icon |
|------|-------|------|
| `system` | System & Hosting | `fa-server` |
| `database` | Database | `fa-database` |
| `security` | Security | `fa-shield-halved` |
| `users` | Users | `fa-users` |
| `extensions` | Extensions | `fa-puzzle-piece` |
| `performance` | Performance | `fa-gauge-high` |
| `seo` | SEO | `fa-magnifying-glass` |
| `content` | Content Quality | `fa-file-lines` |

Or create your own! See the [Quick Start](/developers/quick-start) guide for examples.

## Next Steps

Ready to build your first health check plugin? Continue to [Quick Start](/developers/quick-start) for a step-by-step tutorial.

For complete working examples, see:

* **Example Plugin** - `plugins/healthchecker/example/` (included with Health Checker)
* **Core Plugin** - `plugins/healthchecker/core/` (129 production examples)
* **Akeeba Integrations** - `plugins/healthchecker/akeebabackup/` and `akeebaadmintools/`
