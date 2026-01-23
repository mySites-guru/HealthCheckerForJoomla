# Provider Metadata

Learn how to register provider metadata for branding and attribution of your health checks.

## Overview

Provider metadata identifies who provides health checks and displays attribution in the UI. This gives visibility to your extension and helps users understand where checks come from.

## Provider Attribution Display

Your provider metadata appears in:

1. **Check badges** - Small badge on each check showing provider name
2. **Hover tooltips** - Detailed info when hovering over badge
3. **Provider list** - Footer showing all active providers
4. **Logo display** - Optional logo shown with your checks

![Provider Metadata Badges](/images/provider-metadata-badges.png)
*Example of provider badges displayed on individual health checks. Each check shows the provider name in a badge (e.g., "Akeeba Backup (Unofficial)").*

![Provider Metadata Footer](/images/provider-metadata-footer.png)
*Provider metadata displayed in the Third-Party Health Check Providers section at the bottom of the health check report, showing logos, names, descriptions, and links.*

## Registering Provider Metadata

Provider metadata is registered via the `onHealthCheckerCollectProviders` event:

```php
use MySitesGuru\HealthChecker\Component\Administrator\Provider\ProviderMetadata;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectProvidersEvent;

public static function getSubscribedEvents(): array
{
    return [
        'onHealthCheckerCollectProviders' => 'onCollectProviders',
        // ... other events
    ];
}

public function onCollectProviders(CollectProvidersEvent $event): void
{
    $event->addResult(new ProviderMetadata(
        slug: 'yourplugin',
        name: 'Your Plugin Name',
        description: 'Brief description of your extension',
        url: 'https://yourwebsite.com',
        icon: 'fa-puzzle-piece',
        logoUrl: 'plugins/healthchecker/yourplugin/media/logo.svg',
        version: '1.0.0'
    ));
}
```

## Provider Properties

### slug (required)
Unique identifier matching your check provider.

**Rules**:
- Lowercase only
- Underscores for spaces
- Match this in your checks' `getProvider()` method

**Example**: `yourplugin`, `akeeba_backup`, `my_extension`

### name (required)
Display name of your extension.

**Examples**:
- "Akeeba Backup"
- "Your Extension Pro"
- "SEO Ultimate"

### description (optional)
Brief description of what your checks do.

**Keep it short**: 1-2 sentences

**Example**: "Monitors backup status and configuration for Akeeba Backup"

### url (optional)
Link to your extension's website or JED listing.

**Examples**:
- Extension website: `https://yourextension.com`
- JED listing: `https://extensions.joomla.org/extension/yourextension`
- Documentation: `https://docs.yourextension.com`

**Opens in**: New tab when clicked

### icon (optional)
FontAwesome 6 icon class.

**Format**: `fa-{icon-name}`

**Default**: `fa-puzzle-piece` if not specified

**Examples**:
- `fa-shield-halved` - Security extensions
- `fa-cloud` - Cloud services
- `fa-envelope` - Email extensions
- `fa-chart-line` - Analytics

### logoUrl (optional)
URL or path to your logo image.

**Recommended format**:
- SVG (vector, scales perfectly)
- PNG (if no SVG available)
- Max display size: 120x40px
- Transparent background recommended

**Path examples**:
```php
// Relative to site root
logoUrl: 'plugins/healthchecker/yourplugin/media/logo.svg'

// Absolute URL
logoUrl: 'https://yoursite.com/images/logo.svg'

// Media folder
logoUrl: 'media/plg_healthchecker_yourplugin/logo.svg'
```

### version (optional)
Your extension version number.

**Format**: SemVer recommended (`1.0.0`, `2.3.1`, etc.)

**Displayed**: In tooltip when hovering over provider badge

## Complete Example

```php
<?php
namespace YourVendor\Plugin\HealthChecker\YourPlugin\Extension;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectProvidersEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Provider\ProviderMetadata;
use Joomla\Event\SubscriberInterface;

final class YourPlugin extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onHealthCheckerCollectProviders' => 'onCollectProviders',
        ];
    }

    public function onCollectProviders(CollectProvidersEvent $event): void
    {
        // Get plugin version from manifest
        $plugin = PluginHelper::getPlugin('healthchecker', 'yourplugin');
        $version = $plugin->manifest_cache ? json_decode($plugin->manifest_cache)->version : '1.0.0';

        $event->addResult(new ProviderMetadata(
            slug: 'yourplugin',
            name: 'Your Awesome Extension',
            description: 'Monitors configuration and connectivity for Your Awesome Extension',
            url: 'https://yourextension.com',
            icon: 'fa-rocket',
            logoUrl: 'plugins/healthchecker/yourplugin/media/logo.svg',
            version: $version
        ));
    }
}
```

## Matching Provider in Checks

Your checks must return the same provider slug:

```php
final class YourCheck extends AbstractHealthCheck
{
    public function getProvider(): string
    {
        return 'yourplugin'; // Must match ProviderMetadata slug
    }

    // ... rest of implementation
}
```

## Creating a Logo

### Logo Requirements

- **Format**: SVG (preferred) or PNG
- **Size**: 120x40px max display size
- **Background**: Transparent recommended
- **Colors**: Works in light and dark themes

### Logo Location

Place in your plugin's media folder:

```
plugins/healthchecker/yourplugin/
├── media/
│   ├── logo.svg          ← Your logo here
│   └── css/
│       └── styles.css
├── src/
│   └── Extension/
│       └── YourPlugin.php
└── yourplugin.xml
```

### SVG Logo Example

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 40">
    <text x="5" y="25" font-family="Arial" font-size="18" fill="#0066cc">
        Your Extension
    </text>
</svg>
```

## Best Practices

### Provider Information
- **Be concise**: Short, clear descriptions
- **Be helpful**: Include useful links (docs, support)
- **Be accurate**: Keep version numbers updated
- **Be branded**: Use your actual extension name and logo

### Slug Naming
```php
// ✅ Good
slug: 'yourextension'
slug: 'company_product'

// ❌ Avoid
slug: 'plugin123'
slug: 'my-cool-extension' // Use underscores, not hyphens
```

### URL Selection
```php
// ✅ Best options
url: 'https://docs.yourextension.com'     // Documentation
url: 'https://yourextension.com'          // Main site
url: 'https://extensions.joomla.org/...'  // JED listing

// ❌ Avoid
url: 'https://facebook.com/yourextension' // Social media
url: 'mailto:support@...'                 // Email addresses
```

### Logo Design
- Keep it simple and readable at small sizes
- Test in both light and dark admin themes
- Ensure colors have good contrast
- Don't include taglines or extra text
- Use your brand colors

## Testing Provider Display

1. **Install your plugin**
2. **Run health checks**: Components → Health Checker
3. **Verify badge**: Each check shows your provider name
4. **Test hover**: Tooltip displays description, version, link
5. **Check logo**: Logo appears if provided
6. **Test link**: Clicking URL opens your site

## Next Steps

- [Creating Checks](./creating-checks.md) - Build health checks with your provider
- [Custom Categories](./custom-categories.md) - Create branded categories
- [Examples](./examples.md) - See complete implementations
