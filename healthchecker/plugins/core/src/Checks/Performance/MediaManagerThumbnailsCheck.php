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
 * generate thumbnails for each directory in the Media Manager, improving
 * performance when browsing large media libraries.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * By default, the Joomla Media Manager loads full-size images when browsing,
 * which can be extremely slow for sites with large media libraries or high-resolution
 * images. Enabling thumbnail generation creates smaller preview images that load
 * much faster, dramatically improving the Media Manager user experience.
 *
 * RESULT MEANINGS:
 *
 * GOOD: Thumbnail generation is enabled for all configured directories. The Media
 * Manager will display optimized thumbnails instead of full-size images.
 *
 * WARNING: Thumbnail generation is disabled for one or more directories, the plugin
 * is not properly configured, or no directories are configured at all. Enable
 * thumbnail generation in the Filesystem - Local plugin settings to improve
 * Media Manager performance, especially for sites with many or large images.
 *
 * CRITICAL: This check does not return CRITICAL status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Performance;

use Joomla\CMS\Language\Text;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;

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

    public function getDocsUrl(?HealthStatus $healthStatus = null): string
    {
        return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/core/src/Checks/Performance/MediaManagerThumbnailsCheck.php';
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

        if ($plugin === null) {
            return $this->warning(Text::_('COM_HEALTHCHECKER_CHECK_PERFORMANCE_MEDIA_MANAGER_THUMBNAILS_WARNING'));
        }

        if ((int) $plugin->enabled === 0) {
            return $this->warning(
                Text::_('COM_HEALTHCHECKER_CHECK_PERFORMANCE_MEDIA_MANAGER_THUMBNAILS_WARNING_2'),
            );
        }

        $params = json_decode((string) $plugin->params, true);

        if (! is_array($params)) {
            return $this->warning(Text::_('COM_HEALTHCHECKER_CHECK_PERFORMANCE_MEDIA_MANAGER_THUMBNAILS_WARNING_3'));
        }

        // The plugin stores directories as a subform array under 'directories'.
        // Each entry has 'directory' (folder name) and 'thumbs' (0 or 1).
        $directories = $params['directories'] ?? [];

        if (! is_array($directories) || $directories === []) {
            return $this->warning(
                Text::_('COM_HEALTHCHECKER_CHECK_PERFORMANCE_MEDIA_MANAGER_THUMBNAILS_WARNING_4'),
            );
        }

        $disabledDirs = [];

        foreach ($directories as $directory) {
            if (! is_array($directory)) {
                continue;
            }

            $dirName = (string) ($directory['directory'] ?? '');
            $thumbsEnabled = (int) ($directory['thumbs'] ?? 0);

            if ($dirName !== '' && $thumbsEnabled === 0) {
                $disabledDirs[] = $dirName;
            }
        }

        if ($disabledDirs !== []) {
            return $this->warning(
                Text::sprintf(
                    'COM_HEALTHCHECKER_CHECK_PERFORMANCE_MEDIA_MANAGER_THUMBNAILS_WARNING_5',
                    implode(', ', $disabledDirs),
                ),
            );
        }

        return $this->good(
            Text::sprintf('COM_HEALTHCHECKER_CHECK_PERFORMANCE_MEDIA_MANAGER_THUMBNAILS_GOOD', \count($directories)),
        );
    }
}
