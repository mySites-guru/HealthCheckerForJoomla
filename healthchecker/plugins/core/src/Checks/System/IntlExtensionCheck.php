<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Intl Extension Health Check
 *
 * This check verifies that the PHP Internationalization (Intl) extension is loaded.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * The Intl extension provides advanced internationalization features:
 * - Locale-aware date and time formatting
 * - Number and currency formatting per locale
 * - Collation (sorting) for different languages
 * - Transliteration and Unicode normalization
 * - IDN (Internationalized Domain Names) support
 * While Joomla can function without Intl, some internationalization features
 * will fall back to basic implementations or may not work correctly.
 *
 * RESULT MEANINGS:
 *
 * GOOD: Intl extension is loaded. Full internationalization support is available
 *       for date/time formatting, number formatting, and locale-aware operations.
 *
 * WARNING: Intl extension is not available. Basic operations will still work,
 *          but some internationalization features may display incorrectly or
 *          use fallback formatting. Consider enabling the intl extension for
 *          better multilingual support.
 *
 * Note: This check does not produce CRITICAL results as Joomla can operate
 *       with reduced functionality without Intl.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class IntlExtensionCheck extends AbstractHealthCheck
{
    /**
     * Returns the unique identifier for this health check.
     *
     * @return string The check slug in format 'system.intl_extension'
     */
    public function getSlug(): string
    {
        return 'system.intl_extension';
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
     * Performs the Intl extension availability check.
     *
     * Verifies that the PHP Internationalization extension is loaded. This extension
     * provides advanced internationalization features including locale-aware date/time
     * formatting, number and currency formatting, collation (sorting) for different
     * languages, transliteration, Unicode normalization, and IDN support. While Joomla
     * can function without Intl, some internationalization features will fall back to
     * basic implementations or may not work correctly.
     *
     * @return HealthCheckResult Good status if Intl extension is loaded,
     *                            Warning status if Intl extension is not available
     */
    /**
     * Perform the Intl Extension health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        // Intl provides enhanced internationalization but is not strictly required
        if (! extension_loaded('intl')) {
            return $this->warning(
                'Intl extension is not loaded. Some internationalization features may not work correctly.',
            );
        }

        return $this->good('Intl extension is loaded.');
    }
}
