<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Database Size Health Check
 *
 * This check monitors the total size of all Joomla database tables (data + indexes)
 * and identifies the largest tables that may need attention.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Large databases can cause operational issues:
 * - Slower backups that may timeout or consume excessive resources
 * - Longer restoration times during disaster recovery
 * - Increased hosting costs for storage and I/O
 * - Slower queries if large tables lack proper indexes
 * Monitoring size helps plan for maintenance and scaling.
 *
 * RESULT MEANINGS:
 *
 * GOOD: Database size is below 1GB. This is a healthy size for most Joomla
 * sites and should not cause operational issues.
 *
 * WARNING: Database size is between 1GB and 5GB. Monitor growth and consider:
 * - Archiving old content (action logs, redirects, sessions)
 * - Optimizing tables to reclaim space
 * - Reviewing the largest tables for cleanup opportunities
 *
 * CRITICAL: Database size exceeds 5GB. This may cause backup failures,
 * performance issues, and increased hosting costs. Immediate attention
 * recommended to archive old data and optimize storage.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Database;

use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class SizeCheck extends AbstractHealthCheck
{
    /**
     * Warning threshold in megabytes (1GB).
     *
     * Databases exceeding this size should be monitored for growth.
     *
     * @var int
     */
    private const WARNING_SIZE_MB = 1024; // 1 GB

    /**
     * Critical threshold in megabytes (5GB).
     *
     * Databases exceeding this size may cause operational issues.
     *
     * @var int
     */
    private const CRITICAL_SIZE_MB = 5120; // 5 GB

    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check slug in format 'database.size'
     */
    public function getSlug(): string
    {
        return 'database.size';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category slug 'database'
     */
    public function getCategory(): string
    {
        return 'database';
    }

    /**
     * Perform the database size health check.
     *
     * Monitors the total size of all Joomla database tables (data + indexes)
     * and identifies the largest tables that may need attention.
     *
     * Check logic:
     * 1. Query SHOW TABLE STATUS for all tables with Joomla prefix
     * 2. Sum Data_length and Index_length for each table
     * 3. Identify the 3 largest tables for reporting
     * 4. If total size >= 5GB: CRITICAL - immediate attention needed
     * 5. If total size >= 1GB: WARNING - monitor and consider cleanup
     * 6. If total size < 1GB: GOOD - healthy database size
     *
     * @return HealthCheckResult The result with appropriate status and message
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        $prefix = Factory::getApplication()->get('dbprefix');

        // Query table status for all Joomla tables
        $query = 'SHOW TABLE STATUS LIKE ' . $database->quote($prefix . '%');
        $tables = $database->setQuery($query)
            ->loadObjectList();

        $totalDataSize = 0;
        $totalIndexSize = 0;
        $tableCount = count($tables);
        $largestTables = [];

        // Calculate total size and track largest tables
        foreach ($tables as $table) {
            $dataSize = (int) ($table->Data_length ?? 0);
            $indexSize = (int) ($table->Index_length ?? 0);
            $tableSize = $dataSize + $indexSize;

            $totalDataSize += $dataSize;
            $totalIndexSize += $indexSize;

            $largestTables[$table->Name] = $tableSize;
        }

        // Sort tables by size (largest first) and keep top 3
        arsort($largestTables);
        $largestTables = array_slice($largestTables, 0, 3, true);

        $totalSizeBytes = $totalDataSize + $totalIndexSize;
        $totalSizeMB = $totalSizeBytes / (1024 * 1024);
        $totalSizeFormatted = $this->formatBytes($totalSizeBytes);

        // Format largest table info for display
        $largestTableInfo = [];
        foreach ($largestTables as $name => $size) {
            $largestTableInfo[] = sprintf('%s (%s)', $name, $this->formatBytes($size));
        }

        // Evaluate against thresholds
        if ($totalSizeMB >= self::CRITICAL_SIZE_MB) {
            return $this->critical(
                sprintf(
                    'Database is very large: %s across %d tables. Largest: %s. Consider archiving old data.',
                    $totalSizeFormatted,
                    $tableCount,
                    implode(', ', $largestTableInfo),
                ),
            );
        }

        if ($totalSizeMB >= self::WARNING_SIZE_MB) {
            return $this->warning(
                sprintf(
                    'Database is getting large: %s across %d tables. Largest: %s. Monitor growth.',
                    $totalSizeFormatted,
                    $tableCount,
                    implode(', ', $largestTableInfo),
                ),
            );
        }

        return $this->good(
            sprintf('Database size is healthy: %s across %d tables.', $totalSizeFormatted, $tableCount),
        );
    }

    /**
     * Format bytes into human-readable units (B, KB, MB, GB).
     *
     * Converts a byte count into the most appropriate unit by
     * progressively dividing by 1024 until the value is less than 1024
     * or the largest unit is reached.
     *
     * @param int $bytes The number of bytes to format
     *
     * @return string Formatted string with value and unit (e.g., "1.5 MB")
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
