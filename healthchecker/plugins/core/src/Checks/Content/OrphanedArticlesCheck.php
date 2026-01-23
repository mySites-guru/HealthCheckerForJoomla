<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Orphaned Articles Health Check
 *
 * This check identifies published articles that are not linked from any menu item,
 * making them effectively invisible to users navigating through the site menus.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Articles not linked from menus can only be reached via direct URL, search, or
 * links within other content. While sometimes intentional (landing pages, linked
 * only from modules), many orphaned articles are forgotten content that wastes
 * resources. Orphaned articles also miss out on menu-driven navigation benefits
 * and may create content management confusion.
 *
 * RESULT MEANINGS:
 *
 * GOOD: Either all published articles are linked from menus, or only a small
 * number (10 or fewer) are orphaned. Small numbers may be intentional for
 * special landing pages or articles linked only from modules/content.
 *
 * WARNING: More than 10 published articles are not linked from any menu item.
 * Review these articles and either create menu items for them, link them from
 * other content, or unpublish them if no longer needed.
 *
 * CRITICAL: This check does not return critical status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Content;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class OrphanedArticlesCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this health check.
     *
     * @return string The check slug in format 'content.orphaned_articles'
     */
    public function getSlug(): string
    {
        return 'content.orphaned_articles';
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
     * Perform the health check to identify published articles not linked from any menu.
     *
     * This check finds published articles that are not referenced by any published menu
     * items. Uses a NOT EXISTS subquery to efficiently check for menu links containing
     * the article ID. Orphaned articles can only be reached via direct URL, search, or
     * links within other content.
     *
     * @return HealthCheckResult Warning if >10 orphaned, good if ≤10, includes count info
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();

        // Count published articles that have no menu items linking to them.
        // The NOT EXISTS subquery checks for any published menu item with a link
        // parameter containing this article's ID (e.g., 'id=123').
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__content', 'a'))
            ->where($database->quoteName('a.state') . ' = 1')
            ->where(
                'NOT EXISTS (SELECT 1 FROM ' . $database->quoteName('#__menu') . ' m WHERE ' .
                $database->quoteName('m.published') . ' = 1 AND ' .
                $database->quoteName('m.link') . ' LIKE CONCAT(' . $database->quote(
                    '%id=',
                ) . ', ' . $database->quoteName('a.id') . ', ' . $database->quote('%') . '))',
            );

        try {
            $database->setQuery($query);
            $count = (int) $database->loadResult();
        } catch (\Exception $exception) {
            return $this->warning('Unable to check for orphaned articles: ' . $exception->getMessage());
        }

        if ($count > 10) {
            return $this->warning(
                sprintf(
                    '%d published articles are not linked from any menu. Consider creating menu items or unpublishing unused content.',
                    $count,
                ),
            );
        }

        if ($count > 0) {
            return $this->good(
                sprintf('%d published articles are not linked from menus. This may be intentional.', $count),
            );
        }

        return $this->good('All published articles are linked from menus.');
    }
}
