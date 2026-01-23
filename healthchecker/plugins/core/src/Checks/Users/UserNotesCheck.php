<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * User Notes Health Check
 *
 * This check provides visibility into the usage of Joomla's User Notes feature,
 * counting the total number of notes and how many users have notes attached.
 * User Notes allow administrators to record information about users that is
 * visible only to backend administrators.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * User Notes are an often-overlooked feature that can contain sensitive information
 * about users (disciplinary notes, support history, special circumstances). This
 * check provides awareness of whether your site is using this feature. If notes
 * contain sensitive information, administrators should be aware they exist and
 * ensure appropriate access controls are in place. For sites not using User Notes,
 * this check simply confirms the feature is not in use.
 *
 * RESULT MEANINGS:
 *
 * GOOD (no notes): No user notes are configured. This is common for sites that
 *                  do not use this feature, and no action is needed.
 *
 * GOOD (with notes): User notes are in use. The count of notes and affected users
 *                    is reported for awareness. Consider reviewing who has access
 *                    to view these notes and whether they contain sensitive data.
 *
 * Note: This check is purely informational and does not produce WARNING or CRITICAL
 * results. User Notes presence or absence is neither good nor bad - it depends on
 * your site's needs.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Users;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class UserNotesCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug for this check.
     *
     * @return string The check slug in format: users.user_notes
     */
    public function getSlug(): string
    {
        return 'users.user_notes';
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
     * Perform the user notes health check.
     *
     * Provides visibility into the usage of Joomla's User Notes feature by counting
     * the total number of notes and how many users have notes attached. User Notes
     * allow administrators to record information about users (support history,
     * special circumstances, etc.) that is visible only to backend administrators.
     *
     * This is an informational check that always returns GOOD status with counts.
     * Administrators should be aware if notes exist, especially if they contain
     * sensitive information requiring access control consideration.
     *
     * @return HealthCheckResult The result with GOOD status and note/user counts
     */
    /**
     * Perform the User Notes health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // Count total user notes in the system
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__user_notes'));

        $totalNotes = (int) $database->setQuery($query)
            ->loadResult();

        // Count distinct users that have notes attached
        // This helps understand if notes are concentrated on a few users or spread across many
        $usersQuery = $database->getQuery(true)
            ->select('COUNT(DISTINCT ' . $database->quoteName('user_id') . ')')
            ->from($database->quoteName('#__user_notes'));

        $usersWithNotes = (int) $database->setQuery($usersQuery)
            ->loadResult();

        if ($totalNotes === 0) {
            return $this->good('No user notes configured.');
        }

        return $this->good(sprintf('%d user note(s) found across %d user(s).', $totalNotes, $usersWithNotes));
    }
}
