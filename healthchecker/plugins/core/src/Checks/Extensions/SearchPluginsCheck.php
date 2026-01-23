<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Search Plugins Health Check
 *
 * This check verifies the configuration and indexing status of Joomla's Smart Search
 * (Finder) component and its associated content indexing plugins.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Smart Search provides powerful site search functionality but requires proper
 * configuration. The component needs enabled Finder plugins to know which content
 * types to index, and the indexer must be run to populate the search index.
 * Without these, site search will return no results or incomplete results.
 *
 * RESULT MEANINGS:
 *
 * GOOD: Smart Search is either disabled (intentionally not used) or properly
 * configured with enabled plugins and populated index.
 *
 * WARNING: Smart Search configuration issues detected. Either the component is
 * enabled but no Finder plugins are active (no content types will be indexed),
 * or plugins are active but the index is empty (indexer needs to be run).
 *
 * CRITICAL: This check does not return CRITICAL status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class SearchPluginsCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check slug in the format 'category.check_name'
     */
    public function getSlug(): string
    {
        return 'extensions.search_plugins';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category slug
     */
    public function getCategory(): string
    {
        return 'extensions';
    }

    /**
     * Perform the search plugins check.
     *
     * Verifies the configuration and indexing status of Joomla's Smart Search (Finder)
     * component. Smart Search provides powerful site search functionality but requires:
     * 1. The component to be enabled
     * 2. At least one Finder plugin enabled (to know what content types to index)
     * 3. The indexer to be run to populate the search index
     *
     * Smart Search architecture:
     * - Component (com_finder): Core search functionality
     * - Plugins (folder = 'finder'): Content type indexers (articles, contacts, etc.)
     * - Index table (#__finder_links): Stores indexed content items
     *
     * Without enabled plugins, Smart Search won't know what content to index.
     * Without running the indexer, search will return no results.
     *
     * @return HealthCheckResult The result with status and description
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // Check if Smart Search (Finder) component is enabled
        // element = 'com_finder' is the component name
        $query = $database->getQuery(true)
            ->select('enabled')
            ->from($database->quoteName('#__extensions'))
            ->where($database->quoteName('element') . ' = ' . $database->quote('com_finder'))
            ->where($database->quoteName('type') . ' = ' . $database->quote('component'));

        $finderEnabled = (int) $database->setQuery($query)
            ->loadResult();

        // Count enabled Finder plugins (content type indexers)
        // folder = 'finder' filters to Smart Search indexer plugins
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__extensions'))
            ->where($database->quoteName('type') . ' = ' . $database->quote('plugin'))
            ->where($database->quoteName('folder') . ' = ' . $database->quote('finder'))
            ->where($database->quoteName('enabled') . ' = 1');

        $finderPluginsEnabled = (int) $database->setQuery($query)
            ->loadResult();

        // Get total Finder plugins available (for context)
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__extensions'))
            ->where($database->quoteName('type') . ' = ' . $database->quote('plugin'))
            ->where($database->quoteName('folder') . ' = ' . $database->quote('finder'));

        $finderPluginsTotal = (int) $database->setQuery($query)
            ->loadResult();

        // If Smart Search is disabled, this is intentional - not a problem
        if ($finderEnabled === 0) {
            return $this->good('Smart Search component is not enabled.');
        }

        // Component enabled but no plugins - misconfiguration
        if ($finderPluginsEnabled === 0) {
            return $this->warning(
                'Smart Search is enabled but no search plugins are active. Enable content plugins to index your content.',
            );
        }

        // Check if the search index contains any content
        // #__finder_links stores indexed content items
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__finder_links'));

        $indexedItems = (int) $database->setQuery($query)
            ->loadResult();

        // Plugins enabled but index empty - indexer needs to be run
        if ($indexedItems === 0) {
            return $this->warning(
                sprintf(
                    '%d of %d Smart Search plugin(s) enabled, but index is empty. Run the indexer.',
                    $finderPluginsEnabled,
                    $finderPluginsTotal,
                ),
            );
        }

        // Everything properly configured and indexed
        return $this->good(
            sprintf(
                '%d of %d Smart Search plugin(s) enabled, %d items indexed.',
                $finderPluginsEnabled,
                $finderPluginsTotal,
                $indexedItems,
            ),
        );
    }
}
