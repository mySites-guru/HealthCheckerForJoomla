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
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckInterface;

\defined('_JEXEC') || die;

/**
 * Event for collecting health check instances from plugins.
 *
 * This event is dispatched during health check execution to gather all available
 * health checks from the core plugin and any third-party healthchecker plugins.
 * Plugins listen for 'onHealthCheckerCollectChecks' and add HealthCheckInterface
 * instances to this event.
 *
 * The event uses Joomla's ResultAware pattern, allowing multiple plugins to
 * contribute health checks without overwriting each other's results.
 *
 * USAGE IN PLUGINS:
 * ```php
 * public function onHealthCheckerCollectChecks(CollectChecksEvent $event): void
 * {
 *     // Register multiple checks
 *     $event->addResult(new MyCustomCheck1($this->getApplication(), $this->getDatabase()));
 *     $event->addResult(new MyCustomCheck2($this->getApplication(), $this->getDatabase()));
 * }
 * ```
 *
 * EVENT FLOW:
 * 1. Component dispatches this event when "Run Health Check" is clicked
 * 2. All healthchecker plugins receive the event
 * 3. Plugins instantiate their check classes and call addResult() for each
 * 4. Component calls getChecks() to retrieve all registered checks
 * 5. HealthCheckRunner executes all checks in parallel via AJAX
 *
 * IMPORTANT NOTES:
 * - Checks must implement HealthCheckInterface
 * - Each check should have a unique slug (format: provider.check_name)
 * - Checks are auto-discovered; no manual registration needed
 * - Checks are executed independently and may run in parallel
 *
 * @since 1.0.0
 */
final class CollectChecksEvent extends Event implements ResultAwareInterface
{
    use ResultAware;

    public $arguments;

    /**
     * Constructs the CollectChecksEvent.
     *
     * Initializes the event with the name from the HealthCheckerEvents enum
     * which plugins will listen for to register their health check instances.
     */
    public function __construct()
    {
        parent::__construct(HealthCheckerEvents::COLLECT_CHECKS->value);
    }

    /**
     * Type-checks that added results are valid HealthCheckInterface instances.
     *
     * This method is called automatically by the ResultAware trait when
     * addResult() is invoked. It ensures type safety by validating that
     * only objects implementing HealthCheckInterface are added to the event results.
     *
     * Each health check must implement the HealthCheckInterface contract, which
     * includes methods for getSlug(), getTitle(), getCategory(), getProvider(),
     * and run().
     *
     * @param mixed $data The data being added via addResult()
     */
    public function typeCheckResult(mixed $data): void
    {
        if (! $data instanceof HealthCheckInterface) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Event %s only accepts HealthCheckInterface instances. Got %s.',
                    $this->getName(),
                    get_debug_type($data),
                ),
            );
        }
    }

    /**
     * Retrieves all health checks registered by plugins.
     *
     * Returns an array of all HealthCheckInterface instances that have been added
     * by plugins listening to this event. This includes both core checks from
     * plg_healthchecker_core and any third-party plugin checks.
     *
     * This method is called by the component after all plugins have had a chance
     * to register their checks. The returned checks are then executed by the
     * HealthCheckRunner.
     *
     * @return HealthCheckInterface[] Array of registered health check instances
     */
    public function getChecks(): array
    {
        return $this->arguments['result'] ?? [];
    }
}
