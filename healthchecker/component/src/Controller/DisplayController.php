<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace MySitesGuru\HealthChecker\Component\Administrator\Controller;

use Joomla\CMS\MVC\Controller\BaseController;

\defined('_JEXEC') || die;

/**
 * Display Controller for Health Checker Component
 *
 * Handles the main display logic for the component, routing requests to the appropriate views.
 * This controller extends Joomla's BaseController and sets the default view to 'report'.
 *
 * @since 1.0.0
 */
class DisplayController extends BaseController
{
    /**
     * The default view for the component
     *
     * @var string
     * @since 1.0.0
     */
    protected $default_view = 'report';

    /**
     * Display the view
     *
     * Overrides the parent display method to provide the default view routing.
     * This method is the entry point for most component requests in the admin area.
     *
     * @param   bool   $cachable   Whether the view output can be cached
     * @param   array  $urlparams  An associative array of safe URL parameters and their variable types
     *
     * @return  static  The controller instance for method chaining
     *
     * @since   1.0.0
     */
    public function display($cachable = false, $urlparams = []): static
    {
        return parent::display($cachable, $urlparams);
    }
}
