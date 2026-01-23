<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Site Meta Description Health Check
 *
 * This check verifies that the site's global meta description is set and has an
 * optimal length for search engine display. The meta description appears in search
 * results below the page title and influences click-through rates.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * The site meta description is the default description used when pages don't have
 * their own. Search engines display this 155-160 character snippet in results,
 * making it critical for attracting clicks. A missing or poorly-sized description
 * means lost SEO opportunities and lower click-through rates from search results.
 *
 * RESULT MEANINGS:
 *
 * GOOD: The site meta description is set and within the optimal 50-160 character
 * range, maximizing visibility in search results without truncation.
 *
 * WARNING: Either the description is missing, too short (less than 50 characters
 * and may not be compelling), or too long (over 160 characters and will be
 * truncated by search engines). Configure in Global Configuration -> Site.
 *
 * CRITICAL: This check does not return critical status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Seo;

use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class SiteMetaDescriptionCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this health check.
     *
     * @return string The check slug in format 'seo.site_meta_description'
     */
    public function getSlug(): string
    {
        return 'seo.site_meta_description';
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
     * Perform the site meta description configuration check.
     *
     * Validates the global site meta description for existence and optimal length.
     * The meta description is used as a fallback when individual pages don't have
     * their own description tags set.
     *
     * Checks performed:
     * 1. Verifies description is not empty or just '0'
     * 2. Validates minimum length (50 characters for meaningful content)
     * 3. Validates maximum length (160 characters to avoid search engine truncation)
     *
     * Search engines typically display 155-160 characters in search results, so
     * descriptions should be compelling summaries within this range. Too short
     * descriptions may not provide enough context; too long ones get cut off.
     *
     * The optimal range is 120-160 characters, but we allow 50-160 to accommodate
     * different writing styles while still flagging descriptions that are clearly
     * too short or too long for effective SEO.
     *
     * @return HealthCheckResult Warning if missing or poor length, Good if within optimal range
     */
    protected function performCheck(): HealthCheckResult
    {
        // Retrieve the global site meta description from Joomla configuration
        // This is the <meta name="description"> tag content shown in search results
        $metaDesc = Factory::getApplication()->get('MetaDesc', '');

        // Check if meta description is empty or just '0' (both are invalid)
        if (in_array(trim((string) $metaDesc), ['', '0'], true)) {
            return $this->warning(
                'Site meta description is not set. Add a description in Global Configuration for better SEO.',
            );
        }

        // Calculate character length for validation against SEO best practices
        $length = strlen((string) $metaDesc);

        // Description is too short - won't be compelling in search results
        // 50 characters minimum ensures there's enough content to be meaningful
        if ($length < 50) {
            return $this->warning(
                sprintf('Site meta description is too short (%d characters). Aim for 120-160 characters.', $length),
            );
        }

        // Description exceeds typical search engine display limit
        // Google and other search engines typically show 155-160 characters
        if ($length > 160) {
            return $this->warning(
                sprintf(
                    'Site meta description is too long (%d characters). Search engines may truncate it. Aim for 120-160 characters.',
                    $length,
                ),
            );
        }

        // Meta description is within optimal range (50-160 characters)
        return $this->good(sprintf('Site meta description is set (%d characters).', $length));
    }
}
