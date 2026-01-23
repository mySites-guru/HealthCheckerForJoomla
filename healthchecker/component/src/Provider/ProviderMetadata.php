<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace MySitesGuru\HealthChecker\Component\Administrator\Provider;

\defined('_JEXEC') || die;

/**
 * Immutable value object containing metadata about a health check provider.
 *
 * A provider represents a plugin or component that supplies health checks to the
 * Health Checker system. The core component is a provider, and each third-party
 * plugin that adds checks is also a provider.
 *
 * Provider metadata is used for:
 * - Attribution in the UI (showing which plugin provided each check)
 * - Branding (logo, icon, colors)
 * - Documentation links
 * - Version information
 *
 * All properties are readonly to ensure immutability after creation.
 *
 * @since 1.0.0
 */
final class ProviderMetadata
{
    /**
     * Create new provider metadata.
     *
     * Only slug and name are required. All other metadata is optional but
     * recommended for better user experience and attribution.
     *
     * @param string      $slug        Unique identifier for the provider (e.g., "core", "akeeba_backup")
     * @param string      $name        Human-readable name (e.g., "Health Checker for Joomla", "Akeeba Backup")
     * @param string      $description Brief description of what checks this provider offers
     * @param string|null $url         URL to provider's website or documentation
     * @param string|null $icon        FontAwesome icon class for the provider
     * @param string|null $logoUrl     URL to provider's logo image
     * @param string|null $version     Version string of the provider plugin
     *
     * @since 1.0.0
     */
    public function __construct(
        /**
         * Unique slug identifier for this provider.
         *
         * Used to associate checks with their provider. Should be lowercase
         * with underscores (e.g., "core", "akeeba_backup", "my_plugin").
         * This slug is used as the first part of check slugs.
         *
         * @var string The provider slug
         * @since 1.0.0
         */
        public readonly string $slug,

        /**
         * Human-readable name of the provider.
         *
         * Displayed in the UI for attribution purposes. Examples:
         * "Health Checker for Joomla", "Akeeba Backup", "My Plugin Name"
         *
         * @var string The provider name
         * @since 1.0.0
         */
        public readonly string $name,

        /**
         * Brief description of the provider and its checks.
         *
         * Explains what types of checks this provider offers. For example:
         * "Built-in health checks" or "Backup monitoring and verification"
         *
         * @var string The provider description
         * @since 1.0.0
         */
        public readonly string $description = '',

        /**
         * URL to the provider's website or documentation.
         *
         * Used for "Learn More" links in the UI. Should point to relevant
         * documentation or the plugin's homepage.
         *
         * @var string|null The provider URL or null
         * @since 1.0.0
         */
        public readonly ?string $url = null,

        /**
         * FontAwesome 6 icon class for the provider.
         *
         * Used for visual representation in the UI. Examples:
         * "fa-heartbeat", "fa-shield-halved", "fa-database"
         * FontAwesome 6 is bundled with Joomla 5.
         *
         * @var string|null FontAwesome icon class or null
         * @since 1.0.0
         */
        public readonly ?string $icon = null,

        /**
         * URL to the provider's logo image.
         *
         * If provided, displayed for branding and attribution in the UI.
         * Particularly useful for third-party plugins to maintain brand identity.
         *
         * @var string|null URL to logo image or null
         * @since 1.0.0
         */
        public readonly ?string $logoUrl = null,

        /**
         * Version string of the provider plugin.
         *
         * Displayed in the UI to help with debugging and support. Should follow
         * semantic versioning format (e.g., "1.0.0", "2.1.3").
         *
         * @var string|null Version string or null
         * @since 1.0.0
         */
        public readonly ?string $version = null,
    ) {}

    /**
     * Convert the provider metadata to an associative array.
     *
     * This is used for JSON serialization, session storage, and passing data
     * to the UI. All properties are included even if null.
     *
     * @return array{slug: string, name: string, description: string, url: string|null, icon: string|null, logoUrl: string|null, version: string|null}
     *               Array representation of the provider metadata
     *
     * @since 1.0.0
     */
    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'url' => $this->url,
            'icon' => $this->icon,
            'logoUrl' => $this->logoUrl,
            'version' => $this->version,
        ];
    }
}
