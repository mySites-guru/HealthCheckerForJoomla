<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace MySitesGuru\HealthChecker\Component\Administrator\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
use MySitesGuru\HealthChecker\Component\Administrator\Extension\HealthCheckerComponent;

\defined('_JEXEC') || die;

/**
 * Ajax Controller for Health Checker Component
 *
 * Handles all AJAX requests for the health checker, including:
 * - Getting metadata (categories, providers, check list)
 * - Running health checks (by category or individual check)
 * - Getting summary statistics for the dashboard module
 * - Clearing the cache
 *
 * All methods in this controller:
 * - Validate CSRF tokens using Session::checkToken()
 * - Check user permissions (core.manage on com_healthchecker)
 * - Return JSON responses via JsonResponse
 * - Close the application after sending response
 *
 * @since 1.0.0
 */
class AjaxController extends BaseController
{
    /**
     * Get metadata without running checks
     *
     * Returns categories, providers, and the complete check list without executing any checks.
     * This is used by the frontend to build the UI before running checks.
     *
     * Response format:
     * {
     *   "categories": [...],
     *   "providers": [...],
     *   "checks": [...]
     * }
     *
     * @since   1.0.0
     */
    public function metadata(): void
    {
        $cmsApplication = Factory::getApplication();

        if (! Session::checkToken('get')) {
            $cmsApplication->setHeader('Content-Type', 'application/json');
            echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
            $cmsApplication->close();
            return;
        }

        $user = $cmsApplication->getIdentity();

        if ($user === null || ! $user->authorise('core.manage', 'com_healthchecker')) {
            $cmsApplication->setHeader('Content-Type', 'application/json');
            echo new JsonResponse(null, Text::_('JERROR_ALERTNOAUTHOR'), true);
            $cmsApplication->close();
            return;
        }

        try {
            /** @var HealthCheckerComponent $component */
            $component = $cmsApplication->bootComponent('com_healthchecker');
            $runner = $component->getHealthCheckRunner();

            $data = $runner->getMetadata();

            $cmsApplication->setHeader('Content-Type', 'application/json');
            echo new JsonResponse($data);
        } catch (\Exception $exception) {
            $cmsApplication->setHeader('Content-Type', 'application/json');
            echo new JsonResponse(null, $exception->getMessage(), true);
        }

        $cmsApplication->close();
    }

    /**
     * Run all health checks in a specific category
     *
     * Executes all health checks belonging to the specified category and returns their results.
     * The category slug is provided via the 'category' GET/POST parameter.
     *
     * Response format:
     * {
     *   "category": "system",
     *   "results": [...]
     * }
     *
     * @since   1.0.0
     */
    public function category(): void
    {
        $cmsApplication = Factory::getApplication();


        if (! Session::checkToken('post') && ! Session::checkToken('get')) {
            $cmsApplication->setHeader('Content-Type', 'application/json');
            echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
            $cmsApplication->close();
            return;
        }

        $user = $cmsApplication->getIdentity();

        if ($user === null || ! $user->authorise('core.manage', 'com_healthchecker')) {
            $cmsApplication->setHeader('Content-Type', 'application/json');
            echo new JsonResponse(null, Text::_('JERROR_ALERTNOAUTHOR'), true);
            $cmsApplication->close();
            return;
        }

        $category = $cmsApplication->getInput()
            ->getString('category', '');

        if (empty($category)) {
            $cmsApplication->setHeader('Content-Type', 'application/json');
            echo new JsonResponse(null, 'Missing category', true);
            $cmsApplication->close();
            return;
        }

        try {
            /** @var HealthCheckerComponent $component */
            $component = $cmsApplication->bootComponent('com_healthchecker');
            $runner = $component->getHealthCheckRunner();

            $results = $runner->runCategory($category);

            $cmsApplication->setHeader('Content-Type', 'application/json');
            echo new JsonResponse([
                'category' => $category,
                'results' => $results,
            ]);
        } catch (\Exception $exception) {
            $cmsApplication->setHeader('Content-Type', 'application/json');
            echo new JsonResponse(null, $exception->getMessage(), true);
        }

        $cmsApplication->close();
    }

