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
 * Immutable value object representing a health check result.
 *
 * This class encapsulates all information about a single health check execution,
 * including its status, descriptive information, and categorization metadata.
 * All properties are readonly to ensure immutability after creation.
 *
 * Instances are typically created by health check classes using the helper
 * methods in AbstractHealthCheck: critical(), warning(), or good().
 *
 * @since 1.0.0
 */
final class HealthCheckResult
{
    /**
     * Create a new health check result.
     *
     * All parameters are required except provider which defaults to "core".
     * The constructed object is fully immutable - all properties are readonly.
     *
     * @param HealthStatus $healthStatus The status of the check (Critical/Warning/Good)
     * @param string       $title        The human-readable title of the check
     * @param string       $description  Detailed description of the check result and any issues found
     * @param string       $slug         Unique identifier for the check (e.g., "core.php_version")
     * @param string       $category     Category slug the check belongs to (e.g., "system")
     * @param string       $provider     Provider slug that owns this check (defaults to "core")
     *
     * @since 1.0.0
     */
    public function __construct(
        /**
         * The health status indicating severity level.
         *
         * @var HealthStatus Critical, Warning, or Good
         * @since 1.0.0
         */
        public readonly HealthStatus $healthStatus,

        /**
         * Human-readable title of the health check.
         *
         * This is typically the translated version from language files.
         *
         * @var string The check title
         * @since 1.0.0
         */
        public readonly string $title,

        /**
         * Detailed description of the check result.
         *
         * Should explain what was checked and the outcome, including any
         * specific values, recommendations, or actions needed.
         *
         * @var string The result description
         * @since 1.0.0
         */
        public readonly string $description,

        /**
         * Unique slug identifier for this check.
         *
         * Format: {provider}.{check_name} (e.g., "core.php_version")
         *
         * @var string The unique slug
         * @since 1.0.0
         */
        public readonly string $slug,

        /**
         * Category slug this check belongs to.
         *
         * One of the standard categories (system, database, security, etc.)
         * or a custom category registered by a plugin.
         *
         * @var string The category slug
         * @since 1.0.0
         */
        public readonly string $category,

        /**
         * Provider slug identifying the owner of this check.
         *
         * Core checks use "core", third-party plugins use their own slug.
         *
         * @var string The provider slug
         * @since 1.0.0
         */
        public readonly string $provider = 'core',
    ) {}

    /**
     * Convert the result to an associative array for serialization.
     *
     * This is used for JSON encoding, session storage, and passing data to
     * JavaScript in the frontend. The health status enum is converted to its
     * string value.
     *
     * @return array{status: string, title: string, description: string, slug: string, category: string, provider: string}
     *               Array representation of the result
     *
     * @since 1.0.0
     */
    public function toArray(): array
    {
        return [
            'status' => $this->healthStatus->value,
            'title' => $this->title,
            'description' => $this->description,
            'slug' => $this->slug,
            'category' => $this->category,
            'provider' => $this->provider,
        ];
    }
}
