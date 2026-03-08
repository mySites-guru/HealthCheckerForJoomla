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
 * GOOD: A valid XML sitemap exists at /sitemap.xml or a common alternative
 * path like /xml-sitemap or /xml-sitemap.xml (either as a physical file
 * or served dynamically) containing proper urlset or sitemapindex structure.
 *
 * WARNING: No sitemap found on disk or via HTTP at any known path, the content
 * is empty, or it doesn't contain valid XML sitemap structure.
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
     * Common sitemap URL paths to check via HTTP.
     *
     * Extensions like PWT Sitemap use Joomla menu aliases (e.g. /xml-sitemap)
     * instead of a physical sitemap.xml file.
     *
     * @var list<string>
     */
    private const SITEMAP_PATHS = ['sitemap.xml', 'xml-sitemap', 'xml-sitemap.xml'];

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
     * falls back to fetching common sitemap paths via HTTP (/sitemap.xml,
     * /xml-sitemap, /xml-sitemap.xml) to detect dynamically generated sitemaps
     * (e.g. PWT Sitemap). Follows redirects automatically to handle sitemap
     * index redirects.
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
     * Try common sitemap URL paths via HTTP to detect dynamically generated sitemaps.
     *
     * Checks /sitemap.xml first, then alternative paths like /xml-sitemap used by
     * extensions such as PWT Sitemap. Joomla's HTTP client follows redirects
     * automatically, so 301 redirects to sub-sitemaps are handled.
     */
    private function checkViaHttp(): HealthCheckResult
    {
        $http = $this->getHttpClient();
        $root = Uri::root();

        foreach (self::SITEMAP_PATHS as $path) {
            try {
                $response = $http->get($root . $path, [], self::HTTP_TIMEOUT_SECONDS);

                if ($response->code !== 200) {
                    continue;
                }

                $content = $response->body;
                $validationError = $this->validateSitemapContent($content);

                if ($validationError instanceof HealthCheckResult) {
                    continue;
                }

                $langKey = $path === 'sitemap.xml'
                    ? 'COM_HEALTHCHECKER_CHECK_SEO_SITEMAP_GOOD_2'
                    : 'COM_HEALTHCHECKER_CHECK_SEO_SITEMAP_GOOD_3';

                return $this->good(Text::sprintf($langKey, '/' . $path));
            } catch (\Throwable) {
                continue;
            }
        }

        return $this->warning(Text::_('COM_HEALTHCHECKER_CHECK_SEO_SITEMAP_WARNING'));
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
