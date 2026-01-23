<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Mbstring Extension Health Check
 *
 * This check verifies that the PHP Multibyte String extension is loaded.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * The Mbstring extension is essential for proper UTF-8 and Unicode handling:
 * - Correct string length calculation for non-ASCII characters
 * - Proper text truncation without breaking multibyte characters
 * - Email encoding and internationalized content
 * - Search functionality with international characters
 * - Form validation for multilingual content
 * Without Mbstring, content in languages like Japanese, Chinese, Arabic, Russian,
 * and many others will display incorrectly or cause errors.
 *
 * RESULT MEANINGS:
 *
 * GOOD: Mbstring extension is loaded. UTF-8 and Unicode text will be handled correctly.
 *
 * CRITICAL: Mbstring extension is not available. Multilingual content will break,
 *           string operations may corrupt data, and international characters
 *           will not display properly. Contact your hosting provider to enable
 *           the mbstring extension.
 *
 * Note: This check does not produce WARNING results as Mbstring is required
 *       for Joomla's core string handling operations.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class MbstringExtensionCheck extends AbstractHealthCheck
{
    /**
     * Returns the unique identifier for this health check.
     *
     * @return string The check slug in format 'system.mbstring_extension'
     */
    public function getSlug(): string
    {
        return 'system.mbstring_extension';
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
     * Performs the Mbstring extension availability check.
     *
     * Verifies that the PHP Multibyte String extension is loaded. This extension
     * is essential for proper UTF-8 and Unicode handling, including correct string
     * length calculation, text truncation, email encoding, and form validation for
     * multilingual content. Without Mbstring, content in languages like Japanese,
     * Chinese, Arabic, Russian will display incorrectly or cause errors.
     *
     * @return HealthCheckResult Good status if Mbstring extension is loaded,
     *                            Critical status if Mbstring extension is not available
     */
    protected function performCheck(): HealthCheckResult
    {
        // Mbstring is required for Joomla's core string handling
        if (! extension_loaded('mbstring')) {
            return $this->critical('Mbstring extension is not loaded. This is required for proper UTF-8 handling.');
        }

        return $this->good('Mbstring extension is loaded.');
    }
}
