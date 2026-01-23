<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Mail Function Health Check
 *
 * This check verifies that Joomla's configured email delivery method is available
 * and properly configured. It checks for PHP's mail() function availability, SMTP
 * configuration, or sendmail executable access depending on the configured mailer.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Email is critical for Joomla operations including user registration confirmations,
 * password resets, contact form submissions, order notifications, and administrative
 * alerts. A misconfigured mail system causes silent failures that frustrate users
 * and can result in lost business or security issues.
 *
 * RESULT MEANINGS:
 *
 * GOOD: The configured mail method is available. For mail(), the function exists
 * and is not disabled. For SMTP, Joomla is configured to use external SMTP. For
 * sendmail, the configured path is executable.
 *
 * WARNING: For sendmail configuration, the sendmail binary path may not be executable.
 * Verify the path in Global Configuration and ensure proper permissions.
 *
 * CRITICAL: Joomla is configured to use PHP mail() but the function is either not
 * available or has been disabled in php.ini. Email sending will fail completely
 * until the configuration is changed or mail() is re-enabled.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\System;

use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class MailFunctionCheck extends AbstractHealthCheck
{
    /**
     * Get the unique identifier for this health check.
     *
     * @return string The check slug in format 'system.mail_function'
     */
    public function getSlug(): string
    {
        return 'system.mail_function';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category slug 'system'
     */
    public function getCategory(): string
    {
        return 'system';
    }

    /**
     * Perform the mail function health check.
     *
     * Validates that Joomla's configured email delivery method is available and
     * properly configured. Checks different configurations:
     * - mail: Verifies PHP mail() function exists and is not disabled
     * - smtp: Confirms SMTP configuration (no additional validation)
     * - sendmail: Checks sendmail binary path is executable
     *
     * @return HealthCheckResult Critical/Warning/Good based on mail configuration validity
     */
    /**
     * Perform the Mail Function health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        // Get configured mailer from Joomla config (mail, smtp, or sendmail)
        $config = Factory::getApplication()->get('mailer', 'mail');

        // Check PHP mail() function availability
        if ($config === 'mail') {
            // Verify mail() function exists in PHP
            if (! \function_exists('mail')) {
                return $this->critical('PHP mail() function is not available but Joomla is configured to use it.');
            }

            // Check if mail() is disabled in php.ini disable_functions directive
            if (ini_get('disable_functions') && str_contains(ini_get('disable_functions'), 'mail')) {
                return $this->critical(
                    'PHP mail() function is disabled in php.ini but Joomla is configured to use it.',
                );
            }

            return $this->good('PHP mail() function is available.');
        }

        // SMTP configuration - no additional checks needed as connection is tested on send
        if ($config === 'smtp') {
            return $this->good('Joomla is configured to use SMTP for email delivery.');
        }

        // Sendmail configuration - verify binary path is executable
        if ($config === 'sendmail') {
            $sendmailPath = Factory::getApplication()->get('sendmail', '/usr/sbin/sendmail');
            if (! is_executable($sendmailPath)) {
                return $this->warning(sprintf('Sendmail path may not be executable: %s', $sendmailPath));
            }

            return $this->good('Sendmail is configured for email delivery.');
        }

        // Unknown/custom mailer configuration
        return $this->good(sprintf('Mail is configured using: %s', $config));
    }
}
