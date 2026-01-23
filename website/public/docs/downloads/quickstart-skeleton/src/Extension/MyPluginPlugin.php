<?php

declare(strict_types=1);

/**
 * @package     Joomla.Plugin
 * @subpackage  HealthChecker.MyPlugin
 *
 * @copyright   (C) 2026 Your Company
 * @license     GNU General Public License version 2 or later
 */

namespace YourCompany\Plugin\HealthChecker\MyPlugin\Extension;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;
use MySitesGuru\HealthChecker\Component\Administrator\Category\HealthCategory;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectCategoriesEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectChecksEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectProvidersEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\HealthCheckerEvents;
use MySitesGuru\HealthChecker\Component\Administrator\Provider\ProviderMetadata;
use YourCompany\Plugin\HealthChecker\MyPlugin\Checks\MyCustomCheck;

\defined('_JEXEC') || die;

/**
 * My Custom Health Checker Plugin
 *
 * This plugin provides custom health checks for [your purpose].
 *
 * @since 1.0.0
 */
final class MyPluginPlugin extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;

    /**
     * Load plugin language files automatically.
     *
     * @var bool
     * @since 1.0.0
     */
    protected $autoloadLanguage = true;

    /**
     * Returns an array of events this subscriber listens to.
     *
     * @return array<string, string>
     * @since  1.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            HealthCheckerEvents::COLLECT_PROVIDERS->value => HealthCheckerEvents::COLLECT_PROVIDERS->getHandlerMethod(),
            HealthCheckerEvents::COLLECT_CATEGORIES->value => HealthCheckerEvents::COLLECT_CATEGORIES->getHandlerMethod(),
            HealthCheckerEvents::COLLECT_CHECKS->value => HealthCheckerEvents::COLLECT_CHECKS->getHandlerMethod(),
        ];
    }

    /**
     * Register provider metadata for branding.
     *
     * @param   CollectProvidersEvent  $event  The event to collect providers
     *
     * @return  void
     * @since   1.0.0
     */
    public function onHealthCheckerCollectProviders(CollectProvidersEvent $event): void
    {
        $event->addResult(
            new ProviderMetadata(
                slug: 'myplugin',
                name: 'My Plugin',
                description: 'Custom health checks for my service',
                url: 'https://example.com',
                icon: 'fa-puzzle-piece',
                logoUrl: null, // Optional: URL to your logo
                version: '1.0.0'
            )
        );
    }

    /**
     * Register custom categories (optional).
     *
     * @param   CollectCategoriesEvent  $event  The event to collect categories
     *
     * @return  void
     * @since   1.0.0
     */
    public function onHealthCheckerCollectCategories(CollectCategoriesEvent $event): void
    {
        // Optional: Register a custom category
        $event->addResult(
            new HealthCategory(
                slug: 'mycategory',
                label: 'PLG_HEALTHCHECKER_MYPLUGIN_CATEGORY_MYCATEGORY',
                icon: 'fa-cog',
                sortOrder: 100
            )
        );
    }

    /**
     * Register health checks.
     *
     * @param   CollectChecksEvent  $event  The event to collect checks
     *
     * @return  void
     * @since   1.0.0
     */
    public function onHealthCheckerCollectChecks(CollectChecksEvent $event): void
    {
        // Register your check instances
        $check = new MyCustomCheck();
        $check->setDatabase($this->getDatabase());
        $event->addResult($check);

        // You can register multiple checks here
    }
}
