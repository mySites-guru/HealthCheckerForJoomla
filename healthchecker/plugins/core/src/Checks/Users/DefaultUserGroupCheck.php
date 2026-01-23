<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Default User Group Health Check
 *
 * This check verifies that the default user group for new registrations is not
 * set to a privileged group. It reads the new_usertype parameter from com_users
 * and flags if it is set to Administrator (group 7) or Super Users (group 8).
 *
 * WHY THIS CHECK IS IMPORTANT:
 * The default user group determines what permissions newly registered users receive.
 * If accidentally set to Administrator or Super Users, any visitor who registers
 * would immediately gain full administrative access to your site. This is one of
 * the most severe misconfigurations possible and can result in complete site
 * compromise within minutes of being exploited. This setting could be changed
 * accidentally or maliciously, making regular checks essential.
 *
 * RESULT MEANINGS:
 *
 * GOOD: The default user group is set to a non-privileged group (typically
 *       "Registered" with ID 2). The group name is displayed for verification.
 *       New registrations will receive appropriate limited permissions.
 *
 * CRITICAL: The default user group is set to Administrator or Super Users.
 *           This is an extremely dangerous misconfiguration. Anyone who registers
 *           on your site would gain administrative access. IMMEDIATELY change this
 *           setting in Users -> Options -> User Options -> New User Registration Group.
 *
 * Note: This check does not produce WARNING results. The configuration is either
 * safe (GOOD) or extremely dangerous (CRITICAL).
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Users;

use Joomla\CMS\Component\ComponentHelper;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class DefaultUserGroupCheck extends AbstractHealthCheck
{
    /**
     * User group IDs that should never be used as default registration groups.
     * Group 7 = Administrator, Group 8 = Super Users
     *
     * @var array<int, int>
     */
    private const DANGEROUS_GROUPS = [7, 8]; // Administrator and Super Users

    /**
     * Returns the unique identifier for this check.
     *
     * @return string The check slug in the format 'users.default_user_group'
     */
    public function getSlug(): string
    {
        return 'users.default_user_group';
    }

    /**
     * Returns the category this check belongs to.
     *
     * @return string The category slug 'users'
     */
    public function getCategory(): string
    {
        return 'users';
    }

    /**
     * Performs the default user group health check.
     *
     * Verifies that the default user group for new registrations (new_usertype parameter
     * in com_users configuration) is not set to a privileged group like Administrator or
     * Super Users. If set to these groups, any visitor could register and gain full admin
     * access.
     *
     * @return HealthCheckResult CRITICAL if default group is Administrator/Super Users,
     *                          GOOD if set to appropriate non-privileged group
     */
    /**
     * Perform the Default User Group health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        // Read com_users configuration to get default user group for new registrations
        $params = ComponentHelper::getParams('com_users');
        $defaultGroup = (int) $params->get('new_usertype', 2); // Default: 2 = Registered

        // Check if default group is Administrator (7) or Super Users (8)
        if (in_array($defaultGroup, self::DANGEROUS_GROUPS, true)) {
            return $this->critical(
                'Default user group is set to Administrator or Super Users! This is a critical security risk.',
            );
        }

        $database = $this->requireDatabase();

        // Fetch the group name for display in the result message
        $query = $database->getQuery(true)
            ->select($database->quoteName('title'))
            ->from($database->quoteName('#__usergroups'))
            ->where($database->quoteName('id') . ' = ' . $defaultGroup);

        $groupName = $database->setQuery($query)
            ->loadResult();

        return $this->good(sprintf('Default user group: %s', $groupName ?: 'ID ' . $defaultGroup));
    }
}
