<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Admin Email Validation Health Check
 *
 * This check validates that all active Super Administrator accounts have legitimate,
 * working email addresses. It checks for empty emails, invalid email formats, and
 * placeholder/disposable domains like example.com, test.com, mailinator.com, etc.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Super Administrator email addresses are critical for account recovery, security
 * notifications, and system alerts. If a super admin forgets their password, the
 * email address is the recovery mechanism. Placeholder emails (commonly left from
 * development) or disposable email addresses mean that password recovery is
 * impossible and security notifications will not be received. This can lock
 * administrators out of their own sites or leave them unaware of security issues.
 * Invalid admin emails often indicate rushed deployments or copied configurations.
 *
 * RESULT MEANINGS:
 *
 * GOOD: All Super Administrator accounts have valid, legitimate email addresses
 *       that are not from known placeholder or disposable domains. The count of
 *       validated accounts is reported.
 *
 * WARNING: No active Super Administrator users were found. This may indicate a
 *          configuration issue that should be investigated.
 *
 * CRITICAL: One or more Super Admin accounts have invalid, missing, or placeholder
 *           email addresses. The affected usernames and specific issues are listed.
 *           Update these email addresses immediately to ensure password recovery
 *           and security notifications will work.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Users;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class AdminEmailCheck extends AbstractHealthCheck
{
    /**
     * List of known placeholder and disposable email domains that should not be used
     * for Super Administrator accounts.
     *
     * @var array<int, string>
     */
    private const INVALID_DOMAINS = [
        'example.com',
        'example.org',
        'example.net',
        'test.com',
        'test.org',
        'localhost',
        'localhost.localdomain',
        'invalid.com',
        'fake.com',
        'mailinator.com',
        'tempmail.com',
        'throwaway.com',
    ];

    /**
     * Returns the unique identifier for this check.
     *
     * @return string The check slug in the format 'users.admin_email'
     */
    public function getSlug(): string
    {
        return 'users.admin_email';
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
     * Performs the admin email validation health check.
     *
     * Validates that all active Super Administrator accounts have legitimate email
     * addresses. Checks for empty emails, invalid formats, and placeholder/disposable
     * domains. Super admin emails are critical for password recovery and security
     * notifications.
     *
     * @return HealthCheckResult CRITICAL if invalid emails found, GOOD if all valid,
     *                          WARNING if no super admins exist
     */
    /**
     * Perform the Admin Email health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // Get Super Admin users (group_id 8) with their email addresses
        // Only check active (non-blocked) accounts
        $query = $database->getQuery(true)
            ->select(
                [$database->quoteName('u.id'), $database->quoteName('u.username'), $database->quoteName('u.email')],
            )
            ->from($database->quoteName('#__users', 'u'))
            ->join(
                'INNER',
                $database->quoteName('#__user_usergroup_map', 'm'),
                $database->quoteName('u.id') . ' = ' . $database->quoteName('m.user_id'),
            )
            ->where($database->quoteName('m.group_id') . ' = 8') // Super Users group
            ->where($database->quoteName('u.block') . ' = 0');   // Only active users

        $superAdmins = $database->setQuery($query)
            ->loadObjectList();

        if ($superAdmins === []) {
            return $this->warning('No active Super Admin users found.');
        }

        $invalidEmails = [];

        // Validate each super admin's email address
        foreach ($superAdmins as $superAdmin) {
            $email = strtolower(trim((string) $superAdmin->email));

            // Check for empty or missing email
            if ($email === '' || $email === '0') {
                $invalidEmails[] = sprintf('%s (no email)', $superAdmin->username);
                continue;
            }

            // Validate email format using PHP's filter
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $invalidEmails[] = sprintf('%s (invalid format)', $superAdmin->username);
                continue;
            }

            // Extract domain portion of email (everything after @)
            $domain = substr($email, strrpos($email, '@') + 1);

            // Check if domain is in the list of invalid/placeholder domains
            if (in_array($domain, self::INVALID_DOMAINS, true)) {
                $invalidEmails[] = sprintf('%s (%s)', $superAdmin->username, $domain);
            }
        }

        // If any invalid emails found, this is a critical security issue
        if ($invalidEmails !== []) {
            return $this->critical(
                sprintf(
                    'Super Admin accounts with invalid or placeholder email addresses: %s',
                    implode(', ', $invalidEmails),
                ),
            );
        }

        return $this->good(
            sprintf('All %d Super Admin account(s) have valid email addresses.', count($superAdmins)),
        );
    }
}
