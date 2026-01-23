<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Articles in Unpublished Categories Health Check
 *
 * This check identifies published articles that exist in unpublished categories,
 * creating a content visibility conflict where articles appear available but are
 * actually inaccessible to site visitors.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * When an article is published but its category is unpublished, the article
 * becomes invisible to visitors even though editors may think it's live. This
 * creates several problems:
 * - Content appears "missing" to users even though it exists
 * - Editors don't realize their published content isn't visible
 * - Internal links to these articles return 404 or access denied errors
 * - SEO impact from dead links and missing content
 * - Confusion when content doesn't appear in category listings
 *
 * This situation typically occurs when:
 * - A category is unpublished for maintenance but articles weren't unpublished
 * - Category access levels change without considering child content
 * - Content is imported or migrated without proper category states
 *
 * RESULT MEANINGS:
 *
 * GOOD: All published articles are in published categories.
 *       No content visibility conflicts exist.
 *
 * WARNING: One or more published articles exist in unpublished categories.
 *          These articles are invisible to visitors. Either publish the
 *          category or unpublish the affected articles.
 *
 * CRITICAL: This check does not return critical status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Content;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class UnpublishedCategoryArticlesCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this health check.
     *
     * @return string The check slug in format 'content.unpublished_category_articles'
     */
    public function getSlug(): string
    {
        return 'content.unpublished_category_articles';
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
     * Perform the health check to find published articles in unpublished categories.
     *
     * This check identifies a problematic content state where articles are marked as
     * published (state = 1) but exist in unpublished categories (published = 0). This
     * creates a visibility conflict: editors may think the content is live, but visitors
     * cannot see it. The check uses an INNER JOIN to match articles with their categories
     * and filters for this mismatched state.
     *
     * @return HealthCheckResult Warning if any mismatched articles found, good otherwise
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();

        // Find published articles in unpublished categories.
        // This represents a content visibility conflict where articles appear to be
        // published but are actually invisible to visitors due to their parent category state.
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__content', 'a'))
            ->join(
                'INNER',
                $database->quoteName('#__categories', 'c') . ' ON ' . $database->quoteName(
                    'a.catid',
                ) . ' = ' . $database->quoteName('c.id'),
            )
            ->where($database->quoteName('a.state') . ' = 1') // Published articles
            ->where($database->quoteName('c.published') . ' = 0'); // In unpublished categories

        try {
            $database->setQuery($query);
            $count = (int) $database->loadResult();
        } catch (\Exception) {
            return $this->warning('Unable to check for articles in unpublished categories.');
        }

        if ($count > 0) {
            return $this->warning(
                sprintf(
                    '%d published %s in unpublished categories and %s invisible to visitors. Either publish the categories or unpublish the articles.',
                    $count,
                    $count === 1 ? 'article is' : 'articles are',
                    $count === 1 ? 'is' : 'are',
                ),
            );
        }

        return $this->good('All published articles are in published categories.');
    }
}
