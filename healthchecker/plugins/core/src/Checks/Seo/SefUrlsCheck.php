<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Search Engine Friendly (SEF) URLs Health Check
 *
 * This check verifies that Search Engine Friendly URLs are enabled along with
 * URL rewriting for clean, human-readable URLs without index.php in the path.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * SEF URLs transform ugly URLs like "index.php?option=com_content&view=article&id=1"
 * into clean URLs like "/my-article". Search engines prefer clean URLs as they
 * provide context about page content and are more likely to be clicked by users.
 * URL rewriting removes "index.php" for even cleaner URLs, improving both SEO
 * and user experience.
 *
 * RESULT MEANINGS:
 *
 * GOOD: Both SEF URLs and URL rewriting are enabled, providing the cleanest
 * possible URLs for optimal search engine indexing and user experience.
 *
 * WARNING: Either SEF URLs are disabled entirely (URLs contain query strings),
 * or SEF is enabled but URL rewriting is off (URLs still contain "index.php").
 * Enable both in Global Configuration -> Site -> SEO Settings.
 *
 * CRITICAL: This check does not return critical status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Seo;

use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class SefUrlsCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this health check.
     *
     * @return string The check slug in format 'seo.sef_urls'
     */
    public function getSlug(): string
    {
        return 'seo.sef_urls';
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
     * Perform the Search Engine Friendly URLs configuration check.
     *
     * Verifies that both SEF URLs and URL rewriting are enabled in Joomla's global
     * configuration. These settings control URL format and appearance:
     *
     * - SEF URLs (sef=1): Converts query strings to path-based URLs
     *   Example: /component/article/1 instead of ?option=com_content&view=article&id=1
     *
     * - URL Rewriting (sef_rewrite=1): Removes 'index.php' from URLs
     *   Example: /my-article instead of /index.php/my-article
     *   Requires server-level .htaccess or nginx rewrite rules to function
     *
     * Both settings together provide the cleanest, most SEO-friendly URLs that
     * search engines prefer and users are more likely to click and share.
     *
     * @return HealthCheckResult Warning if either setting is disabled, Good if both enabled
     */
    /**
     * Perform the Sef Urls health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        // Get SEF configuration from Joomla global configuration
        // sef: Enables Search Engine Friendly URLs (path-based instead of query strings)
        $sef = (int) Factory::getApplication()->get('sef', 0);
        // sef_rewrite: Removes index.php from URLs (requires .htaccess/nginx config)
        $sefRewrite = (int) Factory::getApplication()->get('sef_rewrite', 0);

        // Check if basic SEF URLs are disabled - this is the most critical setting
        if ($sef === 0) {
            return $this->warning('Search Engine Friendly URLs are disabled. Enable them for better SEO.');
        }

        // SEF is enabled but URL rewriting is off - URLs will still contain index.php
        if ($sefRewrite === 0) {
            return $this->warning(
                'SEF URLs are enabled but URL rewriting is off. Enable "Use URL Rewriting" for cleaner URLs.',
            );
        }

        // Both settings are enabled - optimal configuration for SEO
        return $this->good('SEF URLs and URL rewriting are enabled.');
    }
}
