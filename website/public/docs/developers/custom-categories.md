---
url: /docs/developers/custom-categories.md
---
# Custom Categories

Learn how to create custom categories for organizing your health checks.

## Overview

Categories group related health checks together. While Health Checker provides 8 built-in categories, you can create your own for extension-specific checks.

## When to Create Custom Categories

Create a custom category when:

* Your checks don't fit existing categories
* You have multiple related checks
* You want branded grouping for your extension
* You're checking domain-specific features

**Example**: An e-commerce extension might create a "Commerce" category for payment gateway, inventory, and shipping checks.

## Creating a Category

Categories are registered via the `onHealthCheckerCollectCategories` event:

```php
use MySitesGuru\HealthChecker\Component\Administrator\Category\HealthCategory;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectCategoriesEvent;

public static function getSubscribedEvents(): array
{
    return [
        'onHealthCheckerCollectCategories' => 'onCollectCategories',
        // ... other events
    ];
}

public function onCollectCategories(CollectCategoriesEvent $event): void
{
    $event->addResult(new HealthCategory(
        slug: 'commerce',
        label: 'COM_YOUREXTENSION_HEALTHCHECKER_CATEGORY_COMMERCE',
        icon: 'fa-shopping-cart',
        sortOrder: 85
    ));
}
```

## Category Properties

### slug

Unique identifier for the category.

**Format**: Lowercase, underscores for spaces

**Examples**: `commerce`, `backups`, `api_integrations`

### label

Language key for category display name.

Add to your plugin's language file:

```ini
COM_YOUREXTENSION_HEALTHCHECKER_CATEGORY_COMMERCE="E-Commerce"
```

### icon

FontAwesome 6 icon class.

**Format**: `fa-{icon-name}`

**Find icons**: [FontAwesome Icon Gallery](https://fontawesome.com/icons)

**Popular choices**:

* `fa-shopping-cart` - E-commerce
* `fa-cloud` - Cloud/API services
* `fa-envelope` - Email/communications
* `fa-chart-line` - Analytics
* `fa-lock` - Security-specific
* `fa-sync` - Sync/backup

### sortOrder

Display order (lower numbers appear first).

**Built-in categories use**:

* System: 10
* Database: 20
* Security: 30
* Users: 40
* Extensions: 50
* Performance: 60
* SEO: 70
* Content: 80

**Recommendation**: Use 85+ for custom categories to appear after core categories.

## Complete Example

```php
<?php
namespace YourVendor\Plugin\HealthChecker\YourPlugin\Extension;

use Joomla\CMS\Plugin\CMSPlugin;
use MySitesGuru\HealthChecker\Component\Administrator\Category\HealthCategory;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectCategoriesEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectChecksEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectProvidersEvent;
use Joomla\Event\SubscriberInterface;

final class YourPlugin extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onHealthCheckerCollectCategories' => 'onCollectCategories',
            'onHealthCheckerCollectChecks' => 'onCollectChecks',
            'onHealthCheckerCollectProviders' => 'onCollectProviders',
        ];
    }

    public function onCollectCategories(CollectCategoriesEvent $event): void
    {
        $event->addResult(new HealthCategory(
            slug: 'yourplugin',
            label: 'PLG_HEALTHCHECKER_YOURPLUGIN_CATEGORY_YOURPLUGIN',
            icon: 'fa-puzzle-piece',
            sortOrder: 90
        ));
    }

    // ... other event handlers
}
```

**Language file** (`language/en-GB/plg_healthchecker_yourplugin.ini`):

```ini
PLG_HEALTHCHECKER_YOURPLUGIN_CATEGORY_YOURPLUGIN="Your Plugin"
```

## Using Custom Categories

After creating a category, reference it in your checks:

```php
final class YourCheck extends AbstractHealthCheck
{
    public function getCategory(): string
    {
        return 'yourplugin'; // Your custom category slug
    }

    // ... rest of check implementation
}
```

## Best Practices

### Naming

* Use singular nouns: "Commerce" not "Commerce Items"
* Keep short: 1-2 words ideal
* Be specific: "Backups" not "Other Stuff"

### Icons

* Choose relevant, recognizable icons
* Stick to solid style (default FontAwesome)
* Test icon appearance in light/dark themes

### Sort Order

* Place after core categories (85+)
* Group related custom categories together
* Consider alphabetical if multiple custom categories

## Next Steps

* [Creating Checks](./creating-checks.md) - Build checks for your category
* [Provider Metadata](./provider-metadata.md) - Add branding
* [Examples](./examples.md) - See complete plugin examples