    /**
     * Run a single health check by slug
     *
     * Executes a single health check identified by its slug (e.g., 'core.php_version').
     * The slug is provided via the 'slug' GET parameter.
     *
     * Returns a 404 error if the check slug is not found in the registry.
     *
     * Response format:
     * {
     *   "slug": "core.php_version",
     *   "title": "PHP Version",
     *   "status": "good",
     *   "description": "...",
     *   ...
     * }
     *
     * @since   1.0.0
     */
    public function check(): void
    {
        $cmsApplication = Factory::getApplication();


        if (! Session::checkToken('get')) {
            $cmsApplication->setHeader('Content-Type', 'application/json');
            echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
            $cmsApplication->close();
            return;
        }

        $user = $cmsApplication->getIdentity();

        if ($user === null || ! $user->authorise('core.manage', 'com_healthchecker')) {
            $cmsApplication->setHeader('Content-Type', 'application/json');
            echo new JsonResponse(null, Text::_('JERROR_ALERTNOAUTHOR'), true);
            $cmsApplication->close();
            return;
        }

        $slug = $cmsApplication->getInput()
            ->getString('slug', '');

        if (empty($slug)) {
            $cmsApplication->setHeader('Content-Type', 'application/json');
            echo new JsonResponse(null, 'Missing check slug', true);
            $cmsApplication->close();
            return;
        }

        try {
            /** @var HealthCheckerComponent $component */
            $component = $cmsApplication->bootComponent('com_healthchecker');
            $runner = $component->getHealthCheckRunner();

            $result = $runner->runSingleCheck($slug);

            if ($result === null) {
                $cmsApplication->setHeader('Content-Type', 'application/json');
                echo new JsonResponse(null, 'Check not found: ' . $slug, true);
                $cmsApplication->close();
                return;
            }

            $cmsApplication->setHeader('Content-Type', 'application/json');
            echo new JsonResponse($result->toArray());
        } catch (\Exception $exception) {
            $cmsApplication->setHeader('Content-Type', 'application/json');
            echo new JsonResponse(null, $exception->getMessage(), true);
        }

        $cmsApplication->close();
    }

    /**
     * Get summary statistics for the dashboard module
     *
     * Executes all health checks and returns aggregated counts (critical, warning, good, total).
     * This method supports optional caching to reduce server load for frequent dashboard requests.
     *
     * GET Parameters:
     * - cache (int): Set to 1 to enable caching (default: 0)
     * - cache_ttl (int): Cache time-to-live in seconds (default: 900 = 15 minutes)
     *
     * Response format:
     * {
     *   "critical": 2,
     *   "warning": 5,
     *   "good": 119,
     *   "total": 126,
     *   "lastRun": "2026-01-14T10:30:00+00:00"
     * }
     *
     * @since   1.0.0
     */
    public function stats(): void
    {
        $cmsApplication = Factory::getApplication();


        if (! Session::checkToken('get')) {
            $cmsApplication->setHeader('Content-Type', 'application/json');
            echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
            $cmsApplication->close();
            return;
        }

        $user = $cmsApplication->getIdentity();

        if ($user === null || ! $user->authorise('core.manage', 'com_healthchecker')) {
            $cmsApplication->setHeader('Content-Type', 'application/json');
            echo new JsonResponse(null, Text::_('JERROR_ALERTNOAUTHOR'), true);
            $cmsApplication->close();
            return;
        }

        try {
            /** @var HealthCheckerComponent $component */
            $component = $cmsApplication->bootComponent('com_healthchecker');
            $runner = $component->getHealthCheckRunner();

            $input = $cmsApplication->getInput();
            $useCache = $input->getInt('cache', 0) === 1;
            $cacheTtl = $input->getInt('cache_ttl', 900);

            if ($useCache && $cacheTtl > 0) {
                $data = $runner->getStatsWithCache($cacheTtl);
            } else {
                $runner->run();
                $data = [
                    'critical' => $runner->getCriticalCount(),
                    'warning' => $runner->getWarningCount(),
                    'good' => $runner->getGoodCount(),
                    'total' => $runner->getTotalCount(),
                    'lastRun' => $runner->getLastRun()?->format('c'),
                ];
            }

            $cmsApplication->setHeader('Content-Type', 'application/json');
            echo new JsonResponse($data);
        } catch (\Exception $exception) {
            $cmsApplication->setHeader('Content-Type', 'application/json');
            echo new JsonResponse(null, $exception->getMessage(), true);
        }

        $cmsApplication->close();
    }

