<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Robots.txt File Health Check
 *
 * This check verifies the presence and basic validity of the robots.txt file,
 * which controls how search engine crawlers access and index your site.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * The robots.txt file is the first thing search engine crawlers look for when
 * visiting your site. It tells them which areas they can and cannot access.
 * A missing robots.txt results in crawlers indexing everything (including
 * potentially sensitive admin areas), while a misconfigured one can accidentally
 * block your entire site from being indexed.
 *
 * RESULT MEANINGS:
 *
 * GOOD: A robots.txt file exists in the site root and contains valid directives
 * that don't appear to block the entire site from indexing.
 *
 * WARNING: Either the robots.txt file is missing (crawlers will index everything),
 * or it contains "Disallow: /" which blocks the entire site from search engines.
 * Create or fix the file in your site root directory.
 *
 * CRITICAL: This check does not return critical status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Seo;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class RobotsFileCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check slug in format 'seo.robots_file'
     */
    public function getSlug(): string
    {
        return 'seo.robots_file';
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
     * Perform the robots.txt file health check.
     *
     * Verifies that robots.txt exists in site root and doesn't contain
     * directives that would block the entire site from search engine indexing.
     * Checks for the common mistake of "Disallow: /" which prevents all
     * crawling and indexing.
     *
     * @return HealthCheckResult The check result with status and description
     */
    /**
     * Perform the Robots File health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        // robots.txt must be in site root to be found by search engine crawlers.
        // This is the first file crawlers request when visiting a site.
        $robotsPath = JPATH_ROOT . '/robots.txt';

        if (! file_exists($robotsPath)) {
            return $this->warning(
                'robots.txt file not found. Consider creating one to guide search engine crawlers.',
            );
        }

        // Attempt to read the file contents. Using @ to suppress warnings
        // in case of permission issues, which we handle with false check.
        $content = @file_get_contents($robotsPath);

        if ($content === false) {
            return $this->warning('robots.txt exists but could not be read.');
        }

        // Check for the common and dangerous "Disallow: /" pattern which blocks
        // all crawlers from the entire site. We need to be careful to not flag
        // "Disallow: /admin" or similar subdirectory blocks.
        // The regex ensures we only match when / is followed by end of line,
        // indicating the root path block, not a subdirectory.
        if (stripos($content, 'Disallow: /') !== false && stripos($content, 'Disallow: / ') === false && preg_match(
            '/Disallow:\s*\/\s*$/mi',
            $content,
        )) {
            return $this->warning('robots.txt may be blocking the entire site. Review the Disallow rules.');
        }

        // robots.txt exists and doesn't appear to block the entire site.
        // Further validation of directives would require parsing the entire file.
        return $this->good('robots.txt file is present.');
    }
}
