<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Unused Modules Health Check
 *
 * This check identifies published modules that have no menu assignments,
 * meaning they will never be displayed to visitors on any page.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Published modules consume resources during page rendering even if they are
 * not displayed. Modules without menu assignments may have been misconfigured
 * or forgotten after content changes. Identifying these modules helps you
 * either assign them to pages where they should appear or unpublish them
 * to improve site performance.
 *
 * RESULT MEANINGS:
 *
 * GOOD: All published modules have menu assignments and will be displayed
 * on at least one page.
 *
 * WARNING: One or more published modules have no menu assignments. These
 * modules are published but will never be shown. Consider assigning them
 * to pages or unpublishing them. More than 5 unused modules suggests
 * module management needs attention.
 *
 * CRITICAL: This check does not return CRITICAL status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die();

final class UnusedModulesCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check slug in the format 'category.check_name'
     */
    public function getSlug(): string
    {
        return 'extensions.unused_modules';
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
     * Perform the unused modules check.
     *
     * Identifies published modules that have no menu assignments. These modules
     * consume resources during page rendering but will never be displayed to visitors.
     *
     * Module assignment logic in Joomla:
     * - menuid = -1: Display on all pages (NOT unused)
     * - menuid = 0: No assignment (unused)
     * - menuid > 0: Specific menu item assignment
     * - No entries in #__modules_menu: Completely unassigned (unused)
     *
     * We check for two types of unused modules:
     * 1. Modules with no entries at all in #__modules_menu (completely unassigned)
     * 2. Modules with only menuid = 0 entries (explicitly not assigned to any pages)
     *
     * @return HealthCheckResult The result with status and description
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // Find published modules with no menu assignments or only menuid = 0
        // client_id = 0 restricts to site (frontend) modules
        // A module with no entries in #__modules_menu or only menuid = 0 is not assigned to any menus
        $query = $database
            ->getQuery(true)
            ->select(['m.id', 'm.title'])
            ->from($database->quoteName('#__modules', 'm'))
            ->leftJoin(
                $database->quoteName('#__modules_menu', 'mm') .
                    ' ON ' .
                    $database->quoteName('m.id') .
                    ' = ' .
                    $database->quoteName('mm.moduleid'),
            )
            ->where($database->quoteName('m.client_id') . ' = 0')
            ->where($database->quoteName('m.published') . ' = 1')
            ->group($database->quoteName('m.id'))
            ->having(
                'COUNT(' .
                    $database->quoteName('mm.moduleid') .
                    ') = 0 OR MAX(' .
                    $database->quoteName('mm.menuid') .
                    ') = 0',
            );

        $unusedModules = $database->setQuery($query)
            ->loadObjectList();
        $unusedCount = \count($unusedModules);

        // Double-check for modules with absolutely no menu assignments
        // Note: menuid=-1 means "all pages" so those modules ARE used
        $query = $database
            ->getQuery(true)
            ->select(['m.id', 'm.title'])
            ->from($database->quoteName('#__modules', 'm'))
            ->leftJoin(
                $database->quoteName('#__modules_menu', 'mm') .
                    ' ON ' .
                    $database->quoteName('m.id') .
                    ' = ' .
                    $database->quoteName('mm.moduleid'),
            )
            ->where($database->quoteName('m.client_id') . ' = 0')
            ->where($database->quoteName('m.published') . ' = 1')
            ->group($database->quoteName('m.id'))
            ->having('COUNT(' . $database->quoteName('mm.moduleid') . ') = 0');

        $noPageModules = $database->setQuery($query)
            ->loadObjectList();
        $noPageCount = \count($noPageModules);

        $totalUnused = $unusedCount + $noPageCount;

        // More than 5 unused modules suggests module management needs attention
        if ($totalUnused > 5) {
            return $this->warning(
                sprintf(
                    '%d published module(s) have no menu assignment. Consider unpublishing or assigning them to pages. %s',
                    $totalUnused,
                    implode(', ', $unusedModules),
                ),
            );
        }

        if ($totalUnused > 0) {
            return $this->warning(sprintf('%d published module(s) have no menu assignment.', $totalUnused));
        }

        return $this->good('All published modules have menu assignments.');
    }
}
