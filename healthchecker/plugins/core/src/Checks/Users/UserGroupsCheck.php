<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * User Groups Health Check
 *
 * This check counts the total number of user groups defined in your Joomla
 * installation. Joomla ships with a default set of groups, and administrators
 * can create additional custom groups for fine-grained access control.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * User groups are fundamental to Joomla's ACL (Access Control List) system,
 * determining who can view content and perform actions. While custom groups
 * enable flexible permissions, an excessive number of groups can indicate
 * permission sprawl that becomes difficult to manage, audit, and maintain.
 * Over time, unused groups accumulate as roles change or projects end. Complex
 * group hierarchies can lead to unintended permission inheritance and security
 * gaps that are hard to identify.
 *
 * RESULT MEANINGS:
 *
 * GOOD: 20 or fewer user groups are defined. This is a manageable number that
 *       allows for effective access control without excessive complexity. The
 *       exact count is reported for your awareness.
 *
 * WARNING: More than 20 user groups are defined. This level of complexity may
 *          make permissions difficult to manage and audit. Consider reviewing
 *          your group structure, consolidating similar groups, and removing
 *          unused groups to simplify administration.
 *
 * Note: This check does not produce CRITICAL results as group count is an
 * organizational concern rather than a direct security threat.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Users;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class UserGroupsCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug for this check.
     *
     * @return string The check slug in format: users.user_groups
     */
    public function getSlug(): string
    {
        return 'users.user_groups';
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
     * Perform the user groups health check.
     *
     * Counts the total number of user groups defined in the Joomla installation.
     * User groups form the foundation of Joomla's ACL system, but excessive groups
     * can lead to permission sprawl that is difficult to manage and audit.
     *
     * Returns WARNING if more than 20 groups exist (suggesting complexity that should
     * be reviewed), GOOD for 20 or fewer groups (manageable access control).
     *
     * @return HealthCheckResult The result with status (GOOD/WARNING) and group count
     */
    /**
     * Perform the User Groups health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // Count all user groups (includes default Joomla groups + any custom groups)
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__usergroups'));

        $groupCount = (int) $database->setQuery($query)
            ->loadResult();

        // More than 20 groups indicates potential permission sprawl
        // Complex group hierarchies can lead to unintended permission inheritance
        if ($groupCount > 20) {
            return $this->warning(
                sprintf('%d user groups defined. Consider consolidating if not all are needed.', $groupCount),
            );
        }

        return $this->good(sprintf('%d user groups defined.', $groupCount));
    }
}
