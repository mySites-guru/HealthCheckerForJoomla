<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace MySitesGuru\HealthChecker\Component\Administrator\View\Report;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use MySitesGuru\HealthChecker\Component\Administrator\Event\AfterToolbarBuildEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\BeforeReportDisplayEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\HealthCheckerEvents;

\defined('_JEXEC') || die;

/**
 * HTML View for Health Checker Report
 *
 * Displays the main health check report interface in the Joomla administrator.
 * This view handles the UI shell - the actual checks are executed via AJAX
 * requests handled by the AjaxController.
 *
 * The view sets up:
 * - Page toolbar with actions (Run Again, Export, Preferences)
 * - Configuration-based feature toggles (mySites.guru banner)
 * - Layout template with category-based check display
 *
 * @since 1.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * HTML content to inject before the report display
     *
     * Populated by plugins via the BeforeReportDisplayEvent.
     * Allows plugins to inject banners, notices, or other content.
     *
     * @since 1.0.0
     */
    public string $beforeReportHtml = '';

    /**
     * Display the health check report view
     *
     * Loads component configuration, sets up the toolbar, and renders the template.
     * The template contains JavaScript that executes health checks via AJAX.
     *
     * @param   string|null  $tpl  The name of the template file to parse (optional)
     *
     * @since   1.0.0
     */
    public function display($tpl = null): void
    {
        // Dispatch event to allow plugins to inject content before report display
        $beforeReportDisplayEvent = new BeforeReportDisplayEvent();
        Factory::getApplication()->getDispatcher()->dispatch(
            HealthCheckerEvents::BEFORE_REPORT_DISPLAY->value,
            $beforeReportDisplayEvent,
        );
        $this->beforeReportHtml = $beforeReportDisplayEvent->getHtmlContent();

        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * Add the page toolbar with actions and export options
     *
     * Creates the admin toolbar with:
     * - "Run Again" button to re-execute health checks
     * - Export dropdown with JSON and HTML export options
     * - Component preferences (if user has core.admin permission)
     *
     * All export URLs include CSRF tokens for security.
     *
     * @since   1.0.0
     */
    protected function addToolbar(): void
    {
        ToolbarHelper::title(Text::_('COM_HEALTHCHECKER_REPORT'), 'heartbeat');

        $toolbar = Toolbar::getInstance('toolbar');

        $toolbar->standardButton('refresh', 'COM_HEALTHCHECKER_RUN_AGAIN')
            ->icon('icon-refresh')
            ->onclick('runHealthChecks(); return false;')
            ->buttonClass('btn btn-success');

        $token = Session::getFormToken();
        $jsonUrl = Route::_('index.php?option=com_healthchecker&view=report&format=json&' . $token . '=1', false);
        $htmlUrl = Route::_('index.php?option=com_healthchecker&view=report&format=htmlexport&' . $token . '=1', false);

        $dropdown = $toolbar->dropdownButton('export')
            ->text('COM_HEALTHCHECKER_EXPORT')
            ->toggleSplit(false)
            ->icon('icon-download')
            ->buttonClass('btn btn-action');

        $childBar = $dropdown->getChildToolbar();

        $childBar->linkButton('export-json')
            ->text('COM_HEALTHCHECKER_EXPORT_JSON')
            ->url($jsonUrl)
            ->icon('icon-code');

        $childBar->linkButton('export-html')
            ->text('COM_HEALTHCHECKER_EXPORT_HTML')
            ->url($htmlUrl)
            ->icon('icon-file');

        $toolbar->linkButton('github')
            ->text('COM_HEALTHCHECKER_GITHUB')
            ->url('https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/CONTRIBUTING.md')
            ->icon('icon-code-branch')
            ->attributes([
                'target' => '_blank',
                'style' => 'text-decoration:none',
            ])
            ->buttonClass('btn btn-primary healthchecker-no-external-icon');

        $toolbar->linkButton('community-plugins')
            ->text('COM_HEALTHCHECKER_COMMUNITY_PLUGINS')
            ->url('https://www.joomlahealthchecker.com/docs/integrations/community-plugins')
            ->icon('icon-puzzle-piece')
            ->attributes([
                'target' => '_blank',
                'style' => 'text-decoration:none',
            ])
            ->buttonClass('btn btn-primary healthchecker-no-external-icon');

        // Dispatch event to allow plugins to add toolbar buttons
        $cmsApplication = Factory::getApplication();
        $afterToolbarBuildEvent = new AfterToolbarBuildEvent($toolbar);
        $cmsApplication->getDispatcher()
            ->dispatch(HealthCheckerEvents::AFTER_TOOLBAR_BUILD->value, $afterToolbarBuildEvent);

        $user = $cmsApplication->getIdentity();

        if ($user !== null && $user->authorise('core.admin', 'com_healthchecker')) {
            ToolbarHelper::preferences('com_healthchecker');
        }
    }
}
