<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Broken Links (404 Errors) Health Check
 *
 * This check analyzes the Joomla Redirect Manager to identify unhandled 404
 * errors that indicate broken links pointing to your site.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Broken links create poor user experiences and waste search engine crawl budget.
 * When users or search engines encounter 404 errors, it signals poor site
 * maintenance. Search engines may lower rankings for sites with many broken links.
 * The Redirect Manager tracks these 404s, allowing you to create redirects to
 * valid pages, preserving SEO value and user experience.
 *
 * RESULT MEANINGS:
 *
 * GOOD: Either no unhandled 404 errors exist, a small number exist (under 50),
 * the Redirect component is not installed, or the redirect table couldn't be
 * checked. Small numbers of 404s are normal and informational.
 *
 * WARNING: More than 50 unhandled 404 errors are tracked in the Redirect Manager.
 * Review these URLs and create redirects for important pages to improve user
 * experience and preserve SEO value from inbound links.
 *
 * CRITICAL: This check does not return critical status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Seo;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class BrokenLinksCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check slug in format 'seo.broken_links'
     */
    public function getSlug(): string
    {
        return 'seo.broken_links';
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
     * Perform the broken links (404 errors) health check.
     *
     * Analyzes Joomla's Redirect Manager component to count unhandled 404
     * errors. These represent broken links pointing to the site that haven't
     * been redirected to valid pages. High numbers indicate poor user experience
     * and wasted search engine crawl budget.
     *
     * @return HealthCheckResult The check result with status and description
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();

        // First verify that com_redirect is installed and available.
        // Without this component, Joomla doesn't track 404 errors,
        // so we can't perform the check.
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__extensions'))
            ->where($database->quoteName('type') . ' = ' . $database->quote('component'))
            ->where($database->quoteName('element') . ' = ' . $database->quote('com_redirect'));

        $database->setQuery($query);
        $redirectInstalled = (int) $database->loadResult();

        if ($redirectInstalled === 0) {
            return $this->good('Redirect component is not installed. Unable to check for tracked 404 errors.');
        }

        // Query the redirect links table for unhandled 404 errors.
        // published = 0 means these are tracked errors without redirects.
        // header = 404 means these are "Not Found" responses.
        // The combination represents broken links that should be addressed.
        try {
            $query = $database->getQuery(true)
                ->select('COUNT(*)')
                ->from($database->quoteName('#__redirect_links'))
                ->where($database->quoteName('published') . ' = 0') // Unpublished = no redirect created yet
                ->where($database->quoteName('header') . ' = 404'); // HTTP 404 Not Found

            $database->setQuery($query);
            $count404 = (int) $database->loadResult();
        } catch (\Exception) {
            // Table might not exist if component was improperly installed/uninstalled
            return $this->good('Could not check redirect links table.');
        }

        // More than 50 404 errors indicates significant broken link issues.
        // This many errors wastes search engine crawl budget and creates
        // poor user experience. Site owner should review and create redirects
        // for important pages that moved or were deleted.
        if ($count404 > 50) {
            return $this->warning(
                sprintf(
                    '%d unhandled 404 errors found in Redirect Manager. Review and create redirects to improve user experience and SEO.',
                    $count404,
                ),
            );
        }

        // Small number of 404s is normal and acceptable. Pages get moved/deleted,
        // external sites link to wrong URLs. Informational only - not urgent.
        if ($count404 > 0) {
            return $this->good(
                sprintf(
                    '%d unhandled 404 errors tracked in Redirect Manager. Consider creating redirects for important pages.',
                    $count404,
                ),
            );
        }

        // No tracked 404 errors - excellent link maintenance or new site.
        return $this->good('No unhandled 404 errors found in Redirect Manager.');
    }
}
