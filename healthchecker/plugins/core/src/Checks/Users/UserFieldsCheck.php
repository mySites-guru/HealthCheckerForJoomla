<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * User Custom Fields Health Check
 *
 * This check examines the custom fields configured for user profiles in your
 * Joomla installation. It counts both published and unpublished custom fields
 * in the com_users.user context.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Custom user fields can collect additional information during registration or
 * profile updates. This check provides awareness of:
 * - What additional data you are collecting from users (privacy/GDPR implications)
 * - Whether there are unpublished fields that might be obsolete
 * - The complexity of your user profile system
 * Sites collecting user data should be aware of all fields for data protection
 * compliance. Unpublished fields still exist in the database and may contain
 * data from when they were active.
 *
 * RESULT MEANINGS:
 *
 * GOOD (no fields): No custom user fields are configured. Your site uses only
 *                   Joomla's standard user profile fields. No action needed.
 *
 * GOOD (all published): Custom user fields are configured and all are published.
 *                       The count is reported for awareness of what data you collect.
 *
 * GOOD (mixed state): Custom user fields exist with some unpublished. The breakdown
 *                     is shown. Consider whether unpublished fields should be deleted
 *                     if they are no longer needed, especially if they contained
 *                     personal data.
 *
 * Note: This check is purely informational and does not produce WARNING or CRITICAL
 * results. Custom field presence is neither good nor bad - it depends on requirements.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Users;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class UserFieldsCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug for this check.
     *
     * @return string The check slug in format: users.user_fields
     */
    public function getSlug(): string
    {
        return 'users.user_fields';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category slug: users
     */
    public function getCategory(): string
    {
        return 'users';
    }

    /**
     * Perform the user custom fields health check.
     *
     * Examines custom fields configured for user profiles (com_users.user context),
     * counting both published and unpublished fields. This check is purely informational,
     * providing awareness of what additional data is being collected from users for
     * privacy/GDPR compliance and data management purposes.
     *
     * Always returns GOOD status with a count of custom fields and their publish state.
     *
     * @return HealthCheckResult The result with GOOD status and field count information
     */
    /**
     * Perform the User Fields health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // Count custom fields for com_users.user context
        // These fields collect additional information during registration or profile updates
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__fields'))
            ->where($database->quoteName('context') . ' = ' . $database->quote('com_users.user'));

        $totalFields = (int) $database->setQuery($query)
            ->loadResult();

        // Count published fields (currently active and visible to users)
        $publishedQuery = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__fields'))
            ->where($database->quoteName('context') . ' = ' . $database->quote('com_users.user'))
            ->where($database->quoteName('state') . ' = 1');

        $publishedFields = (int) $database->setQuery($publishedQuery)
            ->loadResult();

        if ($totalFields === 0) {
            return $this->good('No custom user fields configured.');
        }

        $unpublishedFields = $totalFields - $publishedFields;

        // Report breakdown if there are unpublished fields
        // Note: Unpublished fields still exist in database and may contain data from when active
        if ($unpublishedFields > 0) {
            return $this->good(
                sprintf(
                    '%d custom user field(s) configured (%d published, %d unpublished).',
                    $totalFields,
                    $publishedFields,
                    $unpublishedFields,
                ),
            );
        }

        return $this->good(sprintf('%d custom user field(s) configured and published.', $totalFields));
    }
}
