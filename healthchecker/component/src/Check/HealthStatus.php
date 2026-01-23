<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace MySitesGuru\HealthChecker\Component\Administrator\Check;

\defined('_JEXEC') || die;

/**
 * Health check status enumeration.
 *
 * Defines the three possible states for a health check result:
 * - Critical: Site is broken, severely compromised, or data is at risk
 * - Warning: Issue should be addressed but site still functions
 * - Good: Everything is optimal, no issues found
 *
 * This enum is backed by string values for serialization and provides
 * helper methods for UI rendering (labels, icons, CSS classes, sorting).
 *
 * @since 1.0.0
 */
enum HealthStatus: string
{
    /**
     * Critical status - requires immediate attention.
     *
     * Indicates the site is broken, severely compromised, or data is at risk.
     * These issues should be addressed immediately.
     *
     * @since 1.0.0
     */
    case Critical = 'critical';

    /**
     * Warning status - should be addressed but not urgent.
     *
     * Indicates an issue that should be addressed but doesn't prevent the site
     * from functioning. Represents potential problems or areas for improvement.
     *
     * @since 1.0.0
     */
    case Warning = 'warning';

    /**
     * Good status - everything is optimal.
     *
     * Indicates the check completed successfully and found no issues.
     * Everything is working as expected.
     *
     * @since 1.0.0
     */
    case Good = 'good';

    /**
     * Get the language key for this status label.
     *
     * Returns a language key constant that should be translated for display.
     * The actual translation is handled by the calling code using Joomla's
     * Text::_() function.
     *
     * @return string Language key constant (not translated)
     *
     * @since 1.0.0
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Critical => 'COM_HEALTHCHECKER_STATUS_CRITICAL',
            self::Warning => 'COM_HEALTHCHECKER_STATUS_WARNING',
            self::Good => 'COM_HEALTHCHECKER_STATUS_GOOD',
        };
    }

    /**
     * Get the FontAwesome icon class for this status.
     *
     * Returns the icon class name to be used in UI rendering. Icons are from
     * FontAwesome 6 (bundled with Joomla 5).
     *
     * - Critical: fa-times-circle (X in circle)
     * - Warning: fa-exclamation-triangle (triangle with exclamation)
     * - Good: fa-check-circle (checkmark in circle)
     *
     * @return string FontAwesome icon class name
     *
     * @since 1.0.0
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::Critical => 'fa-times-circle',
            self::Warning => 'fa-exclamation-triangle',
            self::Good => 'fa-check-circle',
        };
    }

    /**
     * Get the Bootstrap badge CSS classes for this status.
     *
     * Returns Bootstrap 5 badge classes for styling status indicators in the UI.
     *
     * - Critical: bg-danger (red)
     * - Warning: bg-warning text-dark (yellow with dark text)
     * - Good: bg-success (green)
     *
     * @return string Bootstrap 5 CSS classes
     *
     * @since 1.0.0
     */
    public function getBadgeClass(): string
    {
        return match ($this) {
            self::Critical => 'bg-danger',
            self::Warning => 'bg-warning text-dark',
            self::Good => 'bg-success',
        };
    }

    /**
     * Get the sort order priority for this status.
     *
     * Lower numbers appear first when sorting. This ensures critical issues
     * are displayed at the top of lists, followed by warnings, then good results.
     *
     * - Critical: 1 (highest priority)
     * - Warning: 2 (medium priority)
     * - Good: 3 (lowest priority)
     *
     * @return int Sort order value (1-3)
     *
     * @since 1.0.0
     */
    public function getSortOrder(): int
    {
        return match ($this) {
            self::Critical => 1,
            self::Warning => 2,
            self::Good => 3,
        };
    }
}
