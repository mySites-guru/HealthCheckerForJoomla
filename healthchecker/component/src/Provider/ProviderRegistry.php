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
 * Registry for managing health check provider metadata.
 *
 * This registry maintains a collection of all registered providers (plugins and
 * components that supply health checks). It automatically registers the core
 * provider on instantiation and allows third-party plugins to register themselves
 * via the CollectProvidersEvent.
 *
 * The registry is used throughout the system for:
 * - Looking up provider metadata by slug
 * - Displaying attribution in the UI
 * - Filtering checks by provider
 * - Listing third-party integrations
 *
 * @since 1.0.0
 */
final class ProviderRegistry
{
    /**
     * Internal storage of registered providers indexed by slug.
     *
     * @var array<string, ProviderMetadata>
     * @since 1.0.0
     */
    private array $providers = [];

    /**
     * Initialize the registry and register the core provider.
     *
     * The core provider is automatically registered with metadata about the
     * Health Checker component itself. Third-party providers are registered
     * later via the register() method when plugins handle the
     * CollectProvidersEvent.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->registerCoreProvider();
    }

    /**
     * Register the built-in core provider.
     *
     * This private method is called during construction to ensure the core
     * provider is always available. The core provider represents the Health
     * Checker component and its built-in checks.
     *
     * @since 1.0.0
     */
    private function registerCoreProvider(): void
    {
        $this->register(new ProviderMetadata(
            slug: 'core',
            name: 'Health Checker for Joomla',
            description: 'Built-in health checks',
            url: 'https://github.com/user/health-checker-for-joomla',
            icon: 'fa-heartbeat',
            version: '1.0.0',
        ));
    }

    /**
     * Register a new provider in the registry.
     *
     * Called by plugins during the CollectProvidersEvent to register their
     * metadata. If a provider with the same slug already exists, it will be
     * replaced (last registration wins).
     *
     * @param ProviderMetadata $providerMetadata The provider metadata to register
     *
     * @since 1.0.0
     */
    public function register(ProviderMetadata $providerMetadata): void
    {
        $this->providers[$providerMetadata->slug] = $providerMetadata;
    }

    /**
     * Retrieve a provider's metadata by slug.
     *
     * Used to look up provider information when displaying checks in the UI
     * or generating reports. Returns null if the provider is not registered.
     *
     * @param string $slug The provider slug to look up
     *
     * @return ProviderMetadata|null The provider metadata or null if not found
     *
     * @since 1.0.0
     */
    public function get(string $slug): ?ProviderMetadata
    {
        return $this->providers[$slug] ?? null;
    }

    /**
     * Check if a provider with the given slug is registered.
     *
     * Useful for conditional logic when a specific provider may or may not
     * be available (e.g., checking if an optional integration plugin is active).
     *
     * @param string $slug The provider slug to check
     *
     * @return bool True if the provider is registered, false otherwise
     *
     * @since 1.0.0
     */
    public function has(string $slug): bool
    {
        return isset($this->providers[$slug]);
    }

    /**
     * Get all registered providers.
     *
     * Returns an associative array of all providers indexed by their slug.
     * This includes both the core provider and all third-party providers.
     *
     * @return array<string, ProviderMetadata> All registered providers
     *
     * @since 1.0.0
     */
    public function all(): array
    {
        return $this->providers;
    }

    /**
     * Get only third-party (non-core) providers.
     *
     * Returns an array of provider metadata objects for all providers except
     * the core provider. Useful for displaying "Integrations" or "Third-party
     * plugins" sections in the UI. The array is re-indexed numerically.
     *
     * @return ProviderMetadata[] Array of third-party provider metadata
     *
     * @since 1.0.0
     */
    public function getThirdParty(): array
    {
        return array_filter(
            $this->providers,
            fn(ProviderMetadata $providerMetadata): bool => $providerMetadata->slug !== 'core',
        );
    }
}
