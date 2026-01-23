<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Default Secret Key Health Check
 *
 * This check verifies that Joomla's secret key is unique and secure. The secret key
 * is used for cryptographic operations including session security, form tokens,
 * and password reset links.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * The secret key is a critical security component. If it is empty, a known default
 * value, or too short, attackers could potentially forge session tokens, bypass
 * CSRF protection, or compromise password reset functionality. Each Joomla installation
 * must have a unique, random secret key.
 *
 * RESULT MEANINGS:
 *
 * GOOD: The secret key is configured, unique (not a known default), and sufficiently
 *       long (16+ characters) to provide adequate security.
 *
 * WARNING: The secret key is shorter than 16 characters. While functional, a longer
 *          key provides better security. Regenerate a longer random key.
 *
 * CRITICAL: The secret key is empty or matches a known default value. This is a
 *           severe security vulnerability. Generate a new unique secret key immediately.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Security;

use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class DefaultSecretCheck extends AbstractHealthCheck
{
    /**
     * Known weak or default secret keys that represent a security vulnerability.
     *
     * These are common default values that should never be used in production.
     * Empty string is included as it represents an unset secret key.
     *
     * @var array<string>
     */
    private const WEAK_SECRETS = ['FBVtggIk5lAXBMqz', ''];

    /**
     * Get the unique identifier for this check.
     *
     * @return string The check slug in format 'security.default_secret'
     */
    public function getSlug(): string
    {
        return 'security.default_secret';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category identifier 'security'
     */
    public function getCategory(): string
    {
        return 'security';
    }

    /**
     * Perform the secret key security check.
     *
     * Verifies that Joomla's secret key is unique, not a known default, and sufficiently
     * long. The secret key is critical for cryptographic operations including session
     * security, CSRF tokens, and password reset links. A weak or default key compromises
     * these security features.
     *
     * @return HealthCheckResult CRITICAL if secret is empty or matches a known default,
     *                          WARNING if secret is shorter than 16 characters,
     *                          GOOD if secret is properly configured and unique
     */
    protected function performCheck(): HealthCheckResult
    {
        $secret = Factory::getApplication()->get('secret', '');

        // Empty secret key - critical security vulnerability
        if (empty($secret)) {
            return $this->critical('Secret key is empty. This is a critical security issue.');
        }

        // Known default or weak secret - critical security vulnerability
        if (in_array($secret, self::WEAK_SECRETS, true)) {
            return $this->critical('Secret key appears to be a default value. Generate a new unique secret.');
        }

        // Secret is too short - less secure but still functional
        if (strlen((string) $secret) < 16) {
            return $this->warning('Secret key is shorter than recommended. Consider using a longer key.');
        }

        // Secret appears to be unique and sufficiently long
        return $this->good('Secret key is configured and appears unique.');
    }
}