    /**
     * Clear the health check cache
     *
     * Removes all cached health check results from the session.
     * This forces the next stats() or run() call to re-execute all checks.
     *
     * Response format:
     * {
     *   "success": true,
     *   "message": "Cache cleared successfully"
     * }
     *
     * @since   1.0.0
     */
    public function clearCache(): void
    {
        $cmsApplication = Factory::getApplication();


        if (! Session::checkToken('get')) {
            $cmsApplication->setHeader('Content-Type', 'application/json');
            echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
            $cmsApplication->close();
            return;
        }

        $user = $cmsApplication->getIdentity();

        if ($user === null || ! $user->authorise('core.manage', 'com_healthchecker')) {
            $cmsApplication->setHeader('Content-Type', 'application/json');
            echo new JsonResponse(null, Text::_('JERROR_ALERTNOAUTHOR'), true);
            $cmsApplication->close();
            return;
        }

        try {
            /** @var HealthCheckerComponent $component */
            $component = $cmsApplication->bootComponent('com_healthchecker');
            $runner = $component->getHealthCheckRunner();

            $runner->clearCache();

            $cmsApplication->setHeader('Content-Type', 'application/json');
            echo new JsonResponse([
                'success' => true,
                'message' => Text::_('COM_HEALTHCHECKER_CACHE_CLEARED'),
            ]);
        } catch (\Exception $exception) {
            $cmsApplication->setHeader('Content-Type', 'application/json');
            echo new JsonResponse(null, $exception->getMessage(), true);
        }

        $cmsApplication->close();
    }

    /**
     * Run all health checks at once (legacy method)
     *
     * Executes all registered health checks across all categories and returns complete results.
     * This method is primarily kept for JSON export functionality.
     *
     * Note: The UI now uses category() for parallel execution, but this method is still
     * used by the JSON export view to get all results in a single request.
     *
     * Response format:
     * {
     *   "results": [...],
     *   "categories": [...],
     *   "providers": [...],
     *   "stats": { "critical": 2, "warning": 5, "good": 119, "total": 126 },
     *   "lastRun": "2026-01-14T10:30:00+00:00"
     * }
     *
     * @since   1.0.0
     */
    public function run(): void
    {
        $cmsApplication = Factory::getApplication();


        if (! Session::checkToken('get')) {
            $cmsApplication->setHeader('Content-Type', 'application/json');
            echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
            $cmsApplication->close();
            return;
        }

        $user = $cmsApplication->getIdentity();

        if ($user === null || ! $user->authorise('core.manage', 'com_healthchecker')) {
            $cmsApplication->setHeader('Content-Type', 'application/json');
            echo new JsonResponse(null, Text::_('JERROR_ALERTNOAUTHOR'), true);
            $cmsApplication->close();
            return;
        }

        try {
            /** @var HealthCheckerComponent $component */
            $component = $cmsApplication->bootComponent('com_healthchecker');
            $runner = $component->getHealthCheckRunner();

            $runner->run();

            $data = $runner->toArray();

            $cmsApplication->setHeader('Content-Type', 'application/json');
            echo new JsonResponse($data);
        } catch (\Exception $exception) {
            $cmsApplication->setHeader('Content-Type', 'application/json');
            echo new JsonResponse(null, $exception->getMessage(), true);
        }

        $cmsApplication->close();
    }
}
