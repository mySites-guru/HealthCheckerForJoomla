<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Database Table Status Health Check
 *
 * This check examines all Joomla database tables for corruption or structural
 * issues and reports the total database size.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Corrupted database tables can cause:
 * - Data loss or incomplete content display
 * - Errors when saving or updating content
 * - Site crashes or white screens
 * - Failed extension operations
 * Detecting corruption early allows for repair before data is permanently lost.
 *
 * RESULT MEANINGS:
 *
 * GOOD: All tables are healthy with no signs of corruption. The total table
 * count and combined data/index size are reported for reference.
 *
 * WARNING: Not applicable for this check - tables are either healthy or corrupted.
 *
 * CRITICAL: One or more tables appear to be corrupted. The table comment shows
 * "Corrupt" or the engine is NULL. Run REPAIR TABLE on the affected tables
 * immediately, and consider restoring from a backup if repair fails.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Database;

use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class TableStatusCheck extends AbstractHealthCheck
{
    /**
     * Get the unique identifier for this health check.
     *
     * @return string The check slug in format "database.table_status"
     */
    public function getSlug(): string
    {
        return 'database.table_status';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category slug "database"
     */
    public function getCategory(): string
    {
        return 'database';
    }

    /**
     * Perform the table status and corruption health check.
     *
     * Examines all Joomla database tables for corruption or structural issues by
     * running SHOW TABLE STATUS and analyzing the results. Also calculates total
     * database size for informational purposes.
     *
     * The check looks for corruption indicators:
     * - Comment field set to "Corrupt" (database detected corruption)
     * - Engine field is NULL (table definition is broken)
     *
     * Additionally calculates:
     * - Total data size across all tables
     * - Total index size across all tables
     * - Combined size formatted in human-readable units
     *
     * @return HealthCheckResult Critical if database unavailable or tables are corrupted,
     *                           good if all tables are healthy (includes table count and size)
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        $prefix = Factory::getApplication()->get('dbprefix');

        // SHOW TABLE STATUS returns detailed information about each table including:
        // - Name, Engine, Comment (contains "Corrupt" if corrupted)
        // - Data_length, Index_length (sizes in bytes)
        $query = 'SHOW TABLE STATUS LIKE ' . $database->quote($prefix . '%');
        $tables = $database->setQuery($query)
            ->loadObjectList();

        $corruptedTables = [];
        $totalDataSize = 0;
        $totalIndexSize = 0;

        foreach ($tables as $table) {
            // Check for corruption indicators
            if ($table->Comment === 'Corrupt' || $table->Engine === null) {
                $corruptedTables[] = $table->Name;
            }

            // Accumulate sizes for reporting (even if corrupted, to show scope)
            $totalDataSize += (int) ($table->Data_length ?? 0);
            $totalIndexSize += (int) ($table->Index_length ?? 0);
        }

        if ($corruptedTables !== []) {
            return $this->critical(
                sprintf(
                    '%d table(s) appear corrupted: %s. Run REPAIR TABLE.',
                    count($corruptedTables),
                    implode(', ', $corruptedTables),
                ),
            );
        }

        $totalSize = $this->formatBytes($totalDataSize + $totalIndexSize);

        return $this->good(sprintf('%d tables checked, total size: %s.', count($tables), $totalSize));
    }

    /**
     * Format bytes into human-readable size with appropriate units.
     *
     * Converts raw byte count into KB, MB, or GB as appropriate, with 2 decimal
     * places of precision. Stops at GB even for larger sizes.
     *
     * @param int $bytes The number of bytes to format
     *
     * @return string Formatted size string like "15.23 MB" or "2.5 GB"
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        // Divide by 1024 repeatedly until we reach the appropriate unit
        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
