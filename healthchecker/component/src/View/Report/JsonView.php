<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace MySitesGuru\HealthChecker\Component\Administrator\View\Report;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\JsonView as BaseJsonView;

\defined('_JEXEC') || die;

/**
 * JSON View for Health Checker Report Export
 *
 * Generates a JSON export of all health check results. This view executes all
 * health checks and formats the output as a downloadable JSON file.
 *
 * Output includes:
 * - All health check results with full details
 * - Category metadata
 * - Provider metadata
 * - Summary statistics
 * - Execution timestamp
 *
 * The JSON file is formatted with pretty-printing for readability.
 *
 * @since 1.0.0
 */
class JsonView extends BaseJsonView
{
    /**
     * Display the JSON export
     *
     * Executes all health checks, formats the complete report as JSON, and sends
     * it as a downloadable file with appropriate headers.
     *
     * Filename format: health-report-YYYY-MM-DD.json
     *
     * This method terminates the application after sending the response.
     *
     * @param   string|null  $tpl  The name of the template file to parse (not used for JSON)
     *
     * @since   1.0.0
     */
    public function display($tpl = null): void
    {
        $cmsApplication = Factory::getApplication();

        /** @var \MySitesGuru\HealthChecker\Component\Administrator\Model\ReportModel $model */
        $model = $this->getModel();
        $model->runChecks();

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="health-report-' . date('Y-m-d') . '.json"');

        echo $model->toJson();

        $cmsApplication->close();
    }
}
