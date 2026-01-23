<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Media Manager Thumbnails Health Check
 *
 * This check verifies whether the Filesystem - Local plugin is configured to
 * generate thumbnails for the Media Manager, improving performance when browsing
 * large media libraries.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * By default, the Joomla Media Manager loads full-size images when browsing,
 * which can be extremely slow for sites with large media libraries or high-resolution
 * images. Enabling thumbnail generation creates smaller preview images that load
 * much faster, dramatically improving the Media Manager user experience.
 *
 * RESULT MEANINGS:
 *
 * GOOD: Thumbnail generation is enabled. The Media Manager will display optimized
 * thumbnails instead of full-size images, providing faster browsing performance.
 *
 * WARNING: Thumbnail generation is disabled or the plugin is not properly configured.
 * Enable thumbnail generation in the Filesystem - Local plugin settings to improve
 * Media Manager performance, especially for sites with many or large images.
 *
 * CRITICAL: This check does not return CRITICAL status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Performance;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class MediaManagerThumbnailsCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check slug in format 'performance.media_manager_thumbnails'
     */
    public function getSlug(): string
    {
        return 'performance.media_manager_thumbnails';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category slug 'performance'
     */
    public function getCategory(): string
    {
        return 'performance';
    }

    /**
     * Perform the Media Manager thumbnails health check.
     *
     * Checks if the Filesystem - Local plugin is configured to generate thumbnails
     * for the Media Manager. Without thumbnails, the Media Manager loads full-size
     * images which can be very slow for large media libraries.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();

        // Query the Filesystem - Local plugin parameters
        $query = $database->getQuery(true)
            ->select([$database->quoteName('params'), $database->quoteName('enabled')])
            ->from($database->quoteName('#__extensions'))
            ->where($database->quoteName('type') . ' = ' . $database->quote('plugin'))
            ->where($database->quoteName('folder') . ' = ' . $database->quote('filesystem'))
            ->where($database->quoteName('element') . ' = ' . $database->quote('local'));

        $plugin = $database->setQuery($query)
            ->loadObject();

        if (! $plugin) {
            return $this->warning(
                'Filesystem - Local plugin not found. This core plugin is required for the Media Manager.',
            );
        }

        if ((int) $plugin->enabled === 0) {
            return $this->warning(
                'Filesystem - Local plugin is disabled. Enable it for the Media Manager to function.',
            );
        }

        $params = json_decode((string) $plugin->params, true);

        if (! is_array($params)) {
            return $this->warning('Unable to read Filesystem - Local plugin configuration.');
        }

        // Check if thumbnail generation is enabled
        // The parameter is 'thumbnail_size' - if set to a value > 0, thumbnails are enabled
        $thumbnailSize = (int) ($params['thumbnail_size'] ?? 0);

        if ($thumbnailSize <= 0) {
            return $this->warning(
                'Media Manager thumbnail generation is disabled. Enable it in the Filesystem - Local plugin settings to improve browsing performance.',
            );
        }

        return $this->good(
            sprintf(
                'Media Manager thumbnails are enabled (%dpx). Browsing large media libraries will be faster.',
                $thumbnailSize,
            ),
        );
    }
}
