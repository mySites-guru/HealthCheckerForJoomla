<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * CAPTCHA Configuration Health Check
 *
 * This check verifies that a CAPTCHA plugin is enabled and configured as the default
 * for form protection. CAPTCHA challenges help distinguish humans from automated bots.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Automated bots constantly probe websites to submit spam through contact forms,
 * create fake user accounts, and attempt brute force login attacks. CAPTCHA adds
 * a verification step that is easy for humans but difficult for bots, significantly
 * reducing automated abuse.
 *
 * RESULT MEANINGS:
 *
 * GOOD: A CAPTCHA plugin is enabled and set as the default in Global Configuration.
 *       Forms can use CAPTCHA to prevent spam and automated submissions.
 *
 * WARNING: Either no CAPTCHA plugins are enabled, or a plugin is enabled but not
 *          set as the default captcha in Global Configuration. Enable a CAPTCHA
 *          plugin (such as reCAPTCHA or hCaptcha) and configure it as the default.
 *
 * CRITICAL: Not applicable for this check.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Security;

use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class ReCaptchaCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check slug in format 'category.check_name'
     */
    public function getSlug(): string
    {
        return 'security.recaptcha';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category slug
     */
    public function getCategory(): string
    {
        return 'security';
    }

    /**
     * Perform the CAPTCHA configuration check.
     *
     * Verifies that CAPTCHA protection is properly configured by checking:
     * 1. Whether any CAPTCHA plugins are enabled (folder = 'captcha')
     * 2. Whether a default CAPTCHA is configured in Global Configuration
     *
     * CAPTCHA is essential for preventing automated bot abuse including:
     * - Contact form spam submissions
     * - Fake user account creation
     * - Brute force login attempts
     *
     * Both conditions must be met:
     * - At least one CAPTCHA plugin enabled (e.g., reCAPTCHA, hCaptcha)
     * - Default CAPTCHA selected in Global Configuration (captcha setting)
     *
     * @return HealthCheckResult Result indicating CAPTCHA protection status:
     *                           - WARNING: No CAPTCHA plugins enabled OR plugin enabled but not set as default
     *                           - GOOD: CAPTCHA plugin enabled and configured as default
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();

        // Ensure database connection is available
        // Check if any captcha plugins are enabled in the database
        // All CAPTCHA plugins are in the 'captcha' folder
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__extensions'))
            ->where($database->quoteName('type') . ' = ' . $database->quote('plugin'))
            ->where($database->quoteName('folder') . ' = ' . $database->quote('captcha'))
            ->where($database->quoteName('enabled') . ' = 1');

        $enabledCaptcha = (int) $database->setQuery($query)
            ->loadResult();

        // Check global configuration for default CAPTCHA setting
        // '0' or empty means no default CAPTCHA is configured
        $defaultCaptcha = Factory::getApplication()->get('captcha', '0');

        // No CAPTCHA plugins are enabled - site is vulnerable to bots
        if ($enabledCaptcha === 0) {
            return $this->warning(
                'No CAPTCHA plugins are enabled. Consider enabling CAPTCHA to prevent spam and bot attacks.',
            );
        }

        // CAPTCHA plugin exists but not configured as default
        // Must be selected in Global Configuration for forms to use it
        if ($defaultCaptcha === '0' || empty($defaultCaptcha)) {
            return $this->warning(
                'CAPTCHA plugin is enabled but not set as default. Configure default CAPTCHA in Global Configuration.',
            );
        }

        // CAPTCHA is properly configured and ready to protect forms
        return $this->good('CAPTCHA is configured for form protection.');
    }
}
