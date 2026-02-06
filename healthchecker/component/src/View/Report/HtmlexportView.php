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
use Joomla\CMS\Plugin\PluginHelper;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;

\defined('_JEXEC') || die;

/**
 * HTML Export View for Health Checker Report
 *
 * Generates a standalone, self-contained HTML export of the health check report.
 * This view creates a complete HTML document with embedded CSS that can be saved
 * or emailed without external dependencies.
 *
 * The HTML export includes:
 * - Site name and Joomla version in header
 * - Summary statistics cards (critical, warning, good, total)
 * - All health check results organized by category
 * - Provider attribution for third-party checks
 * - Optional mySites.guru promotional banner
 * - Print-optimized CSS
 *
 * @since 1.0.0
 */
class HtmlexportView extends BaseHtmlView
{
    /**
     * Display the HTML export
     *
     * Executes all health checks, gathers metadata, and renders a complete standalone
     * HTML document with embedded styles. The document is sent as a downloadable file
     * with appropriate headers.
     *
     * Filename format: health-report-YYYY-MM-DD.html
     *
     * This method terminates the application after sending the response.
     *
     * @param   string|null  $tpl  The name of the template file to parse (not used for export)
     *
     * @since   1.0.0
     */
    public function display($tpl = null): void
    {
        $cmsApplication = Factory::getApplication();

        /** @var \MySitesGuru\HealthChecker\Component\Administrator\Model\ReportModel $model */
        $model = $this->getModel();
        $model->runChecks();

        $results = $model->getResultsByCategory();
        $categories = $model->getRunner()
            ->getCategoryRegistry()
            ->all();
        $providers = $model->getRunner()
            ->getProviderRegistry()
            ->all();

        $siteName = $cmsApplication->get('sitename', 'Joomla Site');
        $reportDate = date('F j, Y \a\t g:i A');
        $joomlaVersion = JVERSION;

        $criticalCount = $model->getCriticalCount();
        $warningCount = $model->getWarningCount();
        $goodCount = $model->getGoodCount();
        $totalCount = $model->getTotalCount();

        // Check if mySites.guru plugin is enabled (banner only shows if plugin enabled)
        $showMySitesGuruBanner = PluginHelper::isEnabled('healthchecker', 'mysitesguru');

        // Get the logo URL (use absolute URL from the site)
        $logoUrl = \Joomla\CMS\Uri\Uri::root() . 'media/plg_healthchecker_mysitesguru/logo.png';

        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="health-report-' . date('Y-m-d') . '.html"');

        $this->renderHtmlReport(
            $results,
            $categories,
            $providers,
            $siteName,
            $reportDate,
            $joomlaVersion,
            $criticalCount,
            $warningCount,
            $goodCount,
            $totalCount,
            $showMySitesGuruBanner,
            $logoUrl,
        );

        $cmsApplication->close();
    }

