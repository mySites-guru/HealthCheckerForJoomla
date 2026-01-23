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
use MySitesGuru\HealthChecker\Component\Administrator\Category\HealthCategory;

\defined('_JEXEC') || die;

/**
 * Event for collecting custom health check categories from plugins.
 *
 * This event is dispatched during the health check initialization phase to allow
 * third-party plugins to register custom categories that their checks will use.
 * Plugins listen for 'onHealthCheckerCollectCategories' and add HealthCategory
 * instances to this event.
 *
 * The event uses Joomla's ResultAware pattern, allowing multiple plugins to
 * contribute categories without overwriting each other's results.
 *
 * USAGE IN PLUGINS:
 * ```php
 * public function onHealthCheckerCollectCategories(CollectCategoriesEvent $event): void
 * {
 *     $category = new HealthCategory(
 *         slug: 'my_category',
 *         label: 'My Category',
 *         icon: 'fa-star',
 *         sort: 100
 *     );
 *     $event->addResult($category);
 * }
 * ```
 *
 * EVENT FLOW:
 * 1. Component dispatches this event before collecting checks
 * 2. All healthchecker plugins receive the event
 * 3. Plugins call addResult() to register their categories
 * 4. Component calls getCategories() to retrieve all registered categories
 * 5. Categories are merged with core categories and displayed in UI
 *
 * @since 1.0.0
 */
final class CollectCategoriesEvent extends Event implements ResultAwareInterface
{
    use ResultAware;

    public $arguments;

    /**
     * Constructs the CollectCategoriesEvent.
     *
     * Initializes the event with the name from the HealthCheckerEvents enum
     * which plugins will listen for to register custom categories.
     */
    public function __construct()
    {
        parent::__construct(HealthCheckerEvents::COLLECT_CATEGORIES->value);
    }

    /**
     * Type-checks that added results are valid HealthCategory instances.
     *
     * This method is called automatically by the ResultAware trait when
     * addResult() is invoked. It ensures type safety by validating that
     * only HealthCategory objects are added to the event results.
     *
     * @param mixed $data The data being added via addResult()
     */
    public function typeCheckResult(mixed $data): void
    {
        if (! $data instanceof HealthCategory) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Event %s only accepts HealthCategory instances. Got %s.',
                    $this->getName(),
                    get_debug_type($data),
                ),
            );
        }
    }

    /**
     * Retrieves all categories registered by plugins.
     *
     * Returns an array of all HealthCategory instances that have been added
     * by plugins listening to this event. Categories are returned in the order
     * they were added, though they will be sorted by their sort property later.
     *
     * This method is called by the component after all plugins have had a chance
     * to register their categories.
     *
     * @return HealthCategory[] Array of registered category instances
     */
    public function getCategories(): array
    {
        return $this->arguments['result'] ?? [];
    }
}
