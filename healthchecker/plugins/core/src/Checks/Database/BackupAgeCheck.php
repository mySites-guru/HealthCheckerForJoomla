<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Database Backup Age Health Check
 *
 * This check examines Akeeba Backup records to determine when the last
 * successful backup was created (warning at 7 days, critical at 30 days).
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Regular backups are essential for disaster recovery:
 * - Protect against data loss from hacking, malware, or human error
 * - Enable quick recovery after failed updates or installations
 * - Provide a restore point before major changes
 * - Required for compliance in many industries
 * Knowing backup age helps ensure you can recover if needed.
 *
 * RESULT MEANINGS:
 *
 * GOOD: A successful backup was created within the last 7 days. Your site
 * has a recent restore point available.
 *
 * WARNING (7-30 days): The last successful backup is between 7 and 30 days
 * old. Consider running a backup soon to ensure you have a current restore
 * point. Also triggers if Akeeba Backup is not installed.
 *
 * CRITICAL (30+ days): No successful backup in over 30 days, or Akeeba
 * Backup is installed but has never completed a backup successfully.
 * Create a backup immediately - your site is at risk of unrecoverable
 * data loss.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Database;

use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class BackupAgeCheck extends AbstractHealthCheck
{
    private const WARNING_DAYS = 7;

    private const CRITICAL_DAYS = 30;

    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check slug in format 'database.backup_age'
     */
    public function getSlug(): string
    {
        return 'database.backup_age';
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
     * Perform the Backup Age health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        $prefix = Factory::getApplication()->get('dbprefix');
        $akStatsTable = $prefix . 'ak_stats';

        // Check if Akeeba Backup stats table exists
        try {
            $tables = $database->setQuery('SHOW TABLES LIKE ' . $database->quote($akStatsTable))->loadColumn();

            if ($tables === []) {
                return $this->warning(
                    'Akeeba Backup is not installed or has never run. Consider installing a backup solution.',
                );
            }

            // Get the most recent successful backup
            $query = $database->getQuery(true)
                ->select('backupstart, description, status')
                ->from($database->quoteName($akStatsTable))
                ->where($database->quoteName('status') . ' = ' . $database->quote('complete'))
                ->where($database->quoteName('origin') . ' != ' . $database->quote('restorepoint'))
                ->order($database->quoteName('backupstart') . ' DESC');

            $lastBackup = $database->setQuery($query, 0, 1)
                ->loadObject();

            if ($lastBackup === null) {
                // Check if there are any backups at all (including failed)
                $anyBackupQuery = $database->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($database->quoteName($akStatsTable));

                $totalBackups = (int) $database->setQuery($anyBackupQuery)
                    ->loadResult();

                if ($totalBackups === 0) {
                    return $this->critical('Akeeba Backup is installed but no backups have been created.');
                }

                return $this->critical('No successful backups found. Check Akeeba Backup for errors.');
            }

            $backupDate = new \DateTime($lastBackup->backupstart);
            $now = new \DateTime();
            $daysSinceBackup = (int) $now->diff($backupDate)
                ->days;

            $description = $lastBackup->description ?: 'No description';

            if ($daysSinceBackup >= self::CRITICAL_DAYS) {
                return $this->critical(
                    sprintf(
                        'Last successful backup was %d days ago (%s). Create a backup immediately.',
                        $daysSinceBackup,
                        $backupDate->format('Y-m-d H:i'),
                    ),
                );
            }

            if ($daysSinceBackup >= self::WARNING_DAYS) {
                return $this->warning(
                    sprintf(
                        'Last successful backup was %d days ago (%s). Consider running a backup soon.',
                        $daysSinceBackup,
                        $backupDate->format('Y-m-d H:i'),
                    ),
                );
            }

            return $this->good(
                sprintf(
                    'Last successful backup: %s (%d day(s) ago).',
                    $backupDate->format('Y-m-d H:i'),
                    $daysSinceBackup,
                ),
            );
        } catch (\Exception $exception) {
            return $this->warning('Unable to check backup status: ' . $exception->getMessage());
        }
    }
}
