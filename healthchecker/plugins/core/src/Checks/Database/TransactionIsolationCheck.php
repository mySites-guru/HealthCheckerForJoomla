<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Database Transaction Isolation Level Health Check
 *
 * This check examines the MySQL/MariaDB transaction isolation level setting
 * to ensure it provides appropriate data consistency for Joomla operations.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Transaction isolation levels control visibility of changes between transactions:
 * - READ-UNCOMMITTED: Fastest but allows "dirty reads" of uncommitted data
 * - READ-COMMITTED: Prevents dirty reads but allows non-repeatable reads
 * - REPEATABLE-READ: Default for InnoDB, good balance of consistency/performance
 * - SERIALIZABLE: Maximum consistency but significant performance impact
 * The wrong setting can cause data inconsistencies or performance problems.
 *
 * RESULT MEANINGS:
 *
 * GOOD: The isolation level is REPEATABLE-READ (recommended) or READ-COMMITTED.
 * These provide a good balance of data consistency and performance.
 *
 * WARNING (READ-UNCOMMITTED): Dirty reads are possible, meaning transactions
 * may see data that was never committed. This could cause data inconsistencies
 * in edge cases. Consider using REPEATABLE-READ.
 *
 * WARNING (SERIALIZABLE): Maximum consistency but with significant performance
 * overhead due to increased locking. Unless you have specific requirements,
 * consider using REPEATABLE-READ for better performance.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Database;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class TransactionIsolationCheck extends AbstractHealthCheck
{
    /**
     * The recommended transaction isolation level for Joomla applications.
     *
     * REPEATABLE-READ provides a good balance between data consistency and performance,
     * and is the default for InnoDB in MySQL/MariaDB.
     *
     * @var string
     */
    private const RECOMMENDED_LEVEL = 'REPEATABLE-READ';

    /**
     * Get the unique identifier for this health check.
     *
     * @return string The check slug in format "database.transaction_isolation"
     */
    public function getSlug(): string
    {
        return 'database.transaction_isolation';
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
     * Perform the transaction isolation level health check.
     *
     * Examines the MySQL/MariaDB transaction isolation level to ensure it provides
     * appropriate data consistency for Joomla operations without unnecessary performance
     * overhead. The isolation level controls what data is visible between concurrent
     * transactions.
     *
     * The four isolation levels (in order of increasing consistency/decreasing performance):
     * 1. READ-UNCOMMITTED: Allows dirty reads (WARNING - too permissive)
     * 2. READ-COMMITTED: Prevents dirty reads (GOOD)
     * 3. REPEATABLE-READ: Also prevents non-repeatable reads (GOOD - recommended)
     * 4. SERIALIZABLE: Maximum isolation but high overhead (WARNING - overkill)
     *
     * The check handles MySQL version differences:
     * - MySQL 8.0+ uses @@transaction_isolation variable
     * - Older MySQL/MariaDB uses @@tx_isolation variable
     *
     * @return HealthCheckResult Critical if database unavailable, warning if isolation level
     *                           is READ-UNCOMMITTED or SERIALIZABLE, good for READ-COMMITTED
     *                           or REPEATABLE-READ (recommended)
     */
    /**
     * Perform the Transaction Isolation health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        try {
            // Try MySQL 8.0+ variable first, fall back to older variable name
            $isolationLevel = null;

            try {
                // MySQL 8.0+ renamed tx_isolation to transaction_isolation
                $isolationLevel = $database->setQuery('SELECT @@transaction_isolation')
                    ->loadResult();
            } catch (\Exception) {
                // Fall back to older MySQL/MariaDB variable name
                $isolationLevel = $database->setQuery('SELECT @@tx_isolation')
                    ->loadResult();
            }

            if ($isolationLevel === null) {
                return $this->warning('Unable to determine transaction isolation level.');
            }

            // Normalize the value (MySQL uses hyphens, some return underscores)
            $normalizedLevel = str_replace('_', '-', strtoupper((string) $isolationLevel));

            // Check for potentially problematic isolation levels
            if ($normalizedLevel === 'READ-UNCOMMITTED') {
                return $this->warning(
                    sprintf(
                        'Transaction isolation level is %s (dirty reads allowed). Consider using %s for better data consistency.',
                        $isolationLevel,
                        self::RECOMMENDED_LEVEL,
                    ),
                );
            }

            if ($normalizedLevel === 'SERIALIZABLE') {
                return $this->warning(
                    sprintf(
                        'Transaction isolation level is %s. This provides maximum consistency but may impact performance due to increased locking.',
                        $isolationLevel,
                    ),
                );
            }

            // READ-COMMITTED and REPEATABLE-READ are both acceptable
            if ($normalizedLevel === self::RECOMMENDED_LEVEL) {
                return $this->good(
                    sprintf(
                        'Transaction isolation level is %s (recommended for most applications).',
                        $isolationLevel,
                    ),
                );
            }

            return $this->good(sprintf('Transaction isolation level is %s.', $isolationLevel));
        } catch (\Exception $exception) {
            return $this->warning('Unable to check transaction isolation level: ' . $exception->getMessage());
        }
    }
}
