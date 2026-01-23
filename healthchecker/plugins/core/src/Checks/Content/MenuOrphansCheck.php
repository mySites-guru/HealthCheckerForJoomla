<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Menu Orphans (Broken Menu Links) Health Check
 *
 * This check identifies published menu items that link to articles that no longer
 * exist, which will cause 404 errors for visitors.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * When articles are deleted but their menu items remain, visitors clicking those
 * menu links encounter 404 Not Found errors. This creates a broken user experience
 * and makes the site appear unmaintained. Search engines also penalize sites with
 * many broken internal links. This is a critical issue that directly impacts
 * visitors navigating the site.
 *
 * RESULT MEANINGS:
 *
 * GOOD: All published menu items point to articles that exist. Navigation is
 * working correctly and users won't encounter 404 errors from menu clicks.
 *
 * WARNING: This check does not return warnings.
 *
 * CRITICAL: One or more published menu items link to non-existent articles.
 * These menu items will cause 404 errors for any visitor who clicks them.
 * Either delete the orphaned menu items, or restore/recreate the missing articles.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Content;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class MenuOrphansCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this health check.
     *
     * @return string The check slug in format 'content.menu_orphans'
     */
    public function getSlug(): string
    {
        return 'content.menu_orphans';
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
     * Perform the health check to identify menu items linking to non-existent articles.
     *
     * This check queries published menu items that link to com_content articles
     * and verifies the target articles still exist. It uses a LEFT JOIN approach
     * with regex-based parameter extraction for robustness with SEF URLs and
     * various link formats. Falls back to SUBSTRING_INDEX for older MySQL versions.
     *
     * @return HealthCheckResult Critical if orphaned menu items found, good otherwise
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // Find menu items pointing to com_content articles where the article doesn't exist.
        // Uses regex extraction with LEFT JOIN for better performance and robustness.
        // REGEXP_SUBSTR extracts the article ID from the link parameter (handles SEF URLs).
        // The LEFT JOIN ensures we only count menu items where c.id IS NULL (article doesn't exist).
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__menu', 'm'))
            ->leftJoin(
                $database->quoteName('#__content', 'c') . ' ON ' .
                // Extract id parameter using REGEXP for robustness with SEF URLs
                'c.id = CAST(REGEXP_SUBSTR(' . $database->quoteName('m.link') . ', ' . $database->quote(
                    '([?&]id=)(\d+)',
                ) . ', 1, 1, ' . $database->quote('') . ', 2) AS UNSIGNED)',
            )
            ->where($database->quoteName('m.published') . ' = 1')
            ->where($database->quoteName('m.link') . ' LIKE ' . $database->quote('%option=com_content%'))
            ->where($database->quoteName('m.link') . ' LIKE ' . $database->quote('%view=article%'))
            ->where($database->quoteName('m.link') . ' LIKE ' . $database->quote('%id=%'))
            ->where($database->quoteName('m.client_id') . ' = 0')
            ->where($database->quoteName('c.id') . ' IS NULL');

        try {
            $orphanCount = (int) $database->setQuery($query)
                ->loadResult();
        } catch (\Exception) {
            // Fallback to older MySQL versions without REGEXP_SUBSTR support.
            // Uses SUBSTRING_INDEX to extract the article ID from the link parameter.
            // First SUBSTRING_INDEX gets everything after 'id=', second gets everything before '&'.
            $query = $database->getQuery(true)
                ->select('COUNT(*)')
                ->from($database->quoteName('#__menu', 'm'))
                ->leftJoin(
                    $database->quoteName('#__content', 'c') . ' ON ' .
                    'CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(' . $database->quoteName('m.link') . ', ' . $database->quote(
                        'id=',
                    ) . ', -1), ' . $database->quote('&') . ', 1) AS UNSIGNED) = ' . $database->quoteName('c.id'),
                )
                ->where($database->quoteName('m.published') . ' = 1')
                ->where($database->quoteName('m.link') . ' LIKE ' . $database->quote('%option=com_content%'))
                ->where($database->quoteName('m.link') . ' LIKE ' . $database->quote('%view=article%'))
                ->where($database->quoteName('m.link') . ' LIKE ' . $database->quote('%id=%'))
                ->where($database->quoteName('m.client_id') . ' = 0')
                ->where($database->quoteName('c.id') . ' IS NULL');

            $orphanCount = (int) $database->setQuery($query)
                ->loadResult();
        }

        if ($orphanCount > 0) {
            return $this->critical(
                sprintf(
                    '%d menu item(s) point to non-existent articles. These will cause 404 errors for visitors.',
                    $orphanCount,
                ),
            );
        }

        return $this->good('All menu items point to existing content.');
    }
}
