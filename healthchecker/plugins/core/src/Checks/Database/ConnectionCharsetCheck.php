<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Database Connection Charset Health Check
 *
 * This check verifies that the database connection is using the utf8mb4 character
 * set, which provides full Unicode support including emoji and special characters.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * The connection charset determines how text data is transmitted between PHP and
 * the database. Using utf8mb4 ensures:
 * - Full Unicode support including emoji, Asian characters, and special symbols
 * - Consistent character encoding across all data operations
 * - Prevention of character corruption or mojibake (garbled text)
 * - Proper sorting and comparison of international text
 *
 * RESULT MEANINGS:
 *
 * GOOD: The connection charset is utf8mb4, providing full Unicode support.
 * All characters including emoji will be stored and retrieved correctly.
 *
 * WARNING: The connection charset is utf8 or utf8mb3, which supports most
 * characters but not 4-byte Unicode (emoji, some Asian characters). Or the
 * charset is a non-UTF-8 encoding which may cause character corruption.
 *
 * CRITICAL: The database connection is not available to check the charset.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Database;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class ConnectionCharsetCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check slug in format 'database.connection_charset'
     */
    public function getSlug(): string
    {
        return 'database.connection_charset';
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
     * Perform the database connection charset health check.
     *
     * Verifies that the MySQL/MariaDB connection charset is set to utf8mb4
     * for full Unicode support including emoji and special characters.
     *
     * Check logic:
     * 1. Query the character_set_connection system variable
     * 2. If utf8mb4: GOOD - full Unicode support enabled
     * 3. If utf8/utf8mb3: WARNING - limited Unicode (no 4-byte chars)
     * 4. If other charset: WARNING - non-UTF-8 encoding may cause issues
     *
     * @return HealthCheckResult The result with appropriate status and message
     */
    /**
     * Perform the Connection Charset health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // Query the current connection charset setting
        $query = "SHOW VARIABLES LIKE 'character_set_connection'";
        $result = $database->setQuery($query)
            ->loadObject();

        if ($result === null) {
            return $this->critical('Unable to determine database connection charset.');
        }

        $charset = $result->Value ?? 'unknown';

        // Check if charset is optimal (utf8mb4)
        if ($charset !== 'utf8mb4') {
            // Specific message for older UTF-8 variants that lack 4-byte support
            if (in_array($charset, ['utf8mb3', 'utf8'], true)) {
                return $this->warning(
                    sprintf('Connection charset is %s. utf8mb4 is recommended for full Unicode support.', $charset),
                );
            }

            // Generic warning for non-UTF-8 charsets
            return $this->warning(sprintf('Connection charset is %s. UTF-8 (utf8mb4) is recommended.', $charset));
        }

        return $this->good('Connection charset is utf8mb4 (full Unicode support).');
    }
}
