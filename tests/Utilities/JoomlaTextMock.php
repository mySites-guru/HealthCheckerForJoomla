<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace Joomla\CMS\Language;

/**
 * Mock Text class for unit tests
 *
 * This provides a simple implementation of Joomla's Text::_() method
 * that returns the language key unchanged for constants, or the original string.
 */
class Text
{
    /**
     * Mock translation method
     *
     * @param string $string The language key
     *
     * @return string The language key or original string
     */
    public static function _(string $string): string
    {
        // If it looks like a constant (ALL_CAPS with underscores), return it unchanged
        // Otherwise return the string as-is (for fallback behavior)
        if (preg_match('/^[A-Z_]+$/', $string)) {
            return $string;
        }

        return $string;
    }

    /**
     * Mock sprintf translation method
     *
     * @param string $string The language key
     * @param mixed  ...$args Arguments for sprintf
     *
     * @return string The formatted string
     */
    public static function sprintf(string $string, mixed ...$args): string
    {
        // For mock, just return the formatted string with the error message
        if ($args !== []) {
            return $args[0];
        }

        return $string;
    }
}
