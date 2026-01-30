<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace MySitesGuru\HealthChecker\Component\Administrator\Check;

use MySitesGuru\HealthChecker\Component\Administrator\Service\DescriptionSanitizer;

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
     * All parameters are required except provider, docsUrl, and actionUrl which have defaults.
     * The constructed object is fully immutable - all properties are readonly.
     *
     * @param HealthStatus $healthStatus The status of the check (Critical/Warning/Good)
     * @param string       $title        The human-readable title of the check
     * @param string       $description  Detailed description of the check result and any issues found
     * @param string       $slug         Unique identifier for the check (e.g., "core.php_version")
     * @param string       $category     Category slug the check belongs to (e.g., "system")
     * @param string       $provider     Provider slug that owns this check (defaults to "core")
     * @param string|null  $docsUrl      URL to documentation for this check (shown as ? icon)
     * @param string|null  $actionUrl    URL to navigate to when row is clicked
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

        /**
         * URL to documentation for this health check.
         *
         * When set, displays a (?) icon next to the check that opens
         * the documentation URL in a new tab when clicked.
         *
         * @var string|null The documentation URL or null if none
         * @since 3.0.36
         */
        public readonly ?string $docsUrl = null,

        /**
         * URL to navigate to when the result row is clicked.
         *
         * When set, makes the entire row clickable and navigates to
         * this URL (in the same window) when clicked.
         *
         * @var string|null The action URL or null if not clickable
         * @since 3.0.36
         */
        public readonly ?string $actionUrl = null,
    ) {}

    /**
     * Convert the result to an associative array for serialization.
     *
     * This is used for JSON encoding, session storage, and passing data to
     * JavaScript in the frontend. The health status enum is converted to its
     * string value. The title has all HTML stripped for security, while the
     * description is sanitized to allow only safe HTML formatting tags.
     *
     * @return array{status: string, title: string, description: string, slug: string, category: string, provider: string, docsUrl: string|null, actionUrl: string|null}
     *               Array representation of the result
     *
     * @since 1.0.0
     */
    public function toArray(): array
    {
        $descriptionSanitizer = new DescriptionSanitizer();

        return [
            'status' => $this->healthStatus->value,
            'title' => $this->stripAllHtml($this->title),
            'description' => $descriptionSanitizer->sanitize($this->description),
            'slug' => $this->slug,
            'category' => $this->category,
            'provider' => $this->provider,
            'docsUrl' => $this->docsUrl,
            'actionUrl' => $this->actionUrl,
        ];
    }

    /**
     * Strip all HTML from a string, including the content of script/style tags.
     *
     * This is more aggressive than strip_tags() because it removes the content
     * of dangerous tags like <script>, <style>, and <iframe>, not just the tags.
     *
     * @param string $text The text to clean
     *
     * @return string The text with all HTML removed
     *
     * @since 3.1.0
     */
    private function stripAllHtml(string $text): string
    {
        // First remove dangerous tag content entirely
        $text = (string) preg_replace('/<script\b[^>]*>[\s\S]*?<\/script>/i', '', $text);
        $text = (string) preg_replace('/<style\b[^>]*>[\s\S]*?<\/style>/i', '', $text);
        $text = (string) preg_replace('/<iframe\b[^>]*>[\s\S]*?<\/iframe>/i', '', $text);

        // Then strip all remaining tags
        return strip_tags($text);
    }

    /**
     * Create a HealthCheckResult from an array (deserialization).
     *
     * This is used to reconstruct HealthCheckResult objects from cached JSON data.
     * It's the inverse of toArray() and provides a safe alternative to unserialize().
     *
     * @param array{status: string, title: string, description: string, slug: string, category: string, provider?: string, docsUrl?: string|null, actionUrl?: string|null} $data
     *               Array representation of the result (as produced by toArray())
     *
     * @return self The reconstructed HealthCheckResult object
     *
     * @since 1.0.0
     */
    public static function fromArray(array $data): self
    {
        return new self(
            healthStatus: HealthStatus::from($data['status']),
            title: $data['title'],
            description: $data['description'],
            slug: $data['slug'],
            category: $data['category'],
            provider: $data['provider'] ?? 'core',
            docsUrl: $data['docsUrl'] ?? null,
            actionUrl: $data['actionUrl'] ?? null,
        );
    }
}
