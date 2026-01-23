<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace MySitesGuru\HealthChecker\Plugin\MySitesGuru\Extension;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;
use MySitesGuru\HealthChecker\Component\Administrator\Category\HealthCategory;
use MySitesGuru\HealthChecker\Component\Administrator\Event\AfterToolbarBuildEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\BeforeReportDisplayEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectCategoriesEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectChecksEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectProvidersEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\HealthCheckerEvents;
use MySitesGuru\HealthChecker\Component\Administrator\Provider\ProviderMetadata;
use MySitesGuru\HealthChecker\Plugin\MySitesGuru\Checks\MySitesGuruConnectionCheck;

\defined('_JEXEC') || die;

/**
 * mySites.guru Health Checker Plugin
 *
 * This plugin integrates Health Checker for Joomla with the mySites.guru monitoring
 * service. It provides a custom category, connection status check, and branded
 * provider metadata for displaying mySites.guru integration in the Health Checker UI.
 *
 * The plugin demonstrates the Health Checker SDK's extensibility pattern:
 * - Custom category registration via CollectCategoriesEvent
 * - Health check registration via CollectChecksEvent
 * - Provider branding via CollectProvidersEvent
 *
 * Integration points:
 * - Subscribes to Health Checker event dispatcher
 * - Registers mySites.guru category (sort order 90)
 * - Provides connection status check
 * - Injects branded provider metadata
 *
 * @subpackage  HealthChecker.MySitesGuru
 * @since       1.0.0
 */
