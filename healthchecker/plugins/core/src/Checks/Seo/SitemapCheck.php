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
 * This check verifies that an XML sitemap exists in the site root and contains
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
 * GOOD: A valid XML sitemap exists at /sitemap.xml containing proper urlset
 * or sitemapindex structure for search engine consumption.
 *
 * WARNING: Either no sitemap.xml exists, the file is empty, or it doesn't
 * contain valid XML sitemap structure (missing urlset or sitemapindex elements).
 * Install an XML sitemap extension or generate one manually.
 *
 * CRITICAL: This check does not return critical status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Seo;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class SitemapCheck extends AbstractHealthCheck
{
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

    /**
     * Perform the XML sitemap health check.
     *
     * Verifies that a valid XML sitemap exists at the standard location
     * (/sitemap.xml) and contains proper sitemap structure. Validates XML
     * syntax and checks for required urlset or sitemapindex elements that
     * search engines need to parse the sitemap correctly.
     *
     * @return HealthCheckResult The check result with status and description
     */
    /**
     * Perform the Sitemap health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        // sitemap.xml must be in site root as per sitemap protocol specification.
        // Search engines look for it at http://example.com/sitemap.xml
        $sitemapPath = JPATH_ROOT . '/sitemap.xml';

        if (! file_exists($sitemapPath)) {
            return $this->warning(
                'sitemap.xml not found in site root. Consider generating a sitemap to help search engines discover your content.',
            );
        }

        // Attempt to read sitemap contents. Using @ to suppress warnings
        // for permission issues, handled by false check below.
        $content = @file_get_contents($sitemapPath);

        if ($content === false) {
            return $this->warning('sitemap.xml exists but could not be read.');
        }

        // Empty sitemaps are useless for search engines and may indicate
        // a failed generation or corrupted file.
        if (trim($content) === '') {
            return $this->warning('sitemap.xml exists but appears to be empty. Regenerate your sitemap.');
        }

        // Validate that the file contains well-formed XML.
        // Using internal error handling to catch XML parsing errors gracefully.
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content);
        libxml_clear_errors();

        if ($xml === false) {
            return $this->warning('sitemap.xml exists but contains invalid XML. Check for syntax errors.');
        }

        // Check for required sitemap protocol elements.
        // A valid sitemap must have either <urlset> (single sitemap) or
        // <sitemapindex> (sitemap index pointing to multiple sitemaps).
        // Without these, search engines won't recognize it as a valid sitemap.
        $hasUrlset = stripos($content, '<urlset') !== false;
        $hasSitemapIndex = stripos($content, '<sitemapindex') !== false;

        if (! $hasUrlset && ! $hasSitemapIndex) {
            return $this->warning(
                'sitemap.xml exists but does not contain valid sitemap structure (missing urlset or sitemapindex).',
            );
        }

        // Sitemap exists, is valid XML, and contains required sitemap elements.
        // Further validation of URLs and structure would require full parsing.
        return $this->good('sitemap.xml is present in site root.');
    }
}
