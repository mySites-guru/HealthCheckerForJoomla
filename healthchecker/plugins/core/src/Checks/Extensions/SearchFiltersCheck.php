<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Smart Search Filters Health Check
 *
 * Verifies that Smart Search (Finder) filters used in menu items have content
 * map nodes selected. A filter with zero maps always returns empty search
 * results, which is confusing for site visitors.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * When a menu item links to com_finder with a specific filter_id, and that
 * filter has no content maps selected (map_count = 0), search results will
 * always be empty. This is a common misconfiguration that is hard to spot
 * because the page loads without errors — it just shows no results.
 *
 * RESULT MEANINGS:
 *
 * GOOD: Smart Search is disabled, no filters exist, or all filters
 * referenced by menu items have content maps configured.
 *
 * WARNING: One or more published Smart Search filters have no content maps
 * selected (map_count = 0), or a menu item references a filter that does
 * not exist or is unpublished. Edit the affected filters and select at
 * least one content map node.
 *
 * CRITICAL: This check does not return CRITICAL status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions;

use Joomla\CMS\Language\Text;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;

\defined('_JEXEC') || die;

final class SearchFiltersCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check slug in the format 'category.check_name'
     */
    public function getSlug(): string
    {
        return 'extensions.search_filters';
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

    public function getDocsUrl(?HealthStatus $healthStatus = null): string
    {
        return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/core/src/Checks/Extensions/SearchFiltersCheck.php';
    }

    public function getActionUrl(?HealthStatus $healthStatus = null): ?string
    {
        if ($healthStatus === HealthStatus::Warning) {
            return '/administrator/index.php?option=com_finder&view=filters';
        }

        return null;
    }

    /**
     * Perform the Smart Search filters check.
     *
     * Checks whether Smart Search filters referenced by published menu items
     * have content maps selected. A filter with map_count = 0 means no content
     * types are included, so search always returns empty results.
     *
     * Also detects menu items that reference non-existent or unpublished filters.
     *
     * @return HealthCheckResult The result with status and description
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();

        // Check if Smart Search (Finder) component is enabled
        $query = $database->getQuery(true)
            ->select('enabled')
            ->from($database->quoteName('#__extensions'))
            ->where($database->quoteName('element') . ' = ' . $database->quote('com_finder'))
            ->where($database->quoteName('type') . ' = ' . $database->quote('component'));

        $finderEnabled = (int) $database->setQuery($query)
            ->loadResult();

        if ($finderEnabled === 0) {
            return $this->good(Text::_('COM_HEALTHCHECKER_CHECK_EXTENSIONS_SEARCH_FILTERS_GOOD_DISABLED'));
        }

        // Find all published frontend menu items for com_finder that reference a filter
        $query = $database->getQuery(true)
            ->select([$database->quoteName('id'), $database->quoteName('title'), $database->quoteName('params')])
            ->from($database->quoteName('#__menu'))
            ->where($database->quoteName('link') . ' LIKE ' . $database->quote('%option=com_finder%'))
            ->where($database->quoteName('published') . ' = 1')
            ->where($database->quoteName('client_id') . ' = 0');

        $menuItems = $database->setQuery($query)
            ->loadObjectList();

        // Collect unique filter IDs referenced by published menu items
        $referencedFilterIds = [];

        foreach ($menuItems as $menuItem) {
            $params = json_decode($menuItem->params ?? '{}', true);
            $filterId = (int) ($params['f'] ?? $params['filter_id'] ?? 0);

            if ($filterId > 0) {
                $referencedFilterIds[$filterId] = $menuItem->title;
            }
        }

        // If no menu items reference filters, nothing to check
        if ($referencedFilterIds === []) {
            return $this->good(Text::_('COM_HEALTHCHECKER_CHECK_EXTENSIONS_SEARCH_FILTERS_GOOD_NO_FILTERS'));
        }

        // Load published Smart Search filters
        $query = $database->getQuery(true)
            ->select([
                $database->quoteName('filter_id'),
                $database->quoteName('title'),
                $database->quoteName('map_count'),
            ])
            ->from($database->quoteName('#__finder_filters'))
            ->where($database->quoteName('state') . ' = 1');

        $filterList = $database->setQuery($query)
            ->loadObjectList();

        // Key filters by filter_id for quick lookup
        $filters = [];

        foreach ($filterList as $filter) {
            $filters[(int) $filter->filter_id] = $filter;
        }

        $emptyFilters = [];
        $missingFilters = [];

        foreach ($referencedFilterIds as $filterId => $menuTitle) {
            if (! isset($filters[$filterId])) {
                $missingFilters[] = Text::sprintf(
                    'COM_HEALTHCHECKER_CHECK_EXTENSIONS_SEARCH_FILTERS_MISSING_ITEM',
                    $menuTitle,
                    $filterId,
                );

                continue;
            }

            if ((int) $filters[$filterId]->map_count === 0) {
                $emptyFilters[] = $filters[$filterId]->title;
            }
        }

        if ($emptyFilters === [] && $missingFilters === []) {
            return $this->good(
                Text::sprintf(
                    'COM_HEALTHCHECKER_CHECK_EXTENSIONS_SEARCH_FILTERS_GOOD',
                    \count($referencedFilterIds),
                ),
            );
        }

        $messages = [];

        if ($emptyFilters !== []) {
            $messages[] = Text::sprintf(
                'COM_HEALTHCHECKER_CHECK_EXTENSIONS_SEARCH_FILTERS_WARNING_EMPTY',
                implode(', ', $emptyFilters),
            );
        }

        if ($missingFilters !== []) {
            $messages[] = Text::sprintf(
                'COM_HEALTHCHECKER_CHECK_EXTENSIONS_SEARCH_FILTERS_WARNING_MISSING',
                implode(', ', $missingFilters),
            );
        }

        return $this->warning(implode(' ', $messages));
    }
}
