<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Template Override Health Check
 *
 * This check examines Joomla's template override tracking system to identify
 * overrides that may need updating after core or extension updates.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Template overrides allow customization of core output, but when the original
 * files are updated (e.g., during a Joomla update), the overrides may become
 * outdated. Outdated overrides can cause missing features, security issues,
 * or visual bugs. Joomla tracks overrides and flags those that may need review.
 *
 * RESULT MEANINGS:
 *
 * GOOD: All tracked template overrides are up to date, or template override
 * tracking is not available on this Joomla version.
 *
 * WARNING: One or more template overrides may be outdated following core changes.
 * Review these overrides and update them to incorporate changes from the original files.
 * The check lists which templates and files need attention.
 *
 * CRITICAL: This check does not return CRITICAL status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class OverrideCheck extends AbstractHealthCheck
{
    /**
     * Maximum number of override details to show in the warning message.
     * Prevents excessively long output for sites with many outdated overrides.
     */
    private const MAX_DETAILS_TO_SHOW = 10;

    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check slug in the format 'category.check_name'
     */
    public function getSlug(): string
    {
        return 'extensions.overrides';
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

    public function getDocsUrl(): string
    {
        return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/core/src/Checks/Extensions/OverrideCheck.php';
    }

    /**
     * Perform the template override check.
     *
     * Examines Joomla's template override tracking system to identify overrides
     * that may be outdated. Template overrides allow sites to customize the output
     * of components, modules, and plugins by placing modified view files in the
     * template directory.
     *
     * Override tracking system (Joomla 4+):
     * - #__template_overrides table stores MD5 hashes of original files
     * - When original files change (core update), hashes don't match
     * - state = 0: Override may be outdated and needs review
     * - state = 1: Override is current/up to date
     *
     * This table may not exist on all Joomla installations (feature added in Joomla 4).
     *
     * @return HealthCheckResult The result with status and description
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();

        // Check if template_overrides table exists (Joomla 4+ feature)
        $tables = $database->getTableList();
        $prefix = $database->getPrefix();
        $overridesTable = $prefix . 'template_overrides';

        if (! \in_array($overridesTable, $tables, true)) {
            return $this->good('Template override tracking is not available.');
        }

        // Get details of overrides that may need updating
        // state = 0 indicates the original file has changed since the override was created
        $query = $database->getQuery(true)
            ->select([
                $database->quoteName('template'),
                $database->quoteName('hash_id'),
                $database->quoteName('action'),
                $database->quoteName('modified_date'),
                $database->quoteName('client_id'),
            ])
            ->from($database->quoteName('#__template_overrides'))
            ->where($database->quoteName('state') . ' = 0')
            ->order($database->quoteName('modified_date') . ' DESC');

        $outdatedOverrides = $database->setQuery($query)
            ->loadObjectList();
        $outdatedCount = \count($outdatedOverrides);

        if ($outdatedCount > 0) {
            $details = $this->formatOverrideDetails($outdatedOverrides);

            return $this->warning($details);
        }

        // Get total count of tracked overrides for context
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__template_overrides'));

        $totalOverrides = (int) $database->setQuery($query)
            ->loadResult();

        return $this->good(sprintf('%d template override(s) tracked, all up to date.', $totalOverrides));
    }

    /**
     * Format the outdated override details into a readable message.
     *
     * Groups overrides by template and provides file paths that need review.
     * The hash_id field contains a base64-encoded path to the override file
     * relative to the template's html/ directory.
     *
     * @param array<object{template: string, hash_id: string, action: string, modified_date: string, client_id: int}> $overrides
     *
     * @return string Formatted message with override details
     */
    private function formatOverrideDetails(array $overrides): string
    {
        $count = \count($overrides);

        // Group overrides by template for cleaner output
        $byTemplate = [];

        foreach ($overrides as $override) {
            $template = $override->template;
            $clientLabel = (int) $override->client_id === 1 ? 'admin' : 'site';
            $templateKey = $template . ' (' . $clientLabel . ')';

            // Decode the hash_id to get the relative file path
            // hash_id is base64-encoded by Joomla, so decode should always succeed
            $filePath = base64_decode($override->hash_id, true);

            if ($filePath === false) {
                // Skip invalid entries (shouldn't happen with valid Joomla data)
                continue;
            }

            if (! isset($byTemplate[$templateKey])) {
                $byTemplate[$templateKey] = [];
            }

            $byTemplate[$templateKey][] = $filePath;
        }

        // Build the message
        $message = sprintf('%d template override(s) may need updating after core/extension changes. ', $count);
        $message .= 'Review these in System → Templates → Templates: ';

        $templateDetails = [];
        $shownCount = 0;

        foreach ($byTemplate as $templateName => $files) {
            if ($shownCount >= self::MAX_DETAILS_TO_SHOW) {
                break;
            }

            $fileList = [];

            foreach ($files as $file) {
                if ($shownCount >= self::MAX_DETAILS_TO_SHOW) {
                    break;
                }

                // Clean up the file path for display (remove leading slash if present)
                $fileList[] = ltrim($file, '/');
                ++$shownCount;
            }

            $templateDetails[] = $templateName . ': ' . implode(', ', $fileList);
        }

        $message .= implode('; ', $templateDetails);

        // Add note if we truncated the list
        if ($count > self::MAX_DETAILS_TO_SHOW) {
            $message .= sprintf(' (and %d more)', $count - self::MAX_DETAILS_TO_SHOW);
        }

        return $message;
    }
}
