<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Open Graph Meta Tags Health Check
 *
 * This check detects whether an Open Graph plugin is installed and enabled,
 * which controls how your content appears when shared on Facebook, LinkedIn,
 * and other social platforms.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Open Graph meta tags control the title, description, and image shown when
 * your pages are shared on social media. Without them, platforms make guesses
 * that often result in poor previews with wrong images or truncated text.
 * Proper Open Graph tags significantly increase click-through rates from
 * social shares and make your content look professional.
 *
 * RESULT MEANINGS:
 *
 * GOOD: One or more plugins that likely provide Open Graph meta tags are
 * installed and enabled, ensuring attractive social media share previews.
 *
 * WARNING: No Open Graph plugin detected. Shared links may have poor previews
 * on Facebook, LinkedIn, and other platforms. Install a social meta or
 * Open Graph extension to control how shared content appears.
 *
 * CRITICAL: This check does not return critical status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Seo;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class OpenGraphCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this health check.
     *
     * @return string The check slug in format 'seo.open_graph'
     */
    public function getSlug(): string
    {
        return 'seo.open_graph';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category slug 'seo'
     */
    public function getCategory(): string
    {
        return 'seo';
    }

    /**
     * Perform the Open Graph meta tags detection check.
     *
     * Searches the extensions table for enabled plugins that likely provide Open Graph
     * meta tags. Checks both the plugin name and element fields for common keywords
     * associated with Open Graph and social meta tag functionality.
     *
     * The check looks for plugins containing:
     * - 'opengraph' or 'open graph' in the name
     * - 'social meta' in the name
     * - 'opengraph' or 'og' in the element field
     *
     * Open Graph meta tags (og:title, og:description, og:image, etc.) are essential
     * for controlling how content appears when shared on Facebook, LinkedIn, and
     * other social platforms that support the Open Graph protocol.
     *
     * @return HealthCheckResult Good if Open Graph plugins found, Warning if none detected
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();

        // Check for common Open Graph plugins by searching the extensions table
        // for enabled plugins with keywords indicating Open Graph functionality
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__extensions'))
            ->where($database->quoteName('type') . ' = ' . $database->quote('plugin'))
            ->where($database->quoteName('enabled') . ' = 1')
            ->where(
                '(' .
                // Search plugin name field for Open Graph indicators
                $database->quoteName('name') . ' LIKE ' . $database->quote('%opengraph%') .
                ' OR ' . $database->quoteName('name') . ' LIKE ' . $database->quote('%open graph%') .
                ' OR ' . $database->quoteName('name') . ' LIKE ' . $database->quote('%social%meta%') .
                // Search plugin element field for Open Graph indicators
                ' OR ' . $database->quoteName('element') . ' LIKE ' . $database->quote('%opengraph%') .
                ' OR ' . $database->quoteName('element') . ' LIKE ' . $database->quote('%og%') .
                ')',
            );

        $database->setQuery($query);
        $ogPluginCount = (int) $database->loadResult();

        // If one or more Open Graph plugins are found, social sharing is likely configured
        if ($ogPluginCount > 0) {
            return $this->good(
                sprintf('Found %d enabled plugin(s) that may provide Open Graph meta tags.', $ogPluginCount),
            );
        }

        // No Open Graph plugins detected - social shares will have poor previews
        return $this->warning(
            'No Open Graph plugin detected. Consider installing an Open Graph/social meta plugin to improve social media sharing.',
        );
    }
}
