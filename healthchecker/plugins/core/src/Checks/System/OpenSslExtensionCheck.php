<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * OpenSSL Extension Health Check
 *
 * This check verifies that the PHP OpenSSL extension is loaded for cryptographic operations.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * The OpenSSL extension is critical for security and encrypted communications:
 * - HTTPS connections for secure browsing and API calls
 * - Password hashing and verification
 * - Session encryption and security tokens
 * - Two-factor authentication (2FA) implementation
 * - Secure random number generation
 * - TLS/SSL certificate verification
 * - Encrypted cookie handling
 * Without OpenSSL, Joomla cannot establish secure connections or perform
 * essential cryptographic operations required for modern web security.
 *
 * RESULT MEANINGS:
 *
 * GOOD: OpenSSL extension is loaded with the reported version.
 *       Secure communications and cryptographic operations are available.
 *
 * CRITICAL: OpenSSL extension is not available. HTTPS connections will fail,
 *           password security will be compromised, and many security features
 *           will not function. Contact your hosting provider immediately to
 *           enable the OpenSSL extension.
 *
 * Note: This check does not produce WARNING results as OpenSSL is essential
 *       for secure operation of any modern Joomla site.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class OpenSslExtensionCheck extends AbstractHealthCheck
{
    /**
     * Returns the unique identifier for this health check.
     *
     * @return string The check slug in format 'system.openssl_extension'
     */
    public function getSlug(): string
    {
        return 'system.openssl_extension';
    }

    /**
     * Returns the category this check belongs to.
     *
     * @return string The category identifier 'system'
     */
    public function getCategory(): string
    {
        return 'system';
    }

    /**
     * Performs the OpenSSL extension availability check.
     *
     * Verifies that the PHP OpenSSL extension is loaded. This extension is critical
     * for security and encrypted communications, including HTTPS connections, password
     * hashing/verification, session encryption, two-factor authentication, secure random
     * number generation, TLS/SSL certificate verification, and encrypted cookie handling.
     * Without OpenSSL, Joomla cannot establish secure connections or perform essential
     * cryptographic operations required for modern web security.
     *
     * @return HealthCheckResult Good status if OpenSSL extension is loaded (with version),
     *                            Critical status if OpenSSL extension is not available
     */
    /**
     * Perform the Open Ssl Extension health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        // OpenSSL is essential for secure operation of any modern Joomla site
        if (! extension_loaded('openssl')) {
            return $this->critical('OpenSSL extension is not loaded. HTTPS connections and encryption will not work.');
        }

        return $this->good(sprintf('OpenSSL extension is loaded (%s).', OPENSSL_VERSION_TEXT));
    }
}
