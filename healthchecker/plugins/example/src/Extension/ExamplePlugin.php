<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace MySitesGuru\HealthChecker\Plugin\Example\Extension;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;
use MySitesGuru\HealthChecker\Component\Administrator\Category\HealthCategory;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectCategoriesEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectChecksEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectProvidersEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\HealthCheckerEvents;
use MySitesGuru\HealthChecker\Component\Administrator\Provider\ProviderMetadata;
use MySitesGuru\HealthChecker\Plugin\Example\Checks\CustomConfigCheck;
use MySitesGuru\HealthChecker\Plugin\Example\Checks\ThirdPartyServiceCheck;

\defined('_JEXEC') || die;

/**
 * Example Health Checker Plugin
 *
 * TEMPLATE FOR THIRD-PARTY DEVELOPERS
 *
 * This plugin demonstrates how third-party developers can extend
 * Health Checker with their own health checks, categories, and provider metadata.
 *
 * KEY CONCEPTS DEMONSTRATED:
 * - Event subscriber pattern for auto-discovery
 * - Registering custom categories
 * - Registering health checks
 * - Registering provider metadata
 * - Database injection for checks that need it
 *
 * DEVELOPMENT WORKFLOW:
 * 1. Copy this plugin as a template
 * 2. Update namespace and class names
 * 3. Register your provider metadata (onCollectProviders)
 * 4. Optionally register custom categories (onCollectCategories)
 * 5. Create your check classes (extend AbstractHealthCheck)
 * 6. Register your checks (onCollectChecks)
 * 7. Add language keys for titles and category labels
 *
 * AUTO-DISCOVERY:
 * Health Checker uses Symfony Event Dispatcher for automatic discovery.
 * When you implement SubscriberInterface and return event mappings from
 * getSubscribedEvents(), your plugin is automatically called during check collection.
 * No manual registration in component configuration is needed.
 *
 * @since  1.0.0
 */
final class ExamplePlugin extends CMSPlugin implements SubscriberInterface
{
    /**
     * Database access trait providing getDatabase() and setDatabase() methods.
     *
     * This is injected via the service provider (services/provider.php).
     * Only needed if your health checks perform database queries.
     *
     * @since  1.0.0
     */
    use DatabaseAwareTrait;

    /**
     * Enable automatic language file loading.
     *
     * When true, Joomla automatically loads:
     * - language/en-GB/plg_healthchecker_example.ini
     * - language/en-GB/plg_healthchecker_example.sys.ini
     *
     * @since  1.0.0
     */
    protected $autoloadLanguage = true;

    /**
     * Returns an array of events this plugin subscribes to.
     *
     * This is the core of the auto-discovery pattern. When Health Checker
     * dispatches these events, your plugin's methods are automatically called.
     *
     * EVENT EXECUTION ORDER:
     * 1. onHealthCheckerCollectProviders - Register your plugin identity
     * 2. onHealthCheckerCollectCategories - Register custom categories
     * 3. onHealthCheckerCollectChecks - Register your health checks
     *
     * @return array<string, string> Map of event names to handler method names
     *
     * @since  1.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            HealthCheckerEvents::COLLECT_CATEGORIES->value => HealthCheckerEvents::COLLECT_CATEGORIES->getHandlerMethod(),
            HealthCheckerEvents::COLLECT_CHECKS->value => HealthCheckerEvents::COLLECT_CHECKS->getHandlerMethod(),
            HealthCheckerEvents::COLLECT_PROVIDERS->value => HealthCheckerEvents::COLLECT_PROVIDERS->getHandlerMethod(),
        ];
    }

    /**
     * Event handler: Register custom categories for organizing checks.
     *
     * Categories group related health checks in the UI. You can:
     * - Create your own categories for your plugin's checks
     * - Use existing core categories: 'system', 'database', 'security',
     *   'users', 'extensions', 'performance', 'seo', 'content'
     *
     * CUSTOM CATEGORY REQUIREMENTS:
     * - Unique slug (lowercase, underscores allowed)
     * - Language key for the label (add to your .ini file)
     * - FontAwesome icon class (e.g., 'fa-plug', 'fa-shield')
     * - Sort order (10-based increments, higher = later in list)
     *
     * DEVELOPER NOTES:
     * - Categories are optional - you can use existing categories only
     * - Custom categories appear in the UI alongside core categories
     * - Category language keys should follow pattern: COM_HEALTHCHECKER_CATEGORY_{SLUG_UPPERCASE}
     *
     * @param   CollectCategoriesEvent  $collectCategoriesEvent  Event object to add categories to
     *
     * @since   1.0.0
     */
    public function onCollectCategories(CollectCategoriesEvent $collectCategoriesEvent): void
    {
        // Example: Register a custom "Third-Party" category
        // This demonstrates creating a new category for grouping plugin-specific checks
        $collectCategoriesEvent->addResult(new HealthCategory(
            slug: 'thirdparty',                              // Unique identifier (lowercase)
            label: 'COM_HEALTHCHECKER_CATEGORY_THIRDPARTY',  // Language key
            icon: 'fa-plug',                                  // FontAwesome 6 icon
            sortOrder: 90,                                    // Appears after Content Quality (80)
        ));
    }

