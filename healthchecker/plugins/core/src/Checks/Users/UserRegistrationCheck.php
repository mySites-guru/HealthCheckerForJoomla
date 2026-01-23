<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * User Registration Health Check
 *
 * This check examines whether public user registration is enabled in your Joomla
 * site by reading the allowUserRegistration setting from the com_users component
 * configuration.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Open user registration, while necessary for community sites and membership
 * platforms, is a common target for spam bots and malicious actors. Sites that
 * do not require public registration should have it disabled to reduce attack
 * surface. When registration is enabled, proper spam protection (CAPTCHA) and
 * email verification should be configured. Many site owners enable registration
 * during development and forget to disable it, leaving an unnecessary opening
 * for abuse.
 *
 * RESULT MEANINGS:
 *
 * GOOD: User registration is disabled. This is the recommended setting for sites
 *       that do not need public signups, such as corporate sites, brochure sites,
 *       or sites where all users are created by administrators.
 *
 * WARNING: User registration is enabled. Verify this is intentional for your site
 *          type. If registration is needed, ensure CAPTCHA is properly configured
 *          (System -> Global Configuration -> Users -> CAPTCHA), email verification
 *          is enabled, and consider using additional anti-spam measures.
 *
 * Note: This check does not produce CRITICAL results as enabled registration is
 * a valid configuration for many sites, just one that requires additional security.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Users;

use Joomla\CMS\Component\ComponentHelper;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class UserRegistrationCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug for this check.
     *
     * @return string The check slug in format: users.user_registration
     */
    public function getSlug(): string
    {
        return 'users.user_registration';
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
     * Perform the user registration health check.
     *
     * Examines whether public user registration is enabled by reading the
     * allowUserRegistration setting from the com_users component configuration.
     *
     * Open registration is a common target for spam bots and should only be enabled
     * for sites that genuinely need public signups (community sites, membership platforms).
     * Sites that don't require public registration should have it disabled to reduce
     * attack surface.
     *
     * Returns WARNING if registration is enabled (requiring CAPTCHA and email verification),
     * GOOD if registration is disabled (recommended for most corporate/brochure sites).
     *
     * @return HealthCheckResult The result with status (GOOD/WARNING) and configuration state
     */
    /**
     * Perform the User Registration health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        // Read the component parameters for com_users to check registration setting
        $params = ComponentHelper::getParams('com_users');
        $allowUserRegistration = (int) $params->get('allowUserRegistration', 0);

        // Registration enabled = potential security concern if not properly protected
        // Ensure CAPTCHA (System -> Global Configuration -> Users -> CAPTCHA) and
        // email verification are configured if registration is needed
        if ($allowUserRegistration === 1) {
            return $this->warning(
                'User registration is enabled. Ensure this is intentional and CAPTCHA is configured.',
            );
        }

        // Registration disabled = recommended for sites where admins create all user accounts
        return $this->good('User registration is disabled.');
    }
}
