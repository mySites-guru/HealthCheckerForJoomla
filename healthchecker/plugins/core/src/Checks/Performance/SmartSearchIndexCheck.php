<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Smart Search Index Health Check
 *
 * This check verifies the status of Joomla's Smart Search (Finder) index,
 * ensuring it contains content when the component is enabled.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * If Smart Search is enabled but the index is empty, site visitors will get
 * no results when searching. An empty index indicates the indexer has never
 * been run or has failed. Running the indexer populates the search index
 * with content from articles, categories, contacts, and other content types.
 *
 * RESULT MEANINGS:
 *
 * GOOD: Smart Search is either disabled (intentionally not used) or enabled
 * with a populated index containing indexed content items.
 *
 * WARNING: Smart Search is enabled but the index is empty. Navigate to
 * Components > Smart Search > Index and run the indexer to populate the
 * search index with your site's content.
 *
 * CRITICAL: This check does not return CRITICAL status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Performance;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class SmartSearchIndexCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check identifier in format 'category.check_name'
     */
    public function getSlug(): string
    {
        return 'performance.smart_search_index';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category slug
     */
    public function getCategory(): string
    {
        return 'performance';
    }

    /**
     * Perform the Smart Search (Finder) index status check.
     *
     * Verifies that if Smart Search is enabled, the search index contains content.
     * An empty index means visitors will receive no search results, even though
     * the component is enabled.
     *
     * Performance considerations:
     * - Smart Search pre-indexes content for fast searching
     * - Empty index indicates indexer has never run or failed
     * - Index should be maintained regularly for new/updated content
     * - Large indexes (10,000+ items) may need periodic optimization
     *
     * Database tables checked:
     * - #__extensions: To verify com_finder is enabled
     * - #__finder_links: To count indexed content items
     *
     * @return HealthCheckResult Returns GOOD if disabled or indexed,
     *                           WARNING if enabled but empty index
     */
    /**
     * Perform the Smart Search Index health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // Check if Smart Search component (com_finder) is enabled
        // We only care about index status if the component is actually in use
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__extensions'))
            ->where($database->quoteName('element') . ' = ' . $database->quote('com_finder'))
            ->where($database->quoteName('enabled') . ' = 1');

        $isEnabled = (int) $database->setQuery($query)
            ->loadResult() > 0;

        // Component not enabled - no need to check index
        if (! $isEnabled) {
            return $this->good('Smart Search is not enabled.');
        }

        // Component is enabled, verify the index has content
        // The #__finder_links table stores the main index entries
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__finder_links'));

        $indexedItems = (int) $database->setQuery($query)
            ->loadResult();

        // Index is empty - search will return no results for users
        if ($indexedItems === 0) {
            return $this->warning('Smart Search is enabled but the index is empty. Run the indexer.');
        }

        // Index contains content - search functionality is working
        return $this->good(sprintf('Smart Search index contains %d items.', $indexedItems));
    }
}