final class MySitesGuruPlugin extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;

    /**
     * Whether to automatically load the plugin language files.
     *
     * @since 1.0.0
     */
    protected $autoloadLanguage = true;

    /**
     * Returns an array of events this subscriber listens to.
     *
     * Maps Health Checker event names to corresponding plugin methods.
     * This enables automatic event discovery and registration by Joomla's
     * event dispatcher without manual subscription code.
     *
     * @return array<string, string> Event name => method name mapping
     *
     * @since 1.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            HealthCheckerEvents::COLLECT_CATEGORIES->value => HealthCheckerEvents::COLLECT_CATEGORIES->getHandlerMethod(),
            HealthCheckerEvents::COLLECT_CHECKS->value => HealthCheckerEvents::COLLECT_CHECKS->getHandlerMethod(),
            HealthCheckerEvents::COLLECT_PROVIDERS->value => HealthCheckerEvents::COLLECT_PROVIDERS->getHandlerMethod(),
            HealthCheckerEvents::BEFORE_REPORT_DISPLAY->value => HealthCheckerEvents::BEFORE_REPORT_DISPLAY->getHandlerMethod(),
            HealthCheckerEvents::AFTER_TOOLBAR_BUILD->value => HealthCheckerEvents::AFTER_TOOLBAR_BUILD->getHandlerMethod(),
        ];
    }

    /**
     * Register the mySites.guru Integration category.
     *
     * Creates a custom category for mySites.guru-specific health checks.
     * This category appears in the Health Checker UI with custom branding,
     * icon, and logo to clearly identify integration checks.
     *
     * Category configuration:
     * - Slug: 'mysitesguru'
     * - Sort order: 90 (appears near the end of category list)
     * - Icon: FontAwesome tachometer-alt
     * - Branded logo from plugin media directory
     *
     * @param CollectCategoriesEvent $collectCategoriesEvent Event object for collecting category definitions
     *
     * @since 1.0.0
     */
    public function onCollectCategories(CollectCategoriesEvent $collectCategoriesEvent): void
    {
        $collectCategoriesEvent->addResult(new HealthCategory(
            slug: 'mysitesguru',
            label: 'mySites.guru Integration',
            icon: 'fa-tachometer-alt',
            sortOrder: 90,
            logoUrl: '/media/plg_healthchecker_mysitesguru/logo.png',
        ));
    }

    /**
     * Register the mySites.guru connection check.
     *
     * Instantiates and registers the MySitesGuruConnectionCheck which verifies
     * whether the site is connected to mySites.guru monitoring service.
     *
     * The check requires database access to query the extensions table,
     * so the database instance is injected via setDatabase().
     *
     * Integration pattern:
     * 1. Create check instance
     * 2. Inject required dependencies (database)
     * 3. Add to event's result collection
     *
     * @param CollectChecksEvent $collectChecksEvent Event object for collecting health check instances
     *
     * @since 1.0.0
     */
    public function onCollectChecks(CollectChecksEvent $collectChecksEvent): void
    {
        $mySitesGuruConnectionCheck = new MySitesGuruConnectionCheck();
        $mySitesGuruConnectionCheck->setDatabase($this->getDatabase());

        $collectChecksEvent->addResult($mySitesGuruConnectionCheck);
    }

    /**
     * Register mySites.guru as a provider with branding.
     *
     * Registers mySites.guru's metadata for display in the Health Checker UI.
     * This metadata appears in:
     * - Provider attribution column in results table
     * - Provider filter dropdown
     * - Check detail modals
     *
     * Provider information includes:
     * - Unique slug identifier
     * - Display name and description
     * - Website URL for linking to documentation
     * - FontAwesome icon and logo URL for branding
     * - Version number for compatibility tracking
     *
     * @param CollectProvidersEvent $collectProvidersEvent Event object for collecting provider metadata
     *
     * @since 1.0.0
     */
    public function onCollectProviders(CollectProvidersEvent $collectProvidersEvent): void
    {
        $collectProvidersEvent->addResult(new ProviderMetadata(
            slug: 'mysitesguru',
            name: 'mySites.guru',
            description: 'Joomla Monitoring Dashboard - Monitor unlimited sites from one place',
            url: 'https://mysites.guru',
            icon: 'fa-tachometer-alt',
            logoUrl: '/media/plg_healthchecker_mysitesguru/logo.png',
            version: '1.0.0',
        ));
    }

    /**
     * Inject mySites.guru promotional banner before report display
     *
     * Adds an optional promotional banner to the top of the Health Checker report
     * promoting the mySites.guru monitoring service. The banner is dismissible and
     * uses localStorage to remember the user's preference for 30 days.
     *
     * The banner only displays when:
     * - This plugin is enabled
     * - User hasn't dismissed it in the last 30 days (via localStorage)
     *
     * The dismiss logic is handled by the Health Checker component's JavaScript
     * (admin-report.js) which stores the dismissal timestamp in localStorage.
     *
     * @param BeforeReportDisplayEvent $event Event object for injecting HTML content
     *
     * @since 1.0.0
     */
    public function onBeforeReportDisplay(BeforeReportDisplayEvent $event): void
    {
        $logoUrl = Uri::root() . 'media/plg_healthchecker_mysitesguru/logo.png';
        $bannerText = Text::_('PLG_HEALTHCHECKER_MYSITESGURU_BANNER_TEXT');
        $bannerLink = Text::_('PLG_HEALTHCHECKER_MYSITESGURU_BANNER_LINK');
        $dismissLabel = Text::_('PLG_HEALTHCHECKER_MYSITESGURU_BANNER_DISMISS');

        // Banner HTML
        $html = <<<HTML
    <div id="mysitesguru-banner" class="mysitesguru-banner" style="display: none;">
        <img src="{$logoUrl}" alt="mySites.guru" class="mysitesguru-banner-logo">
        <div class="mysitesguru-banner-content">
            {$bannerText} -
            <a href="https://mysites.guru" target="_blank" rel="noopener" class="mysitesguru-banner-link">
                {$bannerLink}
            </a>
        </div>
        <button type="button" id="mysitesguru-banner-close" class="mysitesguru-banner-close" aria-label="{$dismissLabel}">
            &times;
        </button>
    </div>
HTML;

        // Banner JavaScript for display/dismiss logic
        $js = <<<'JS'
<script>
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        const banner = document.getElementById('mysitesguru-banner');
        if (!banner) return;

        // Check if banner was previously dismissed
        const dismissedAt = localStorage.getItem('mysitesguru_banner_dismissed');
        const oneDayMs = 86400000; // 24 hours in milliseconds

        // Show banner if not dismissed or dismissal expired
        if (!dismissedAt || (Date.now() - parseInt(dismissedAt, 10)) > oneDayMs) {
            banner.style.display = 'flex';
            if (dismissedAt) {
                localStorage.removeItem('mysitesguru_banner_dismissed'); // Clean up expired
            }
        }

        // Handle banner dismissal
        const dismissBtn = document.getElementById('mysitesguru-banner-close');
        if (dismissBtn) {
            dismissBtn.addEventListener('click', function() {
                banner.style.display = 'none';
                localStorage.setItem('mysitesguru_banner_dismissed', Date.now().toString());
            });
        }
    });
})();
</script>
JS;

        $event->addHtmlContent($html . "\n" . $js);
    }

    /**
     * Add mySites.guru toolbar button after the toolbar is built
     *
     * Adds a branded button to the toolbar that links to the mySites.guru website.
     * This button only appears when this plugin is enabled, providing a seamless
     * way to promote the monitoring service to users of the free Health Checker.
     *
     * The button appears after the component's built-in buttons (Run Again, Export,
     * GitHub) but before the Options button.
     *
     * @param AfterToolbarBuildEvent $event Event object containing the toolbar
     *
     * @since 1.0.0
     */
    public function onAfterToolbarBuild(AfterToolbarBuildEvent $event): void
    {
        $toolbar = $event->getToolbar();

        $toolbar->linkButton('mysitesguru')
            ->text('mySites.guru')
            ->url('https://mySites.guru')
            ->icon('icon-tachometer-alt')
            ->attributes(['target' => '_blank', 'style' => 'text-decoration:none'])
            ->buttonClass('btn btn-primary healthchecker-no-external-icon');
    }
}
