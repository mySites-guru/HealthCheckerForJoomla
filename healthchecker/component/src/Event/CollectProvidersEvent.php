<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace MySitesGuru\HealthChecker\Component\Administrator\Event;

use Joomla\CMS\Event\Result\ResultAware;
use Joomla\CMS\Event\Result\ResultAwareInterface;
use Joomla\Event\Event;
use MySitesGuru\HealthChecker\Component\Administrator\Provider\ProviderMetadata;

\defined('_JEXEC') || die;

/**
 * Event for collecting provider metadata from plugins.
 *
 * This event is dispatched during the health check initialization phase to allow
 * plugins to register metadata about themselves as health check providers. Provider
 * metadata includes information like plugin name, description, logo, version, and
 * website URL that will be displayed in the UI alongside checks from that provider.
 *
 * The event uses Joomla's ResultAware pattern, allowing multiple plugins to
 * contribute their provider information without overwriting each other's results.
 *
 * USAGE IN PLUGINS:
 * ```php
 * public function onHealthCheckerCollectProviders(CollectProvidersEvent $event): void
 * {
 *     $provider = new ProviderMetadata(
 *         slug: 'my_plugin',
 *         name: 'My Plugin',
 *         description: 'Provides custom health checks for My Plugin',
 *         url: 'https://example.com/myplugin',
 *         icon: 'fa-plugin',
 *         logoUrl: 'https://example.com/logo.png',
 *         version: '1.0.0'
 *     );
 *     $event->addResult($provider);
 * }
 * ```
 *
 * EVENT FLOW:
 * 1. Component dispatches this event first, before categories and checks
 * 2. All healthchecker plugins receive the event
 * 3. Plugins call addResult() to register their provider metadata
 * 4. Component calls getProviders() to retrieve all provider information
 * 5. Provider metadata is used to attribute checks and display plugin info in UI
 *
 * PROVIDER SLUG REQUIREMENTS:
 * - Must match the provider slug used in health checks
 * - Should be lowercase with underscores (e.g., 'core', 'akeeba_backup')
 * - Must be unique across all plugins
 *
 * @since 1.0.0
 */
final class CollectProvidersEvent extends Event implements ResultAwareInterface
{
    use ResultAware;

    public $arguments;

    /**
     * Constructs the CollectProvidersEvent.
     *
     * Initializes the event with the name from the HealthCheckerEvents enum
     * which plugins will listen for to register their provider metadata.
     */
    public function __construct()
    {
        parent::__construct(HealthCheckerEvents::COLLECT_PROVIDERS->value);
    }

    /**
     * Type-checks that added results are valid ProviderMetadata instances.
     *
     * This method is called automatically by the ResultAware trait when
     * addResult() is invoked. It ensures type safety by validating that
     * only ProviderMetadata objects are added to the event results.
     *
     * Provider metadata must be a readonly value object containing information
     * about the plugin providing health checks.
     *
     * @param mixed $data The data being added via addResult()
     */
    public function typeCheckResult(mixed $data): void
    {
        if (! $data instanceof ProviderMetadata) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Event %s only accepts ProviderMetadata instances. Got %s.',
                    $this->getName(),
                    get_debug_type($data),
                ),
            );
        }
    }

    /**
     * Retrieves all provider metadata registered by plugins.
     *
     * Returns an array of all ProviderMetadata instances that have been added
     * by plugins listening to this event. This metadata is used throughout the
     * UI to display plugin information, logos, and attribution for health checks.
     *
     * This method is called by the component after all plugins have had a chance
     * to register their provider information.
     *
     * @return ProviderMetadata[] Array of registered provider metadata instances
     */
    public function getProviders(): array
    {
        return $this->arguments['result'] ?? [];
    }
}