    /**
     * Render the HTML report document
     *
     * Outputs a complete, self-contained HTML document with embedded CSS.
     * The document includes all check results organized by category with status badges,
     * summary statistics, and optional promotional content.
     *
     * @param   array   $results                  Health check results grouped by category
     * @param   array   $categories               Category metadata registry
     * @param   array   $providers                Provider metadata registry
     * @param   string  $siteName                 Name of the Joomla site
     * @param   string  $reportDate               Formatted date/time of report generation
     * @param   string  $joomlaVersion            Joomla version string
     * @param   int     $criticalCount            Count of critical status checks
     * @param   int     $warningCount             Count of warning status checks
     * @param   int     $goodCount                Count of good status checks
     * @param   int     $totalCount               Total count of all checks
     * @param   bool    $showMySitesGuruBanner    Whether to show promotional banner
     * @param   string  $logoUrl                  Absolute URL to the mySites.guru logo
     *
     * @since   1.0.0
     */
    private function renderHtmlReport(
        $results,
        array $categories,
        array $providers,
        $siteName,
        string $reportDate,
        string $joomlaVersion,
        $criticalCount,
        $warningCount,
        $goodCount,
        $totalCount,
        bool $showMySitesGuruBanner,
        string $logoUrl,
    ): void {
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Check Report - <?php echo htmlspecialchars($siteName); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header {
            background: #2c3e50;
            color: white;
            padding: 30px;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header .subtitle {
            opacity: 0.9;
            font-size: 14px;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 30px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .summary-card {
            text-align: center;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .summary-card .count {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .summary-card .label {
            font-size: 14px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .summary-card.critical .count { color: #dc3545; }
        .summary-card.warning .count { color: #ffc107; }
        .summary-card.good .count { color: #28a745; }
        .summary-card.total .count { color: #007bff; }

        .content {
            padding: 30px;
        }

        .category {
            margin-bottom: 40px;
        }

        .category-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            background: #f8f9fa;
            border-left: 4px solid #3498db;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .category-header h2 {
            font-size: 20px;
            color: #495057;
        }

        .mysites-banner {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 30px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }

        .mysites-banner-icon {
            flex-shrink: 0;
        }

        .mysites-banner-content {
            flex: 1;
            color: #333;
            font-size: 14px;
            line-height: 1.5;
        }

        .mysites-banner-content a {
            color: #333;
            text-decoration: underline;
        }

        .mysites-banner-content a:hover {
            color: #000;
        }

        .check {
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background: white;
        }

        .check-header {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 10px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            flex-shrink: 0;
            min-width: 94px;
            text-align: center;
        }

        .status-badge.critical {
            background: #dc3545;
            color: white;
        }

        .status-badge.warning {
            background: #ffc107;
            color: #000;
        }

        .status-badge.good {
            background: #28a745;
            color: white;
        }

        .check-title {
            font-size: 16px;
            font-weight: 600;
            color: #212529;
            flex: 1;
        }

        .check-provider {
            font-size: 12px;
            color: #6c757d;
            background: #e9ecef;
            padding: 4px 10px;
            border-radius: 12px;
            flex-shrink: 0;
        }

        .check-description {
            color: #495057;
            line-height: 1.6;
            margin-left: 94px;
        }

        .footer {
            padding: 20px 30px;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }

        .footer a {
            color: #3498db;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .container {
                box-shadow: none;
            }

            .check {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo Text::_('COM_HEALTHCHECKER_REPORT'); ?> - <?php echo htmlspecialchars($siteName); ?></h1>
            <div class="subtitle">
                Generated on <?php echo $reportDate; ?> | Joomla <?php echo htmlspecialchars($joomlaVersion); ?>
            </div>
        </div>

        <div class="summary">
            <div class="summary-card critical">
                <div class="count"><?php echo $criticalCount; ?></div>
                <div class="label"><?php echo Text::_('COM_HEALTHCHECKER_CRITICAL'); ?></div>
            </div>
            <div class="summary-card warning">
                <div class="count"><?php echo $warningCount; ?></div>
                <div class="label"><?php echo Text::_('COM_HEALTHCHECKER_WARNING'); ?></div>
            </div>
            <div class="summary-card good">
                <div class="count"><?php echo $goodCount; ?></div>
                <div class="label"><?php echo Text::_('COM_HEALTHCHECKER_GOOD'); ?></div>
            </div>
            <div class="summary-card total">
                <div class="count"><?php echo $totalCount; ?></div>
                <div class="label">Total Checks</div>
            </div>
        </div>

        <?php if ($showMySitesGuruBanner): ?>
        <div class="mysites-banner">
            <div class="mysites-banner-icon">
                <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="mySites.guru" style="width: 48px; height: 48px; border-radius: 4px;">
            </div>
            <div class="mysites-banner-content">
                This free Health Checker for Joomla is provided free of charge (GPL) by mySites.guru - the original Joomla Health Checker &amp; Joomla Monitoring Dashboard since 2012 - Monitor unlimited sites health from one central dashboard - <a href="https://mysites.guru" target="_blank"><strong>For more details visit mySites.guru</strong></a>
            </div>
        </div>
        <?php endif;
        ?>

        <div class="content">
            <?php foreach ($results as $categorySlug => $categoryResults) {
                ?>
                <?php
                if (empty($categoryResults)) {
                    continue;
                }

                ?>

                <?php
                $category = $categories[$categorySlug] ?? null;
                $categoryTitle = $category ? Text::_($category->label) : $categorySlug;
                $categoryIcon = $category ? $category->icon : '';
                ?>

                <div class="category">
                    <div class="category-header">
                        <h2><?php
                echo htmlspecialchars((string) $categoryTitle);
                ?></h2>
                    </div>

                    <?php
                foreach ($categoryResults as $categoryResult): ?>
                        <div class="check">
                        <div class="check-header">
                            <span class="status-badge <?php echo $categoryResult->healthStatus->value; ?>">
                                <?php echo $categoryResult->healthStatus === HealthStatus::Critical ? '🔴 ' : ($categoryResult->healthStatus === HealthStatus::Warning ? '🟡 ' : '🟢 '); ?>
                                    <?php echo strtoupper((string) $categoryResult->healthStatus->value); ?>
                                </span>
                            <span class="check-title"><?php echo htmlspecialchars((string) $categoryResult->title); ?></span>
                            <?php if ($categoryResult->provider !== 'core'): ?>
                                    <?php
                                        $provider = $providers[$categoryResult->provider] ?? null;
                                $providerName = $provider ? $provider->name : $categoryResult->provider;
                                ?>
                                    <span class="check-provider"><?php echo htmlspecialchars((string) $providerName); ?></span>
                            <?php endif;
                    ?>
                            </div>
                        <div class="check-description">
                            <?php echo nl2br(htmlspecialchars((string) $categoryResult->description)); ?>
                            </div>
                    </div>
<?php endforeach;

                ?>
                </div>
            <?php
            }
        ?>
        </div>

        <div class="footer">
            Generated by <a href="https://github.com/mySites-guru/health-checker-for-joomla" target="_blank">Health Checker for Joomla</a>
            | A free GPL extension from <a href="https://mysites.guru" target="_blank">mySites.guru</a>
        </div>
    </div>
</body>
</html>
<?php
    }
}
