<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Lazy Load Health Check
 *
 * This check verifies whether lazy loading for images is enabled in the
 * Content - Joomla plugin settings.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Lazy loading defers the loading of images until they are about to scroll
 * into the viewport. This reduces initial page load time and bandwidth usage,
 * especially on pages with many images. It improves Core Web Vitals scores
 * (particularly Largest Contentful Paint) and provides better user experience
 * on slower connections.
 *
 * RESULT MEANINGS:
 *
 * GOOD: Lazy loading for images is enabled. Images will only load as users
 * scroll near them, improving initial page load performance.
 *
 * WARNING: Lazy loading is disabled or the Content - Joomla plugin is disabled.
 * Enable lazy loading in the plugin settings for better performance. If the
 * Content - Joomla plugin itself is disabled, lazy loading cannot function.
 *
 * CRITICAL: This check does not return CRITICAL status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Performance;

use Joomla\CMS\Plugin\PluginHelper;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class LazyLoadCheck extends AbstractHealthCheck
{
    /**
     * Get the unique identifier for this health check.
     *
     * @return string The check slug in format 'performance.lazy_load'
     */
    public function getSlug(): string
    {
        return 'performance.lazy_load';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category identifier 'performance'
     */
    public function getCategory(): string
    {
        return 'performance';
    }

    /**
     * Perform the lazy load health check.
     *
     * This method verifies whether lazy loading for images is enabled in the
     * Content - Joomla plugin settings. Lazy loading defers image loading until
     * images are about to enter the viewport, improving initial page load times
     * and Core Web Vitals scores.
     *
     * The check performs these steps:
     * 1. Verifies Content - Joomla plugin is enabled
     * 2. Queries database for plugin parameters
     * 3. Checks if lazy_images parameter is enabled
     *
     * Returns:
     * - GOOD: Lazy loading is enabled
     * - WARNING: Plugin disabled, settings unavailable, or lazy loading disabled
     *
     * @return HealthCheckResult The result indicating lazy load configuration status
     */
    protected function performCheck(): HealthCheckResult
    {
        // First verify the Content - Joomla plugin is enabled
        $isEnabled = PluginHelper::isEnabled('content', 'joomla');

        if (! $isEnabled) {
            return $this->warning(
                'Content - Joomla plugin is disabled. This plugin provides lazy loading for images.',
            );
        }

        $database = $this->requireDatabase();

        // Query the plugin parameters from the database
        $query = $database->getQuery(true)
            ->select($database->quoteName('params'))
            ->from($database->quoteName('#__extensions'))
            ->where($database->quoteName('element') . ' = ' . $database->quote('joomla'))
            ->where($database->quoteName('folder') . ' = ' . $database->quote('content'))
            ->where($database->quoteName('type') . ' = ' . $database->quote('plugin'));

        $params = $database->setQuery($query)
            ->loadResult();

        if ($params === null || $params === '') {
            return $this->warning('Unable to determine lazy load configuration.');
        }

        // Decode JSON parameters
        $paramsObj = json_decode((string) $params, true);

        if (! is_array($paramsObj)) {
            return $this->warning('Unable to decode plugin parameters.');
        }

        // Check if lazy_images parameter is enabled (1 = enabled, 0 = disabled)
        $lazyImages = (int) ($paramsObj['lazy_images'] ?? 0);

        if ($lazyImages === 0) {
            return $this->warning(
                'Lazy loading for images is disabled. Enable it in the Content - Joomla plugin settings for better performance.',
            );
        }

        return $this->good('Lazy loading for images is enabled.');
    }
}
