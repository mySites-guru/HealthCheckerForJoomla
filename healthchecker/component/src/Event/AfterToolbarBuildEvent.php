<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace MySitesGuru\HealthChecker\Component\Administrator\Event;

use Joomla\CMS\Toolbar\Toolbar;
use Joomla\Event\Event;

\defined('_JEXEC') || die;

/**
 * Event triggered after the toolbar is built
 *
 * This event allows plugins to add custom toolbar buttons after the component's
 * built-in buttons have been added. Buttons added here will appear at the end
 * of the toolbar (before Options).
 *
 * @since 1.0.0
 */
final class AfterToolbarBuildEvent extends Event
{
    /**
     * Constructs the AfterToolbarBuildEvent.
     *
     * @param Toolbar $toolbar The toolbar instance to add buttons to
     */
    public function __construct(
        private readonly Toolbar $toolbar,
    ) {
        parent::__construct(HealthCheckerEvents::AFTER_TOOLBAR_BUILD->value);
    }

    /**
     * Get the toolbar instance
     *
     * @return Toolbar The toolbar instance
     *
     * @since 1.0.0
     */
    public function getToolbar(): Toolbar
    {
        return $this->toolbar;
    }
}
