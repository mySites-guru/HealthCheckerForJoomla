<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Password Policy Health Check
 *
 * This check verifies that Joomla's password policy is configured with adequate
 * minimum length and complexity requirements. Strong password policies help prevent
 * account compromise.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Weak passwords are a primary attack vector. Short passwords without complexity
 * requirements can be cracked quickly through brute force or dictionary attacks.
 * A strong password policy forces users to create passwords that are resistant to
 * these attacks, protecting user accounts and administrator access.
 *
 * RESULT MEANINGS:
 *
 * GOOD: Minimum password length is 12 or more characters with complexity requirements
 *       (numbers, symbols, or mixed case). Users must create strong passwords.
 *
 * WARNING: Password length is 8-11 characters, or length is adequate but no complexity
 *          requirements are set. Consider increasing to 12+ characters and enabling
 *          complexity rules in Users > Options > Password Options.
 *
 * CRITICAL: Minimum password length is less than 8 characters. This allows dangerously
 *           weak passwords. Increase to at least 12 characters immediately.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Security;

use Joomla\CMS\Component\ComponentHelper;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class PasswordPolicyCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check slug in format 'category.check_name'
     */
    public function getSlug(): string
    {
        return 'security.password_policy';
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
     * Perform the password policy configuration check.
     *
     * Evaluates Joomla's password policy settings including:
     * - Minimum password length
     * - Complexity requirements (integers, symbols, uppercase, lowercase)
     *
     * Strong password policies are critical for preventing account compromise.
     * Weak passwords can be cracked through brute force or dictionary attacks.
     * This check ensures the policy enforces adequate strength requirements.
     *
     * Security thresholds:
     * - Critical: Less than 8 characters (dangerously weak)
     * - Warning: 8-11 characters, or adequate length but no complexity requirements
     * - Good: 12+ characters with complexity requirements
     *
     * @return HealthCheckResult Result indicating password policy strength:
     *                           - CRITICAL: Minimum length less than 8 characters
     *                           - WARNING: Length 8-11 characters, or no complexity requirements
     *                           - GOOD: 12+ characters with complexity requirements
     */
    protected function performCheck(): HealthCheckResult
    {
        // Password policy settings are stored in com_users component parameters
        // (Users > Options > Password Options), not in global configuration
        $registry = ComponentHelper::getParams('com_users');

        $minLength = (int) $registry->get('minimum_length', 12);
        $minIntegers = (int) $registry->get('minimum_integers', 0);
        $minSymbols = (int) $registry->get('minimum_symbols', 0);
        $minUppercase = (int) $registry->get('minimum_uppercase', 0);
        $minLowercase = (int) $registry->get('minimum_lowercase', 0);

        // Critical: Password length under 8 characters is dangerously weak
        if ($minLength < 8) {
            return $this->critical(
                sprintf(
                    'Minimum password length is %d characters. For security, set this to at least 12 characters.',
                    $minLength,
                ),
            );
        }

        // Warning: Password length 8-11 is acceptable but not ideal
        if ($minLength < 12) {
            return $this->warning(
                sprintf(
                    'Minimum password length is %d characters. Consider increasing to 12 or more for better security.',
                    $minLength,
                ),
            );
        }

        // Check if any complexity requirements are configured
        $hasComplexity = ($minIntegers > 0 || $minSymbols > 0 || $minUppercase > 0 || $minLowercase > 0);

        // Warning: Adequate length but no complexity requirements
        if (! $hasComplexity) {
            return $this->warning(
                sprintf(
                    'Minimum password length is %d characters, but no complexity requirements are set. Consider requiring numbers, symbols, or mixed case.',
                    $minLength,
                ),
            );
        }

        // Good: Strong password policy with length and complexity
        return $this->good(
            sprintf('Password policy configured: minimum %d characters with complexity requirements.', $minLength),
        );
    }
}
