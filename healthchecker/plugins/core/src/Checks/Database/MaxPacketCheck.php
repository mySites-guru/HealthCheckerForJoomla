<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Database Max Allowed Packet Health Check
 *
 * This check verifies that the MySQL/MariaDB max_allowed_packet setting is
 * sufficient for Joomla operations (minimum 1MB, recommended 16MB).
 *
 * WHY THIS CHECK IS IMPORTANT:
 * The max_allowed_packet setting limits the maximum size of a single SQL
 * query or result. When this limit is too low:
 * - Large articles or media metadata cannot be saved
 * - Database backups may fail or become corrupted
 * - Bulk operations (imports, updates) may fail
 * - Large BLOB or TEXT fields may be truncated
 *
 * RESULT MEANINGS:
 *
 * GOOD: The max_allowed_packet is at least 16MB, which is sufficient for
 * most Joomla operations including large content and backup operations.
 *
 * WARNING: The max_allowed_packet is between 1MB and 16MB. While basic
 * operations will work, large content saves or backups may fail. Consider
 * increasing this value in your MySQL/MariaDB configuration.
 *
 * CRITICAL: The max_allowed_packet is below 1MB, which is too small for
 * reliable Joomla operation. Increase this value immediately.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Database;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class MaxPacketCheck extends AbstractHealthCheck
{
    /**
     * Minimum acceptable max_allowed_packet size in bytes (1MB).
     *
     * Below this value, basic Joomla operations may fail.
     *
     * @var int
     */
    private const MINIMUM_BYTES = 1024 * 1024; // 1M

    /**
     * Recommended max_allowed_packet size in bytes (16MB).
     *
     * This size ensures reliable operation for large content and backups.
     *
     * @var int
     */
    private const RECOMMENDED_BYTES = 16 * 1024 * 1024; // 16M

    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check slug in format 'database.max_packet'
     */
    public function getSlug(): string
    {
        return 'database.max_packet';
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
     * Perform the max_allowed_packet health check.
     *
     * Verifies that the MySQL/MariaDB max_allowed_packet setting is
     * sufficient for Joomla operations (minimum 1MB, recommended 16MB).
     *
     * Check logic:
     * 1. Query the max_allowed_packet system variable
     * 2. If < 1MB: CRITICAL - too small for reliable operation
     * 3. If >= 1MB but < 16MB: WARNING - may cause issues with large content
     * 4. If >= 16MB: GOOD - sufficient for most operations
     *
     * The max_allowed_packet setting limits the maximum size of a single
     * SQL query or result, affecting large content saves and backup operations.
     *
     * @return HealthCheckResult The result with appropriate status and message
     */
    /**
     * Perform the Max Packet health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // Query the current max_allowed_packet setting
        $query = "SHOW VARIABLES LIKE 'max_allowed_packet'";
        $result = $database->setQuery($query)
            ->loadObject();

        $maxPacket = (int) ($result->Value ?? 0);

        // Check against minimum threshold (1MB)
        if ($maxPacket < self::MINIMUM_BYTES) {
            return $this->critical(
                sprintf(
                    'max_allowed_packet (%s) is too small. Minimum 1M required.',
                    $this->formatBytes($maxPacket),
                ),
            );
        }

        // Check against recommended threshold (16MB)
        if ($maxPacket < self::RECOMMENDED_BYTES) {
            return $this->warning(
                sprintf('max_allowed_packet (%s) is below recommended 16M.', $this->formatBytes($maxPacket)),
            );
        }

        return $this->good(sprintf('max_allowed_packet is %s.', $this->formatBytes($maxPacket)));
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
     * @return string Formatted string with value and unit (e.g., "16 MB")
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
