<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Canonical URL Configuration Health Check
 *
 * This check verifies that the site is properly configured to generate canonical
 * URLs, which tell search engines the preferred version of a page when multiple
 * URLs can show the same content.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Canonical URLs prevent duplicate content issues. The same page might be
 * accessible via multiple URLs (with/without www, http/https, with query strings,
 * etc.). Without canonical tags, search engines may split ranking signals between
 * duplicates or index the wrong version. Joomla automatically generates canonical
 * tags when SEF URLs and the SEF plugin are enabled.
 *
 * RESULT MEANINGS:
 *
 * GOOD: SEF URLs are enabled and the System SEF plugin is active, allowing
 * Joomla to automatically generate proper canonical URL meta tags.
 *
 * WARNING: Either SEF URLs are disabled (canonical URLs work poorly without SEF),
 * or the System SEF plugin is disabled (which handles canonical tag generation).
 * Enable both for proper canonical URL handling.
 *
 * CRITICAL: This check does not return critical status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Seo;

use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class CanonicalUrlCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check slug in format 'seo.canonical_url'
     */
    public function getSlug(): string
    {
        return 'seo.canonical_url';
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
     * Perform the canonical URL configuration health check.
     *
     * Verifies that Joomla is configured to generate canonical URL meta tags
     * by checking if SEF URLs are enabled and the System SEF plugin is active.
     * Canonical URLs prevent duplicate content issues when the same page is
     * accessible via multiple URLs.
     *
     * @return HealthCheckResult The check result with status and description
     */
    protected function performCheck(): HealthCheckResult
    {
        // Retrieve SEF (Search Engine Friendly) URL configuration.
        // SEF URLs are required for proper canonical URL generation.
        // When disabled, Joomla uses query strings like ?option=com_content&view=article&id=1
        // which makes canonical URL generation unreliable.
        $sef = (int) Factory::getApplication()->get('sef', 0);
        Factory::getApplication()->get('sef_suffix', 0);

        if ($sef === 0) {
            return $this->warning('SEF URLs are disabled. Canonical URLs work best with SEF URLs enabled.');
        }

        // In Joomla 4+/5+, canonical URLs are automatically added to pages when
        // SEF is enabled AND the System SEF plugin is active. The plugin handles
        // adding <link rel="canonical"> tags to prevent duplicate content issues.
        $database = $this->requireDatabase();

        $query = $database->getQuery(true)
            ->select($database->quoteName('enabled'))
            ->from($database->quoteName('#__extensions'))
            ->where($database->quoteName('type') . ' = ' . $database->quote('plugin'))
            ->where($database->quoteName('folder') . ' = ' . $database->quote('system'))
            ->where($database->quoteName('element') . ' = ' . $database->quote('sef'));

        $database->setQuery($query);
        $sefPluginEnabled = (int) $database->loadResult();

        // Without the SEF plugin enabled, canonical tags won't be generated
        // even if SEF URLs are enabled. This leaves the site vulnerable to
        // duplicate content penalties from search engines.
        if ($sefPluginEnabled === 0) {
            return $this->warning(
                'System SEF plugin is disabled. Enable it to ensure proper canonical URL handling.',
            );
        }

        // Both SEF URLs and SEF plugin are enabled - canonical URLs will be
        // automatically generated for all pages, preventing duplicate content issues.
        return $this->good(
            'SEF URLs are enabled and the SEF plugin is active. Canonical URLs should be generated automatically.',
        );
    }
}