    /**
     * Event handler: Register health checks provided by this plugin.
     *
     * This is where you instantiate your health check classes and add them
     * to the event. The Health Checker framework will then execute them
     * when the user runs a health check.
     *
     * IMPORTANT PATTERNS:
     * - Create new instances of your check classes
     * - Inject database if your checks need it (via setDatabase())
     * - Add each check to the event using addResult()
     * - Checks can use existing or custom categories
     *
     * DEPENDENCY INJECTION:
     * - Database: $check->setDatabase($this->getDatabase())
     * - Application: Available via $this->getApplication()
     * - Custom dependencies: Inject via constructor or setters
     *
     * @param   CollectChecksEvent  $collectChecksEvent  Event object to add checks to
     *
     * @since   1.0.0
     */
    public function onCollectChecks(CollectChecksEvent $collectChecksEvent): void
    {
        // Check 1: Custom configuration check (uses existing 'extensions' category)
        // This demonstrates a database-driven check using a core category
        $customConfigCheck = new CustomConfigCheck();
        $customConfigCheck->setDatabase($this->getDatabase());

        $collectChecksEvent->addResult($customConfigCheck);

        // Check 2: Third-party service check (uses our custom 'thirdparty' category)
        // This demonstrates a standalone check (no database) using a custom category
        $thirdPartyServiceCheck = new ThirdPartyServiceCheck();
        // Note: No database injection needed for this check
        $collectChecksEvent->addResult($thirdPartyServiceCheck);
    }

    /**
     * Event handler: Register provider metadata for this plugin.
     *
     * Provider metadata identifies your plugin in the Health Checker UI and
     * provides attribution for your health checks. This information appears
     * in the check results and helps users understand where checks come from.
     *
     * PROVIDER REQUIREMENTS:
     * - slug: Must match what your checks return from getProvider()
     *         Format: lowercase, single word or underscores (e.g., 'example', 'akeeba_backup')
     * - name: Human-readable name shown in UI
     * - description: Brief description of what your checks monitor
     * - url: (Optional) Link to your product/documentation
     * - icon: (Optional) FontAwesome icon class
     * - version: (Optional) Your plugin's version number
     *
     * DEVELOPER NOTES:
     * - Only register ONE provider per plugin
     * - The slug is critical - it must match your checks' getProvider() return value
     * - Provider info appears in check result tooltips and filtered views
     *
     * @param   CollectProvidersEvent  $collectProvidersEvent  Event object to add providers to
     *
     * @since   1.0.0
     */
    public function onCollectProviders(CollectProvidersEvent $collectProvidersEvent): void
    {
        $collectProvidersEvent->addResult(new ProviderMetadata(
            slug: 'example',                                                         // Must match checks' getProvider()
            name: 'Example Provider',                                                // Display name
            description: 'Example health checks demonstrating the SDK',             // Brief description
            url: 'https://github.com/mySitesGuru/health-checker-for-joomla',        // Link to docs/product
            icon: 'fa-flask',                                                        // FontAwesome icon
            version: '1.0.0',                                                        // Plugin version
        ));
    }
}
