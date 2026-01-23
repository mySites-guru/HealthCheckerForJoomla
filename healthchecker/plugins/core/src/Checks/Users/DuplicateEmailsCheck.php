<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Duplicate Emails Health Check
 *
 * This check identifies email addresses that are used by multiple user accounts
 * in your Joomla installation. It groups users by email and flags any email
 * address shared across more than one account.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Duplicate email addresses can cause several problems: password reset emails
 * may go to the wrong account, users may be confused about which account to use,
 * and it can indicate data integrity issues from migrations, imports, or bugs.
 * In some cases, duplicates may indicate a security concern where someone created
 * multiple accounts to circumvent restrictions. Joomla typically enforces unique
 * emails, so duplicates usually result from direct database manipulation, imports,
 * or legacy data.
 *
 * RESULT MEANINGS:
 *
 * GOOD: No duplicate email addresses found. Each user account has a unique email
 *       address, which is the expected and healthy state.
 *
 * WARNING: One or more email addresses are shared by multiple accounts. You should
 *          investigate these duplicates and resolve them by updating email addresses
 *          or merging/removing redundant accounts. The number of duplicate email
 *          addresses (not accounts) is reported.
 *
 * Note: This check does not produce CRITICAL results as duplicate emails are a
 * data integrity issue rather than an immediate security threat.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Users;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class DuplicateEmailsCheck extends AbstractHealthCheck
{
    /**
     * Returns the unique identifier for this check.
     *
     * @return string The check slug in the format 'users.duplicate_emails'
     */
    public function getSlug(): string
    {
        return 'users.duplicate_emails';
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
     * Performs the duplicate email addresses health check.
     *
     * Queries the #__users table to find email addresses that are shared by multiple
     * user accounts. Groups users by email and counts occurrences, flagging any email
     * that appears more than once.
     *
     * @return HealthCheckResult WARNING if duplicate emails found, GOOD otherwise
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // Find email addresses used by multiple accounts
        // GROUP BY email and HAVING COUNT(*) > 1 returns only duplicates
        $query = $database->getQuery(true)
            ->select([$database->quoteName('email'), 'COUNT(*) as cnt'])
            ->from($database->quoteName('#__users'))
            ->group($database->quoteName('email'))
            ->having('COUNT(*) > 1');

        $duplicates = $database->setQuery($query)
            ->loadObjectList();

        if ($duplicates !== []) {
            return $this->warning(
                sprintf('%d email address(es) are used by multiple accounts.', count($duplicates)),
            );
        }

        return $this->good('No duplicate email addresses found.');
    }
}
