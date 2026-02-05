<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace MySitesGuru\HealthChecker\Plugin\AkeebaBackup\Extension;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;
use MySitesGuru\HealthChecker\Component\Administrator\Category\HealthCategory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectCategoriesEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectChecksEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectProvidersEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\HealthCheckerEvents;
use MySitesGuru\HealthChecker\Component\Administrator\Provider\ProviderMetadata;

\defined('_JEXEC') || die;

/**
 * Akeeba Backup Health Checker Plugin
 *
 * This plugin provides health checks for Akeeba Backup installations,
 * monitoring backup status, success rates, storage usage, and configuration.
 *
 * INTEGRATION OVERVIEW:
 * This plugin integrates with Akeeba Backup Pro/Core by querying its database tables
 * to monitor backup health. It does NOT require Akeeba Backup API access - all checks
 * use direct database queries against the following tables:
 * - #__akeebabackup_backups: Backup records and status
 * - #__akeebabackup_profiles: Backup profile configurations
 *
 * HEALTH CHECKS PROVIDED:
 * 1. Installation Check - Verifies Akeeba Backup is installed
 * 2. Last Backup Check - Ensures backups run regularly (weekly recommended)
 * 3. Success Rate Check - Monitors backup completion rate (90%+ recommended)
 * 4. Stuck Backups Check - Detects backups running >24 hours
 * 5. Files Exist Check - Verifies backup archives haven't been deleted
 * 6. Backup Size Check - Reports total storage used by backups
 * 7. Profile Exists Check - Ensures at least one profile is configured
 * 8. Profile Configured Check - Verifies default profile has configuration
 * 9. Failed Backups Check - Reports failures in last 30 days
 * 10. Frequency Check - Validates backup frequency (4+ per month recommended)
 *
 * PROVIDER ATTRIBUTION:
 * All checks are attributed to the 'akeeba_backup' provider with official branding
 * and links to https://www.akeeba.com. This is an unofficial integration.
 *
 * @subpackage  HealthChecker.AkeebaBackup
 * @since       1.0.0
 */
