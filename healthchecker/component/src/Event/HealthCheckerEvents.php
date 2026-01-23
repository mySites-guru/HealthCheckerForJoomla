<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace MySitesGuru\HealthChecker\Component\Administrator\Event;

\defined('_JEXEC') || die;

/**
 * Health Checker Event Names
 *
 * Defines constants for all Health Checker event names to ensure consistency
 * across event dispatchers and subscribers.
 *
 * @since 1.0.0
 */
enum HealthCheckerEvents: string
{
    /**
     * Event triggered when collecting health check providers
     *
     * Plugins should listen to this event to register their provider metadata.
     *
     * @since 1.0.0
     */
    case COLLECT_PROVIDERS = 'onHealthCheckerCollectProviders';

    /**
     * Event triggered when collecting health check categories
     *
     * Plugins should listen to this event to register custom categories.
     *
     * @since 1.0.0
     */
    case COLLECT_CATEGORIES = 'onHealthCheckerCollectCategories';

    /**
     * Event triggered when collecting health checks
     *
     * Plugins should listen to this event to register their health check instances.
     *
     * @since 1.0.0
     */
    case COLLECT_CHECKS = 'onHealthCheckerCollectChecks';

    /**
     * Event triggered before the health check report is displayed
     *
     * Plugins can listen to this event to inject content (banners, notices, etc.)
     * into the report view before it's rendered.
     *
     * @since 1.0.0
     */
    case BEFORE_REPORT_DISPLAY = 'onHealthCheckerBeforeReportDisplay';

    /**
     * Get the standard handler method name for this event
     *
     * Returns the conventional plugin method name that should handle this event.
     *
     * @return string The handler method name (e.g., 'onCollectProviders')
     *
     * @since 1.0.0
     */
    public function getHandlerMethod(): string
    {
        return match ($this) {
            self::COLLECT_PROVIDERS => 'onCollectProviders',
            self::COLLECT_CATEGORIES => 'onCollectCategories',
            self::COLLECT_CHECKS => 'onCollectChecks',
            self::BEFORE_REPORT_DISPLAY => 'onBeforeReportDisplay',
        };
    }
}
