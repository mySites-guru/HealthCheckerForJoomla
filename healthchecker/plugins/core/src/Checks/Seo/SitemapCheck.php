<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * XML Sitemap Health Check
 *
 * This check verifies that an XML sitemap exists either as a physical file
 * or served dynamically (e.g. by extensions like PWT Sitemap) and contains
 * valid sitemap structure to help search engines discover all your content.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * An XML sitemap is a roadmap for search engines, listing all important pages
 * on your site. It helps search engines discover new content faster, understand
 * your site structure, and ensures pages don't get missed during crawling.
 * Sites without sitemaps rely solely on links for discovery, which can mean
 * orphaned or deep pages never get indexed.
 *
 * RESULT MEANINGS:
 *
 * GOOD: A valid XML sitemap exists at /sitemap.xml (either as a physical file
 * or served dynamically) containing proper urlset or sitemapindex structure.
 *
 * WARNING: No sitemap.xml found on disk or via HTTP, the content is empty,
 * or it doesn't contain valid XML sitemap structure.
 *
 * CRITICAL: This check does not return critical status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Seo;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;

\defined('_JEXEC') || die;

final class SitemapCheck extends AbstractHealthCheck
{
    private const HTTP_TIMEOUT_SECONDS = 10;

    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check slug in format 'seo.sitemap'
     */
    public function getSlug(): string
    {
        return 'seo.sitemap';
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

    public function getDocsUrl(?HealthStatus $healthStatus = null): string
    {
        return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/core/src/Checks/Seo/SitemapCheck.php';
    }

    /**
     * Perform the XML sitemap health check.
     *
     * First checks for a physical sitemap.xml file on disk. If not found,
     * falls back to fetching /sitemap.xml via HTTP to detect dynamically
     * generated sitemaps (e.g. PWT Sitemap). Follows redirects automatically
     * to handle sitemap index redirects.
     *
     * @return HealthCheckResult The check result with status and description
     */
    protected function performCheck(): HealthCheckResult
    {
        // Phase 1: Check for a physical sitemap.xml file on disk.
        $sitemapPath = JPATH_ROOT . '/sitemap.xml';

        if (file_exists($sitemapPath)) {
            $content = @file_get_contents($sitemapPath);

            if ($content === false) {
                return $this->warning(Text::_('COM_HEALTHCHECKER_CHECK_SEO_SITEMAP_WARNING_2'));
            }

            return $this->validateSitemapContent($content)
                ?? $this->good(Text::_('COM_HEALTHCHECKER_CHECK_SEO_SITEMAP_GOOD'));
        }

        // Phase 2: No physical file — try fetching via HTTP for dynamic sitemaps.
        return $this->checkViaHttp();
    }

    /**
     * Fetch /sitemap.xml via HTTP to detect dynamically generated sitemaps.
     *
     * Joomla's HTTP client follows redirects automatically, so extensions
     * that 301 redirect from /sitemap.xml to sub-sitemaps are handled.
     */
    private function checkViaHttp(): HealthCheckResult
    {
        try {
            $sitemapUrl = Uri::root() . 'sitemap.xml';
            $http = $this->getHttpClient();
            $response = $http->get($sitemapUrl, [], self::HTTP_TIMEOUT_SECONDS);

            if ($response->code !== 200) {
                return $this->warning(Text::_('COM_HEALTHCHECKER_CHECK_SEO_SITEMAP_WARNING'));
            }

            $content = $response->body;

            return $this->validateSitemapContent($content)
                ?? $this->good(Text::_('COM_HEALTHCHECKER_CHECK_SEO_SITEMAP_GOOD_2'));
        } catch (\Throwable) {
            // Network failure — fall back to the standard "not found" warning.
            return $this->warning(Text::_('COM_HEALTHCHECKER_CHECK_SEO_SITEMAP_WARNING'));
        }
    }

    /**
     * Validate sitemap XML content and return a warning result if invalid, or null if valid.
     */
    private function validateSitemapContent(string $content): ?HealthCheckResult
    {
        if (trim($content) === '') {
            return $this->warning(Text::_('COM_HEALTHCHECKER_CHECK_SEO_SITEMAP_WARNING_3'));
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content);
        libxml_clear_errors();

        if ($xml === false) {
            return $this->warning(Text::_('COM_HEALTHCHECKER_CHECK_SEO_SITEMAP_WARNING_4'));
        }

        $hasUrlset = stripos($content, '<urlset') !== false;
        $hasSitemapIndex = stripos($content, '<sitemapindex') !== false;

        if (! $hasUrlset && ! $hasSitemapIndex) {
            return $this->warning(Text::_('COM_HEALTHCHECKER_CHECK_SEO_SITEMAP_WARNING_5'));
        }

        return null;
    }
}