final class AkeebaBackupPlugin extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;

    /**
     * Plugin parameters.
     *
     * @var mixed
     * @since 1.0.0
     */
    public $params;

    /**
     * Load plugin language files automatically.
     *
     * @since 1.0.0
     */
    protected $autoloadLanguage = true;

    /**
     * Returns an array of events this subscriber listens to.
     *
     * Registers event handlers for the Health Checker's three-phase discovery system:
     * 1. CollectCategories: Registers the "Akeeba Backup" category
     * 2. CollectChecks: Registers all 10 backup health checks
     * 3. CollectProviders: Registers provider metadata with branding
     *
     * @return array<string, string> Array of event names and method names
     * @since  1.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            HealthCheckerEvents::COLLECT_CATEGORIES->value => HealthCheckerEvents::COLLECT_CATEGORIES->getHandlerMethod(),
            HealthCheckerEvents::COLLECT_CHECKS->value => HealthCheckerEvents::COLLECT_CHECKS->getHandlerMethod(),
            HealthCheckerEvents::COLLECT_PROVIDERS->value => HealthCheckerEvents::COLLECT_PROVIDERS->getHandlerMethod(),
        ];
    }

    /**
     * Register the Akeeba Backup category.
     *
     * Creates a custom category for Akeeba Backup health checks, positioned between
     * the built-in "Content Quality" (80) and hypothetical future categories.
     *
     * CATEGORY DETAILS:
     * - Slug: akeeba_backup
     * - Sort Order: 85 (after Content Quality category)
     * - Icon: FontAwesome archive icon
     * - Logo: Custom Akeeba Backup logo from plugin media
     *
     * This category appears in the Health Checker UI and groups all Akeeba-related
     * health checks together. The category is only registered if this plugin is enabled.
     *
     * @param CollectCategoriesEvent $collectCategoriesEvent Event for collecting custom categories
     *
     * @since  1.0.0
     */
    public function onCollectCategories(CollectCategoriesEvent $collectCategoriesEvent): void
    {
        $collectCategoriesEvent->addResult(new HealthCategory(
            slug: 'akeeba_backup',
            label: 'PLG_HEALTHCHECKER_AKEEBABACKUP_CATEGORY',
            icon: 'fa-archive',
            sortOrder: 85,
            logoUrl: '/media/plg_healthchecker_akeebabackup/logo.png',
        ));
    }

    /**
     * Register all Akeeba Backup health checks.
     *
     * Creates and registers 10 health checks for monitoring Akeeba Backup installations.
     * Each check is implemented as an anonymous class extending AbstractHealthCheck to
     * keep all logic self-contained within this plugin.
     *
     * CHECKS REGISTERED:
     * 1. akeeba_backup.installed - Verifies Akeeba Backup tables exist
     * 2. akeeba_backup.last_backup - Checks time since last successful backup
     * 3. akeeba_backup.success_rate - Monitors 30-day backup completion rate
     * 4. akeeba_backup.stuck_backups - Detects backups stuck for >24 hours
     * 5. akeeba_backup.files_exist - Verifies backup archive files exist
     * 6. akeeba_backup.backup_size - Reports total storage used
     * 7. akeeba_backup.profile_exists - Ensures at least one profile configured
     * 8. akeeba_backup.profile_configured - Verifies default profile setup
     * 9. akeeba_backup.failed_backups - Reports failures in last 30 days
     * 10. akeeba_backup.frequency - Validates backup frequency (4+ monthly)
     *
     * DATABASE ACCESS:
     * Each check queries Akeeba Backup's tables directly using the injected database
     * connection. All checks gracefully handle missing tables (returns WARNING if not installed).
     *
     * ERROR HANDLING:
     * All checks inherit AbstractHealthCheck's try/catch error handling, which automatically
     * converts exceptions to CRITICAL status with error messages.
     *
     * @param CollectChecksEvent $collectChecksEvent Event for collecting health checks
     *
     * @since  1.0.0
     */
    public function onCollectChecks(CollectChecksEvent $collectChecksEvent): void
    {
        $database = $this->getDatabase();

        // Collect all checks first, then filter based on configuration
        $allChecks = [];

        // Check 1: Akeeba Backup Installed
        /**
         * Akeeba Backup Installation Health Check
         *
         * Verifies that Akeeba Backup is installed by checking for the presence of
         * its primary database table (#__akeebabackup_backups).
         *
         * WHY THIS CHECK IS IMPORTANT:
         * If Akeeba Backup is not installed, none of the other checks in this category
         * will function properly. This check serves as a prerequisite for all other
         * Akeeba Backup health checks and helps users understand why other checks may
         * be returning warnings.
         *
         * RESULT MEANINGS:
         *
         * GOOD: The #__akeebabackup_backups table exists, indicating Akeeba Backup
         *       is installed and database tables are present.
         *
         * WARNING: The #__akeebabackup_backups table does not exist. This means either:
         *          - Akeeba Backup is not installed at all
         *          - Akeeba Backup installation was incomplete
         *          - Database tables were manually deleted
         *          Action: Install Akeeba Backup or reinstall if corrupted.
         *
         * CRITICAL: This check does not return critical status.
         */
        $installedCheck = new class extends AbstractHealthCheck {
            /**
             * Get the unique slug identifier for this check.
             *
             * @return string Check slug: 'akeeba_backup.installed'
             * @since  1.0.0
             */
            public function getSlug(): string
            {
                return 'akeeba_backup.installed';
            }

            /**
             * Get the category this check belongs to.
             *
             * @return string Category slug: 'akeeba_backup'
             * @since  1.0.0
             */
            public function getCategory(): string
            {
                return 'akeeba_backup';
            }

            /**
             * Get the provider that supplies this check.
             *
             * @return string Provider slug: 'akeeba_backup'
             * @since  1.0.0
             */
            public function getProvider(): string
            {
                return 'akeeba_backup';
            }

            /**
             * Get the translated title for this check.
             *
             * @return string Translated check title
             * @since  1.0.0
             */
            public function getTitle(): string
            {
                return Text::_('PLG_HEALTHCHECKER_AKEEBABACKUP_CHECK_INSTALLED_TITLE');
            }

            public function getDocsUrl(): string
            {
                return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/akeebabackup/src/Extension/AkeebaBackupPlugin.php';
            }

            public function getActionUrl(?HealthStatus $status = null): string
            {
                return '/administrator/index.php?option=com_akeebabackup';
            }

            /**
             * Perform the installation check.
             *
             * Queries the database to verify the #__akeebabackup_backups table exists
             * using SHOW TABLES LIKE. This is more reliable than checking installed
             * extensions as it confirms actual database presence.
             *
             * @return HealthCheckResult GOOD if installed, WARNING if not found
             * @since  1.0.0
             */
            protected function performCheck(): HealthCheckResult
            {
                $database = $this->requireDatabase();
                $prefix = $database->getPrefix();
                $tables = $database->setQuery(
                    'SHOW TABLES LIKE ' . $database->quote($prefix . 'akeebabackup_backups'),
                )->loadColumn();

                if ($tables === []) {
                    return $this->warning('Akeeba Backup is not installed.');
                }

                return $this->good('Akeeba Backup is installed.');
            }
        };
        $installedCheck->setDatabase($database);

        $allChecks[] = $installedCheck;

        // Check 2: Last Backup
        /**
         * Last Backup Recency Health Check
         *
         * Verifies that backups are running regularly by checking when the last
         * successful backup completed.
         *
         * WHY THIS CHECK IS IMPORTANT:
         * Regular backups are critical for disaster recovery and data protection. If backups
         * stop running due to misconfiguration, scheduler failures, or resource issues, you
         * may lose weeks or months of data in the event of a site failure. This check ensures
         * backups are running on schedule and alerts you before the backup window becomes
         * dangerously old. Best practice is weekly backups at minimum, with daily backups
         * recommended for sites with frequent content updates.
         *
         * RESULT MEANINGS:
         *
         * GOOD: Last successful backup completed within the last 3 days. Backup schedule
         *       is current and functioning properly.
         *
         * WARNING: Last backup was 3-7 days ago. While still within acceptable range,
         *          consider increasing backup frequency for sites with regular content updates.
         *          Verify backup scheduler is running correctly.
         *
         * CRITICAL: Either no completed backups exist at all, or last backup was over 7 days ago.
         *           This represents a significant data loss risk. Immediate actions:
         *           - Verify Akeeba Backup scheduler/cron is running
         *           - Check for backup failures in Akeeba logs
         *           - Run a manual backup immediately
         *           - Review backup profile configuration
         */
        $lastBackupCheck = new class extends AbstractHealthCheck {
            /**
             * Get the unique slug identifier for this check.
             *
             * @return string Check slug: 'akeeba_backup.last_backup'
             * @since  1.0.0
             */
            public function getSlug(): string
            {
                return 'akeeba_backup.last_backup';
            }

            /**
             * Get the category this check belongs to.
             *
             * @return string Category slug: 'akeeba_backup'
             * @since  1.0.0
             */
            public function getCategory(): string
            {
                return 'akeeba_backup';
            }

            /**
             * Get the provider that supplies this check.
             *
             * @return string Provider slug: 'akeeba_backup'
             * @since  1.0.0
             */
            public function getProvider(): string
            {
                return 'akeeba_backup';
            }

            /**
             * Get the translated title for this check.
             *
             * @return string Translated check title
             * @since  1.0.0
             */
            public function getTitle(): string
            {
                return Text::_('PLG_HEALTHCHECKER_AKEEBABACKUP_CHECK_LAST_BACKUP_TITLE');
            }

            public function getDocsUrl(): string
            {
                return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/akeebabackup/src/Extension/AkeebaBackupPlugin.php';
            }

            public function getActionUrl(?HealthStatus $status = null): string
            {
                return '/administrator/index.php?option=com_akeebabackup';
            }

            /**
             * Perform the last backup recency check.
             *
             * Queries for the most recent completed backup and calculates days since completion.
             * Only considers backups with status='complete' to exclude failed or running backups.
             *
             * THRESHOLDS:
             * - 0-3 days: GOOD
             * - 3-7 days: WARNING
             * - 7+ days or no backups: CRITICAL
             *
             * @return HealthCheckResult Status based on days since last backup
             * @since  1.0.0
             */
            protected function performCheck(): HealthCheckResult
            {
                $database = $this->requireDatabase();
                $prefix = $database->getPrefix();
                $tables = $database->setQuery(
                    'SHOW TABLES LIKE ' . $database->quote($prefix . 'akeebabackup_backups'),
                )->loadColumn();

                if ($tables === []) {
                    return $this->warning('Akeeba Backup is not installed.');
                }

                $query = $database->getQuery(true)
                    ->select($database->quoteName('backupstart'))
                    ->from($database->quoteName('#__akeebabackup_backups'))
                    ->where($database->quoteName('status') . ' = ' . $database->quote('complete'))
                    ->order($database->quoteName('backupstart') . ' DESC');

                $lastBackup = $database->setQuery($query, 0, 1)
                    ->loadResult();

                if (! $lastBackup) {
                    return $this->critical('No completed backups found.');
                }

                $lastBackupTime = strtotime((string) $lastBackup);
                if ($lastBackupTime === false) {
                    return $this->warning('Unable to parse last backup timestamp.');
                }

                $daysSinceBackup = (time() - $lastBackupTime) / 86400;

                if ($daysSinceBackup > 7) {
                    return $this->critical(sprintf(
                        'Last backup was %.1f days ago (%s). Backups should run at least weekly.',
                        $daysSinceBackup,
                        date('Y-m-d H:i', $lastBackupTime),
                    ));
                }

                if ($daysSinceBackup > 3) {
                    return $this->warning(sprintf(
                        'Last backup was %.1f days ago (%s). Consider more frequent backups.',
                        $daysSinceBackup,
                        date('Y-m-d H:i', $lastBackupTime),
                    ));
                }

                return $this->good(sprintf(
                    'Last backup completed %.1f days ago (%s).',
                    $daysSinceBackup,
                    date('Y-m-d H:i', $lastBackupTime),
                ));
            }
        };
        $lastBackupCheck->setDatabase($database);

        $allChecks[] = $lastBackupCheck;

        // Check 3: Success Rate
        /**
         * Backup Success Rate Health Check
         *
         * Monitors the percentage of successful backups over the last 30 days to identify
         * reliability issues with the backup process.
         *
         * WHY THIS CHECK IS IMPORTANT:
         * A high backup success rate indicates a healthy, reliable backup system. Low success
         * rates suggest underlying problems such as insufficient disk space, memory limits,
         * timeouts, permission issues, or corrupted backup profiles. If backups fail frequently,
         * you cannot trust that you have valid backups for disaster recovery. This check helps
         * identify chronic backup problems that need investigation, ensuring backup reliability
         * before you actually need to restore.
         *
         * RESULT MEANINGS:
         *
         * GOOD: Success rate is 90% or higher over the last 30 days. Occasional failures are
         *       normal (server maintenance, temporary resource constraints), but overall backup
         *       system is reliable.
         *
         * WARNING: Either no backups attempted in 30 days, or success rate is below 90%.
         *          Investigate causes of failures:
         *          - Check Akeeba Backup error logs for failure patterns
         *          - Verify sufficient disk space for backups
         *          - Review PHP memory_limit and max_execution_time settings
         *          - Check file/directory permissions for backup storage
         *          - Consider excluding large files causing timeouts
         *
         * CRITICAL: This check does not return critical status.
         */
        $successRateCheck = new class extends AbstractHealthCheck {
            /**
             * Get the unique slug identifier for this check.
             *
             * @return string Check slug: 'akeeba_backup.success_rate'
             * @since  1.0.0
             */
            public function getSlug(): string
            {
                return 'akeeba_backup.success_rate';
            }

            /**
             * Get the category this check belongs to.
             *
             * @return string Category slug: 'akeeba_backup'
             * @since  1.0.0
             */
            public function getCategory(): string
            {
                return 'akeeba_backup';
            }

            /**
             * Get the provider that supplies this check.
             *
             * @return string Provider slug: 'akeeba_backup'
             * @since  1.0.0
             */
            public function getProvider(): string
            {
                return 'akeeba_backup';
            }

            /**
             * Get the translated title for this check.
             *
             * @return string Translated check title
             * @since  1.0.0
             */
            public function getTitle(): string
            {
                return Text::_('PLG_HEALTHCHECKER_AKEEBABACKUP_CHECK_SUCCESS_RATE_TITLE');
            }

            public function getDocsUrl(): string
            {
                return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/akeebabackup/src/Extension/AkeebaBackupPlugin.php';
            }

            public function getActionUrl(?HealthStatus $status = null): string
            {
                return '/administrator/index.php?option=com_akeebabackup';
            }

            /**
             * Perform the backup success rate check.
             *
             * Calculates the percentage of successful backups (status='complete') versus
             * total backup attempts in the last 30 days.
             *
             * CALCULATION:
             * Success Rate = (Successful Backups / Total Backups) * 100
             *
             * THRESHOLDS:
             * - 90%+: GOOD
             * - <90% or 0 backups: WARNING
             *
             * @return HealthCheckResult Status based on 30-day success rate
             * @since  1.0.0
             */
            protected function performCheck(): HealthCheckResult
            {
                $database = $this->requireDatabase();
                $prefix = $database->getPrefix();
                $tables = $database->setQuery(
                    'SHOW TABLES LIKE ' . $database->quote($prefix . 'akeebabackup_backups'),
                )->loadColumn();

                if ($tables === []) {
                    return $this->warning('Akeeba Backup is not installed.');
                }

                $thirtyDaysAgo = date('Y-m-d H:i:s', strtotime('-30 days'));

                // Count total backups in last 30 days
                $queryTotal = $database->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($database->quoteName('#__akeebabackup_backups'))
                    ->where($database->quoteName('backupstart') . ' >= ' . $database->quote($thirtyDaysAgo));

                $totalBackups = (int) $database->setQuery($queryTotal)
                    ->loadResult();

                if ($totalBackups === 0) {
                    return $this->warning('No backups attempted in the last 30 days.');
                }

                // Count successful backups
                $querySuccess = $database->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($database->quoteName('#__akeebabackup_backups'))
                    ->where($database->quoteName('backupstart') . ' >= ' . $database->quote($thirtyDaysAgo))
                    ->where($database->quoteName('status') . ' = ' . $database->quote('complete'));

                $successfulBackups = (int) $database->setQuery($querySuccess)
                    ->loadResult();

                $successRate = ($successfulBackups / $totalBackups) * 100;

                if ($successRate < 90) {
                    return $this->warning(sprintf(
                        'Backup success rate is %.1f%% (%d of %d backups successful in last 30 days).',
                        $successRate,
                        $successfulBackups,
                        $totalBackups,
                    ));
                }

                return $this->good(sprintf(
                    'Backup success rate is %.1f%% (%d of %d backups successful in last 30 days).',
                    $successRate,
                    $successfulBackups,
                    $totalBackups,
                ));
            }
        };
        $successRateCheck->setDatabase($database);

        $allChecks[] = $successRateCheck;

        // Check 4: Stuck Backups
        /**
         * Stuck Backups Health Check
         *
         * Detects backup processes that started over 24 hours ago but are still marked as
         * running or failed, indicating they never properly completed or cleaned up.
         *
         * WHY THIS CHECK IS IMPORTANT:
         * Stuck backups indicate serious problems with the backup process - usually PHP crashes,
         * server timeouts, or database locking issues. These orphaned backup records can:
         * - Prevent new backups from starting (if Akeeba detects a "running" backup)
         * - Indicate chronic resource problems that will affect future backups
         * - Signal database corruption or connection issues
         * - Consume disk space with incomplete backup files
         * Stuck backups must be manually cleaned up and the underlying cause resolved to ensure
         * future backups complete successfully.
         *
         * RESULT MEANINGS:
         *
         * GOOD: No backups stuck in running/failed state for over 24 hours. Backup cleanup
         *       processes are working correctly.
         *
         * WARNING: This check does not return warning status.
         *
         * CRITICAL: One or more backups started >24 hours ago are still marked as 'run' or 'fail'.
         *           Immediate actions required:
         *           - Access Akeeba Backup admin and delete stuck backup records
         *           - Review server error logs for PHP fatal errors during backup time
         *           - Check for resource exhaustion (memory_limit, max_execution_time)
         *           - Verify database server stability and connection limits
         *           - Consider increasing backup timeout settings
         *           - Test manual backup to verify current backup process works
         */
        $stuckBackupsCheck = new class extends AbstractHealthCheck {
            /**
             * Get the unique slug identifier for this check.
             *
             * @return string Check slug: 'akeeba_backup.stuck_backups'
             * @since  1.0.0
             */
            public function getSlug(): string
            {
                return 'akeeba_backup.stuck_backups';
            }

            /**
             * Get the category this check belongs to.
             *
             * @return string Category slug: 'akeeba_backup'
             * @since  1.0.0
             */
            public function getCategory(): string
            {
                return 'akeeba_backup';
            }

            /**
             * Get the provider that supplies this check.
             *
             * @return string Provider slug: 'akeeba_backup'
             * @since  1.0.0
             */
            public function getProvider(): string
            {
                return 'akeeba_backup';
            }

            /**
             * Get the translated title for this check.
             *
             * @return string Translated check title
             * @since  1.0.0
             */
            public function getTitle(): string
            {
                return Text::_('PLG_HEALTHCHECKER_AKEEBABACKUP_CHECK_STUCK_BACKUPS_TITLE');
            }

            public function getDocsUrl(): string
            {
                return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/akeebabackup/src/Extension/AkeebaBackupPlugin.php';
            }

            public function getActionUrl(?HealthStatus $status = null): string
            {
                return '/administrator/index.php?option=com_akeebabackup';
            }

            /**
             * Perform the stuck backups check.
             *
             * Searches for backup records that:
             * - Started more than 24 hours ago
             * - Are still in step 1 (instep=1)
             * - Have status 'run' or 'fail'
             *
             * Normal backups should complete within hours at most. Anything stuck for
             * 24+ hours is orphaned and needs manual cleanup.
             *
             * @return HealthCheckResult CRITICAL if stuck backups found, GOOD otherwise
             * @since  1.0.0
             */
            protected function performCheck(): HealthCheckResult
            {
                $database = $this->requireDatabase();
                $prefix = $database->getPrefix();
                $tables = $database->setQuery(
                    'SHOW TABLES LIKE ' . $database->quote($prefix . 'akeebabackup_backups'),
                )->loadColumn();

                if ($tables === []) {
                    return $this->warning('Akeeba Backup is not installed.');
                }

                $twentyFourHoursAgo = date('Y-m-d H:i:s', strtotime('-24 hours'));

                $query = $database->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($database->quoteName('#__akeebabackup_backups'))
                    ->where($database->quoteName('instep') . ' = 1')
                    ->where(
                        $database->quoteName('status') . ' IN (' . $database->quote('run') . ', ' . $database->quote(
                            'fail',
                        ) . ')',
                    )
                    ->where($database->quoteName('backupstart') . ' < ' . $database->quote($twentyFourHoursAgo));

                $stuckCount = (int) $database->setQuery($query)
                    ->loadResult();

                if ($stuckCount > 0) {
                    return $this->critical(sprintf(
                        'Found %d stuck backup(s) that started over 24 hours ago and are still marked as running or failed.',
                        $stuckCount,
                    ));
                }

                return $this->good('No stuck backups detected.');
            }
        };
        $stuckBackupsCheck->setDatabase($database);

        $allChecks[] = $stuckBackupsCheck;

        // Check 5: Files Exist
        /**
         * Backup Files Exist Health Check
         *
         * Verifies that backup archive files still exist on disk for all completed backups
         * by checking the filesexist flag in the Akeeba Backup database.
         *
         * WHY THIS CHECK IS IMPORTANT:
         * Completed backups are useless if the actual backup archive files (.jpa, .zip, etc.)
         * have been deleted from disk. This can happen due to:
         * - Manual deletion of backup files
         * - Automated cleanup scripts removing old files
         * - Disk space issues causing file corruption/deletion
         * - Moving/renaming backup storage directories without updating Akeeba config
         * - File system failures or corruption
         * Without the actual files, you cannot restore from these backups despite the database
         * showing them as "complete". This check ensures backup integrity by verifying files
         * exist, alerting you before you discover missing files during an actual restore attempt.
         *
         * RESULT MEANINGS:
         *
         * GOOD: All completed backups have their archive files present on disk (filesexist=1).
         *       Backups are available for restore operations.
         *
         * WARNING: One or more completed backups have filesexist=0, meaning the archive files
         *          are missing from disk. Actions to take:
         *          - Verify backup storage location hasn't changed
         *          - Check if automated cleanup removed files prematurely
         *          - Review disk space and file system health
         *          - Delete database records for backups with missing files (cleanup)
         *          - Run new backups to replace missing archives
         *          - Consider off-site backup storage to prevent file loss
         *
         * CRITICAL: This check does not return critical status.
         */
        $filesExistCheck = new class extends AbstractHealthCheck {
            /**
             * Get the unique slug identifier for this check.
             *
             * @return string Check slug: 'akeeba_backup.files_exist'
             * @since  1.0.0
             */
            public function getSlug(): string
            {
                return 'akeeba_backup.files_exist';
            }

            /**
             * Get the category this check belongs to.
             *
             * @return string Category slug: 'akeeba_backup'
             * @since  1.0.0
             */
            public function getCategory(): string
            {
                return 'akeeba_backup';
            }

            /**
             * Get the provider that supplies this check.
             *
             * @return string Provider slug: 'akeeba_backup'
             * @since  1.0.0
             */
            public function getProvider(): string
            {
                return 'akeeba_backup';
            }

            /**
             * Get the translated title for this check.
             *
             * @return string Translated check title
             * @since  1.0.0
             */
            public function getTitle(): string
            {
                return Text::_('PLG_HEALTHCHECKER_AKEEBABACKUP_CHECK_FILES_EXIST_TITLE');
            }

            public function getDocsUrl(): string
            {
                return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/akeebabackup/src/Extension/AkeebaBackupPlugin.php';
            }

            public function getActionUrl(?HealthStatus $status = null): string
            {
                return '/administrator/index.php?option=com_akeebabackup';
            }

            /**
             * Perform the backup files exist check.
             *
             * Counts completed backups where filesexist=0, indicating the archive file
             * was deleted or moved after the backup completed successfully.
             *
             * @return HealthCheckResult WARNING if missing files found, GOOD otherwise
             * @since  1.0.0
             */
            protected function performCheck(): HealthCheckResult
            {
                $database = $this->requireDatabase();
                $prefix = $database->getPrefix();
                $tables = $database->setQuery(
                    'SHOW TABLES LIKE ' . $database->quote($prefix . 'akeebabackup_backups'),
                )->loadColumn();

                if ($tables === []) {
                    return $this->warning('Akeeba Backup is not installed.');
                }

                $query = $database->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($database->quoteName('#__akeebabackup_backups'))
                    ->where($database->quoteName('status') . ' = ' . $database->quote('complete'))
                    ->where($database->quoteName('filesexist') . ' = 0');

                $missingCount = (int) $database->setQuery($query)
                    ->loadResult();

                if ($missingCount > 0) {
                    return $this->warning(sprintf(
                        'Found %d completed backup(s) with missing files. Backup archives may have been deleted.',
                        $missingCount,
                    ));
                }

                return $this->good('All completed backup files exist.');
            }
        };
        $filesExistCheck->setDatabase($database);

        $allChecks[] = $filesExistCheck;

        // Check 6: Backup Size
        /**
         * Backup Storage Size Health Check
         *
         * Calculates and reports the total disk space consumed by all existing backup
         * archive files for informational and capacity planning purposes.
         *
         * WHY THIS CHECK IS IMPORTANT:
         * Understanding total backup storage usage is critical for:
         * - Capacity planning and preventing disk space exhaustion
         * - Budgeting for hosting/storage costs
         * - Determining when to implement backup rotation policies
         * - Identifying if backup compression is working effectively
         * - Planning off-site backup transfers (bandwidth considerations)
         * While this check always returns GOOD status (informational only), the data helps
         * administrators make informed decisions about backup retention, storage allocation,
         * and when to clean up old backups before running out of disk space.
         *
         * RESULT MEANINGS:
         *
         * GOOD: Always returns GOOD status with total storage size formatted in human-readable
         *       units (B, KB, MB, GB, TB). Use this information to:
         *       - Monitor storage growth trends
         *       - Plan disk space allocation for backup directory
         *       - Determine optimal backup retention period
         *       - Budget for increased storage if backups are growing
         *       - Compare to available disk space (see System checks)
         *
         * WARNING: This check does not return warning status.
         *
         * CRITICAL: This check does not return critical status.
         */
        $backupSizeCheck = new class extends AbstractHealthCheck {
            /**
             * Get the unique slug identifier for this check.
             *
             * @return string Check slug: 'akeeba_backup.backup_size'
             * @since  1.0.0
             */
            public function getSlug(): string
            {
                return 'akeeba_backup.backup_size';
            }

            /**
             * Get the category this check belongs to.
             *
             * @return string Category slug: 'akeeba_backup'
             * @since  1.0.0
             */
            public function getCategory(): string
            {
                return 'akeeba_backup';
            }

            /**
             * Get the provider that supplies this check.
             *
             * @return string Provider slug: 'akeeba_backup'
             * @since  1.0.0
             */
            public function getProvider(): string
            {
                return 'akeeba_backup';
            }

            /**
             * Get the translated title for this check.
             *
             * @return string Translated check title
             * @since  1.0.0
             */
            public function getTitle(): string
            {
                return Text::_('PLG_HEALTHCHECKER_AKEEBABACKUP_CHECK_BACKUP_SIZE_TITLE');
            }

            public function getDocsUrl(): string
            {
                return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/akeebabackup/src/Extension/AkeebaBackupPlugin.php';
            }

            public function getActionUrl(?HealthStatus $status = null): string
            {
                return '/administrator/index.php?option=com_akeebabackup';
            }

            /**
             * Perform the backup storage size check.
             *
             * Sums the total_size column from all completed backups where files still exist.
             * Returns informational GOOD status with human-readable size (e.g., "1.25 GB").
             *
             * @return HealthCheckResult Always GOOD with formatted size information
             * @since  1.0.0
             */
            protected function performCheck(): HealthCheckResult
            {
                $database = $this->requireDatabase();
                $prefix = $database->getPrefix();
                $tables = $database->setQuery(
                    'SHOW TABLES LIKE ' . $database->quote($prefix . 'akeebabackup_backups'),
                )->loadColumn();

                if ($tables === []) {
                    return $this->warning('Akeeba Backup is not installed.');
                }

                $query = $database->getQuery(true)
                    ->select('SUM(' . $database->quoteName('total_size') . ')')
                    ->from($database->quoteName('#__akeebabackup_backups'))
                    ->where($database->quoteName('status') . ' = ' . $database->quote('complete'))
                    ->where($database->quoteName('filesexist') . ' = 1');

                $totalSize = (int) $database->setQuery($query)
                    ->loadResult();

                $formattedSize = $this->formatBytes($totalSize);

                return $this->good(sprintf('Total backup storage: %s', $formattedSize));
            }

            /**
             * Format bytes into human-readable size with appropriate unit.
             *
             * Converts raw byte count into formatted string with units from B to TB.
             * Uses base-1024 (binary) calculation for traditional storage units.
             *
             * @param int $bytes Raw byte count
             *
             * @return string Formatted size string (e.g., "1.25 GB", "512.00 MB")
             * @since  1.0.0
             */
            private function formatBytes(int $bytes): string
            {
                if ($bytes === 0) {
                    return '0 B';
                }

                $units = ['B', 'KB', 'MB', 'GB', 'TB'];
                $pow = floor(log($bytes, 1024));
                $pow = min($pow, count($units) - 1);

                return sprintf('%.2f %s', $bytes / (1024 ** $pow), $units[(int) $pow]);
            }
        };
        $backupSizeCheck->setDatabase($database);

        $allChecks[] = $backupSizeCheck;

        // Check 7: Profile Exists
        /**
         * Backup Profile Exists Health Check
         *
         * Verifies that at least one backup profile is configured in Akeeba Backup,
         * as profiles are required for backups to run.
         *
         * WHY THIS CHECK IS IMPORTANT:
         * Akeeba Backup uses "profiles" to define backup configurations - what to backup,
         * where to store it, compression settings, filters, etc. Without at least one profile,
         * no backups can be created. This check ensures the fundamental prerequisite for backups
         * is met. A fresh Akeeba installation creates a default profile (#1), but if this is
         * missing or all profiles were deleted, backups cannot function. This check prevents
         * situations where scheduled backups silently fail due to missing profile configuration.
         *
         * RESULT MEANINGS:
         *
         * GOOD: One or more backup profiles exist in the database. Backups can be configured
         *       and executed. Message shows total profile count for information.
         *
         * WARNING: No backup profiles found in #__akeebabackup_profiles table. This indicates:
         *          - Fresh installation that hasn't been configured yet
         *          - All profiles were accidentally deleted
         *          - Database corruption affecting profiles table
         *          Action: Access Akeeba Backup admin and create/configure a backup profile.
         *          Until a profile exists, no backups can run.
         *
         * CRITICAL: This check does not return critical status.
         */
        $profileExistsCheck = new class extends AbstractHealthCheck {
            /**
             * Get the unique slug identifier for this check.
             *
             * @return string Check slug: 'akeeba_backup.profile_exists'
             * @since  1.0.0
             */
            public function getSlug(): string
            {
                return 'akeeba_backup.profile_exists';
            }

            /**
             * Get the category this check belongs to.
             *
             * @return string Category slug: 'akeeba_backup'
             * @since  1.0.0
             */
            public function getCategory(): string
            {
                return 'akeeba_backup';
            }

            /**
             * Get the provider that supplies this check.
             *
             * @return string Provider slug: 'akeeba_backup'
             * @since  1.0.0
             */
            public function getProvider(): string
            {
                return 'akeeba_backup';
            }

            /**
             * Get the translated title for this check.
             *
             * @return string Translated check title
             * @since  1.0.0
             */
            public function getTitle(): string
            {
                return Text::_('PLG_HEALTHCHECKER_AKEEBABACKUP_CHECK_PROFILE_EXISTS_TITLE');
            }

            public function getDocsUrl(): string
            {
                return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/akeebabackup/src/Extension/AkeebaBackupPlugin.php';
            }

            public function getActionUrl(?HealthStatus $status = null): string
            {
                return '/administrator/index.php?option=com_akeebabackup';
            }

            /**
             * Perform the profile exists check.
             *
             * Counts total profiles in #__akeebabackup_profiles table. Any count > 0
             * means backups can be configured and run.
             *
             * @return HealthCheckResult WARNING if no profiles, GOOD with count otherwise
             * @since  1.0.0
             */
            protected function performCheck(): HealthCheckResult
            {
                $database = $this->requireDatabase();
                $prefix = $database->getPrefix();
                $tables = $database->setQuery(
                    'SHOW TABLES LIKE ' . $database->quote($prefix . 'akeebabackup_profiles'),
                )->loadColumn();

                if ($tables === []) {
                    return $this->warning('Akeeba Backup is not installed.');
                }

                $query = $database->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($database->quoteName('#__akeebabackup_profiles'));

                $profileCount = (int) $database->setQuery($query)
                    ->loadResult();

                if ($profileCount === 0) {
                    return $this->warning('No backup profiles found. Create a backup profile to enable backups.');
                }

                return $this->good(sprintf('%d backup profile(s) configured.', $profileCount));
            }
        };
        $profileExistsCheck->setDatabase($database);

        $allChecks[] = $profileExistsCheck;

        // Check 8: Profile Configured
        /**
         * Default Backup Profile Configuration Health Check
         *
         * Verifies that the default backup profile (ID 1) has configuration data,
         * indicating it has been set up and is ready to perform backups.
         *
         * WHY THIS CHECK IS IMPORTANT:
         * While a profile record may exist in the database (checked by profile_exists),
         * it's useless without actual configuration data. The configuration field contains
         * serialized settings including:
         * - What files/folders to include/exclude
         * - Database tables to backup
         * - Compression and encryption settings
         * - Output directory location
         * - Post-processing actions (upload to cloud, etc.)
         * An empty configuration means the profile was created but never configured through
         * Akeeba's Configuration Wizard. Profile ID 1 is the default profile used by most
         * backup schedules, so this check focuses on ensuring it's properly set up.
         *
         * RESULT MEANINGS:
         *
         * GOOD: Default profile (ID 1) has configuration data. The profile has been configured
         *       through Akeeba's wizard and is ready to perform backups. This doesn't validate
         *       the configuration is correct, just that it exists.
         *
         * WARNING: Default profile (ID 1) exists but has no configuration data (NULL or empty).
         *          This means:
         *          - Profile was created but Configuration Wizard never run
         *          - Configuration was deleted or corrupted
         *          - Fresh installation not yet configured
         *          Action: Access Akeeba Backup admin → Configuration Wizard and complete
         *          the setup process for profile #1.
         *
         * CRITICAL: This check does not return critical status.
         */
        $profileConfiguredCheck = new class extends AbstractHealthCheck {
            /**
             * Get the unique slug identifier for this check.
             *
             * @return string Check slug: 'akeeba_backup.profile_configured'
             * @since  1.0.0
             */
            public function getSlug(): string
            {
                return 'akeeba_backup.profile_configured';
            }

            /**
             * Get the category this check belongs to.
             *
             * @return string Category slug: 'akeeba_backup'
             * @since  1.0.0
             */
            public function getCategory(): string
            {
                return 'akeeba_backup';
            }

            /**
             * Get the provider that supplies this check.
             *
             * @return string Provider slug: 'akeeba_backup'
             * @since  1.0.0
             */
            public function getProvider(): string
            {
                return 'akeeba_backup';
            }

            /**
             * Get the translated title for this check.
             *
             * @return string Translated check title
             * @since  1.0.0
             */
            public function getTitle(): string
            {
                return Text::_('PLG_HEALTHCHECKER_AKEEBABACKUP_CHECK_PROFILE_CONFIGURED_TITLE');
            }

            public function getDocsUrl(): string
            {
                return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/akeebabackup/src/Extension/AkeebaBackupPlugin.php';
            }

            public function getActionUrl(?HealthStatus $status = null): string
            {
                return '/administrator/index.php?option=com_akeebabackup';
            }

            /**
             * Perform the profile configuration check.
             *
             * Checks if profile ID 1 (default profile) has non-empty configuration data.
             * The configuration field stores serialized profile settings.
             *
             * @return HealthCheckResult WARNING if not configured, GOOD if configuration exists
             * @since  1.0.0
             */
            protected function performCheck(): HealthCheckResult
            {
                $database = $this->requireDatabase();
                $prefix = $database->getPrefix();
                $tables = $database->setQuery(
                    'SHOW TABLES LIKE ' . $database->quote($prefix . 'akeebabackup_profiles'),
                )->loadColumn();

                if ($tables === []) {
                    return $this->warning('Akeeba Backup is not installed.');
                }

                $query = $database->getQuery(true)
                    ->select($database->quoteName('configuration'))
                    ->from($database->quoteName('#__akeebabackup_profiles'))
                    ->where($database->quoteName('id') . ' = 1');

                $configuration = $database->setQuery($query)
                    ->loadResult();

                if (empty($configuration)) {
                    return $this->warning('Default backup profile (ID 1) is not configured.');
                }

                return $this->good('Default backup profile is configured.');
            }
        };
        $profileConfiguredCheck->setDatabase($database);

        $allChecks[] = $profileConfiguredCheck;

        // Check 9: Failed Backups
        /**
         * Failed Backups Health Check
         *
         * Counts the number of backups with status='fail' in the last 30 days to
         * identify backup reliability problems.
         *
         * WHY THIS CHECK IS IMPORTANT:
         * Failed backups indicate problems that prevent successful backup completion. Common
         * failure causes include:
         * - PHP memory exhaustion (memory_limit too low)
         * - PHP execution timeout (max_execution_time insufficient)
         * - Insufficient disk space for backup archives
         * - Database connection failures during backup
         * - Permission issues writing to backup directory
         * - Corrupted backup profiles or configuration
         * While the success_rate check monitors overall reliability percentage, this check
         * provides the absolute count of failures for investigation. Even with good success
         * rates, reviewing failure logs helps identify and prevent future issues.
         *
         * RESULT MEANINGS:
         *
         * GOOD: No backups marked with status='fail' in the last 30 days. Backup process
         *       is completing successfully without failures.
         *
         * WARNING: One or more backups failed in the last 30 days. Actions required:
         *          - Access Akeeba Backup → Manage Backups to view failed backup details
         *          - Review Akeeba error logs for specific failure messages
         *          - Common fixes:
         *            * Increase PHP memory_limit (recommend 256M+ for backups)
         *            * Increase max_execution_time (recommend 300+ seconds)
         *            * Verify backup directory has write permissions
         *            * Check available disk space
         *            * Review and update backup profile filters
         *          - Test manual backup to verify current configuration works
         *
         * CRITICAL: This check does not return critical status.
         */
        $failedBackupsCheck = new class extends AbstractHealthCheck {
            /**
             * Get the unique slug identifier for this check.
             *
             * @return string Check slug: 'akeeba_backup.failed_backups'
             * @since  1.0.0
             */
            public function getSlug(): string
            {
                return 'akeeba_backup.failed_backups';
            }

            /**
             * Get the category this check belongs to.
             *
             * @return string Category slug: 'akeeba_backup'
             * @since  1.0.0
             */
            public function getCategory(): string
            {
                return 'akeeba_backup';
            }

            /**
             * Get the provider that supplies this check.
             *
             * @return string Provider slug: 'akeeba_backup'
             * @since  1.0.0
             */
            public function getProvider(): string
            {
                return 'akeeba_backup';
            }

            /**
             * Get the translated title for this check.
             *
             * @return string Translated check title
             * @since  1.0.0
             */
            public function getTitle(): string
            {
                return Text::_('PLG_HEALTHCHECKER_AKEEBABACKUP_CHECK_FAILED_BACKUPS_TITLE');
            }

            public function getDocsUrl(): string
            {
                return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/akeebabackup/src/Extension/AkeebaBackupPlugin.php';
            }

            public function getActionUrl(?HealthStatus $status = null): string
            {
                return '/administrator/index.php?option=com_akeebabackup';
            }

            /**
             * Perform the failed backups check.
             *
             * Counts backups with status='fail' that started within the last 30 days.
             * Does not include stuck backups (which are checked separately).
             *
             * @return HealthCheckResult WARNING if failures found, GOOD if none
             * @since  1.0.0
             */
            protected function performCheck(): HealthCheckResult
            {
                $database = $this->requireDatabase();
                $prefix = $database->getPrefix();
                $tables = $database->setQuery(
                    'SHOW TABLES LIKE ' . $database->quote($prefix . 'akeebabackup_backups'),
                )->loadColumn();

                if ($tables === []) {
                    return $this->warning('Akeeba Backup is not installed.');
                }

                $thirtyDaysAgo = date('Y-m-d H:i:s', strtotime('-30 days'));

                $query = $database->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($database->quoteName('#__akeebabackup_backups'))
                    ->where($database->quoteName('backupstart') . ' >= ' . $database->quote($thirtyDaysAgo))
                    ->where($database->quoteName('status') . ' = ' . $database->quote('fail'));

                $failedCount = (int) $database->setQuery($query)
                    ->loadResult();

                if ($failedCount > 0) {
                    return $this->warning(sprintf(
                        '%d backup(s) failed in the last 30 days. Review Akeeba Backup logs for details.',
                        $failedCount,
                    ));
                }

                return $this->good('No failed backups in the last 30 days.');
            }
        };
        $failedBackupsCheck->setDatabase($database);

        $allChecks[] = $failedBackupsCheck;

        // Check 10: Backup Frequency
        /**
         * Backup Frequency Health Check
         *
         * Monitors how many successful backups completed in the last 30 days to ensure
         * adequate backup frequency for proper data protection.
         *
         * WHY THIS CHECK IS IMPORTANT:
         * Backup frequency determines your maximum potential data loss in a disaster recovery
         * scenario. If you backup weekly, you could lose up to 7 days of content changes. This
         * check ensures backups run often enough for your site's update frequency:
         * - Static sites: Weekly backups sufficient (4+ per month)
         * - Content sites: Daily backups recommended (30+ per month)
         * - E-commerce sites: Multiple daily backups critical
         * The check verifies at minimum weekly backups (4 per 30 days) are occurring. This
         * complements the last_backup check by looking at overall frequency patterns rather
         * than just the most recent backup.
         *
         * RESULT MEANINGS:
         *
         * GOOD: 4 or more successful backups in the last 30 days, indicating at least weekly
         *       backup frequency. For sites with frequent content updates, consider increasing
         *       to daily backups (30+ per month).
         *
         * WARNING: 1-3 successful backups in last 30 days. While some backups exist, frequency
         *          is below recommended weekly minimum. Actions:
         *          - Review backup scheduler/cron configuration
         *          - Ensure scheduled backups are running automatically
         *          - Consider more frequent backup schedule
         *          - Verify no recurring failures preventing scheduled backups
         *
         * CRITICAL: Zero successful backups in the last 30 days. This is a severe data loss risk.
         *           Immediate actions required:
         *           - Run manual backup immediately
         *           - Configure backup scheduler (cron job or Akeeba's scheduler)
         *           - Set up at minimum weekly scheduled backups
         *           - Verify backup profile is configured correctly
         *           - Test backup process end-to-end
         */
        $frequencyCheck = new class extends AbstractHealthCheck {
            /**
             * Get the unique slug identifier for this check.
             *
             * @return string Check slug: 'akeeba_backup.frequency'
             * @since  1.0.0
             */
            public function getSlug(): string
            {
                return 'akeeba_backup.frequency';
            }

            /**
             * Get the category this check belongs to.
             *
             * @return string Category slug: 'akeeba_backup'
             * @since  1.0.0
             */
            public function getCategory(): string
            {
                return 'akeeba_backup';
            }

            /**
             * Get the provider that supplies this check.
             *
             * @return string Provider slug: 'akeeba_backup'
             * @since  1.0.0
             */
            public function getProvider(): string
            {
                return 'akeeba_backup';
            }

            /**
             * Get the translated title for this check.
             *
             * @return string Translated check title
             * @since  1.0.0
             */
            public function getTitle(): string
            {
                return Text::_('PLG_HEALTHCHECKER_AKEEBABACKUP_CHECK_FREQUENCY_TITLE');
            }

            public function getDocsUrl(): string
            {
                return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/akeebabackup/src/Extension/AkeebaBackupPlugin.php';
            }

            public function getActionUrl(?HealthStatus $status = null): string
            {
                return '/administrator/index.php?option=com_akeebabackup';
            }

            /**
             * Perform the backup frequency check.
             *
             * Counts successful backups (status='complete') in the last 30 days and
             * compares against recommended frequency thresholds.
             *
             * THRESHOLDS:
             * - 0 backups: CRITICAL (no backup coverage)
             * - 1-3 backups: WARNING (less than weekly)
             * - 4+ backups: GOOD (at least weekly)
             *
             * @return HealthCheckResult Status based on 30-day backup count
             * @since  1.0.0
             */
            protected function performCheck(): HealthCheckResult
            {
                $database = $this->requireDatabase();
                $prefix = $database->getPrefix();
                $tables = $database->setQuery(
                    'SHOW TABLES LIKE ' . $database->quote($prefix . 'akeebabackup_backups'),
                )->loadColumn();

                if ($tables === []) {
                    return $this->warning('Akeeba Backup is not installed.');
                }

                $thirtyDaysAgo = date('Y-m-d H:i:s', strtotime('-30 days'));

                $query = $database->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($database->quoteName('#__akeebabackup_backups'))
                    ->where($database->quoteName('backupstart') . ' >= ' . $database->quote($thirtyDaysAgo))
                    ->where($database->quoteName('status') . ' = ' . $database->quote('complete'));

                $backupCount = (int) $database->setQuery($query)
                    ->loadResult();

                if ($backupCount === 0) {
                    return $this->critical(
                        'No successful backups in the last 30 days. Schedule regular backups immediately.',
                    );
                }

                if ($backupCount < 4) {
                    return $this->warning(sprintf(
                        'Only %d successful backup(s) in the last 30 days. Consider scheduling weekly backups.',
                        $backupCount,
                    ));
                }

                return $this->good(sprintf('%d successful backup(s) in the last 30 days.', $backupCount));
            }
        };
        $frequencyCheck->setDatabase($database);

        $allChecks[] = $frequencyCheck;

        // Filter checks based on plugin configuration
        foreach ($allChecks as $allCheck) {
            if ($this->isCheckEnabled($allCheck->getSlug())) {
                $collectChecksEvent->addResult($allCheck);
            }
        }
    }

    /**
     * Check if a specific health check is enabled in the plugin configuration.
     *
     * Reads the plugin parameters to determine if a check should be executed.
     * Checks are enabled by default if not explicitly disabled.
     *
     * @param string $slug The check slug (e.g., 'akeeba_backup.installed')
     *
     * @return bool True if the check is enabled, false otherwise
     *
     * @since 1.0.0
     */
    private function isCheckEnabled(string $slug): bool
    {
        // Convert slug to param name (e.g., 'akeeba_backup.installed' -> 'check_akeeba_backup_installed')
        $paramName = 'check_' . str_replace('.', '_', $slug);

        // Get the toggle value (1 = enabled, 0 = disabled)
        // Default to 1 (enabled) if parameter not set
        return (bool) $this->params->get($paramName, 1);
    }

    /**
     * Register provider metadata for Akeeba Backup integration.
     *
     * Provides branding and attribution information for all checks supplied by this plugin.
     * This metadata appears in the Health Checker UI to identify where checks originate
     * and provide links to the provider's website/documentation.
     *
     * PROVIDER DETAILS:
     * - Slug: akeeba_backup (matches check provider field)
     * - Name: "Akeeba Backup (Unofficial)" - indicates this is a community integration
     * - URL: https://www.akeeba.com - links to official Akeeba Backup site
     * - Logo: Custom Akeeba branding from plugin media directory
     * - Description: Notes this is an unofficial integration example
     *
     * ATTRIBUTION IN UI:
     * When users view health check results, each check shows its provider. For this plugin's
     * checks, users see "Provided by Akeeba Backup (Unofficial)" with logo and link, making
     * it clear these are third-party integration checks, not official Akeeba features.
     *
     * @param CollectProvidersEvent $collectProvidersEvent Event for collecting provider metadata
     *
     * @since  1.0.0
     */
    public function onCollectProviders(CollectProvidersEvent $collectProvidersEvent): void
    {
        $collectProvidersEvent->addResult(new ProviderMetadata(
            slug: 'akeeba_backup',
            name: 'Akeeba Backup (Unofficial)',
            description: 'Checks provided unofficially for this plugin as an example of 3rd party integration',
            url: 'https://www.akeeba.com',
            logoUrl: '/media/plg_healthchecker_akeebabackup/logo.png',
        ));
    }
}
