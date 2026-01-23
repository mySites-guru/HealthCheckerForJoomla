<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Category Depth Health Check
 *
 * This check identifies content categories that are nested more than 5 levels deep,
 * which can cause usability and URL length issues.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Deeply nested categories create several problems: URLs become excessively long
 * and harder to remember/share, navigation breadcrumbs become unwieldy, and users
 * may struggle to find content buried many levels deep. Search engines also tend
 * to give less weight to content in extremely deep URL structures. Most content
 * management best practices recommend limiting category depth to 3-4 levels.
 *
 * RESULT MEANINGS:
 *
 * GOOD: All content categories are nested at reasonable depths (5 levels or fewer
 * from the root), maintaining usable URL lengths and navigation structures.
 *
 * WARNING: One or more categories exceed 5 levels of nesting. Consider
 * restructuring your category hierarchy to reduce depth. Very deep categories
 * may have excessively long URLs and create navigation challenges.
 *
 * CRITICAL: This check does not return critical status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Content;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class CategoryDepthCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this health check.
     *
     * @return string The check slug in format 'content.category_depth'
     */
    public function getSlug(): string
    {
        return 'content.category_depth';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category slug 'content'
     */
    public function getCategory(): string
    {
        return 'content';
    }

    /**
     * Perform the health check to identify excessively nested category hierarchies.
     *
     * This check finds published content categories nested more than 5 levels deep,
     * which can cause usability issues, excessively long URLs, and navigation problems.
     * Level 1 is the root category, so level 6+ represents 5 or more levels of nesting.
     * If deep categories are found, the check also reports the maximum depth for context.
     *
     * @return HealthCheckResult Warning if categories exceed 5 levels deep, good otherwise
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // Check for deeply nested categories (level > 5) in com_content.
        // Level 1 is the root category, so level 6+ means 5 levels deep from root.
        // Only published categories are checked as unpublished ones don't affect users.
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__categories'))
            ->where($database->quoteName('extension') . ' = ' . $database->quote('com_content'))
            ->where($database->quoteName('published') . ' = 1')
            ->where($database->quoteName('level') . ' > 5');

        $deepCount = (int) $database->setQuery($query)
            ->loadResult();

        if ($deepCount > 0) {
            // Get the maximum depth for context in the warning message.
            // This helps administrators understand how deep their category tree goes.
            $maxQuery = $database->getQuery(true)
                ->select('MAX(' . $database->quoteName('level') . ')')
                ->from($database->quoteName('#__categories'))
                ->where($database->quoteName('extension') . ' = ' . $database->quote('com_content'))
                ->where($database->quoteName('published') . ' = 1');

            $maxLevel = (int) $database->setQuery($maxQuery)
                ->loadResult();

            return $this->warning(
                sprintf(
                    '%d categor%s nested more than 5 levels deep (max depth: %d). Deep nesting may cause UX issues and longer URLs.',
                    $deepCount,
                    $deepCount === 1 ? 'y is' : 'ies are',
                    $maxLevel,
                ),
            );
        }

        return $this->good('No categories are nested more than 5 levels deep.');
    }
}
