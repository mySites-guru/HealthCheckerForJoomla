<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_healthchecker
 *
 * @copyright   (C) 2026 mySites.guru / Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') || die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

/** @var \MySitesGuru\HealthChecker\Component\View\Report\HtmlView $this */

/** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle('com_healthchecker.report', 'com_healthchecker/admin-report.css');
$wa->registerAndUseScript('com_healthchecker.report', 'com_healthchecker/admin-report.js', [], ['defer' => true]);

HTMLHelper::_('bootstrap.tooltip', '.hasTooltip');

// Load language strings for JavaScript
Text::script('COM_HEALTHCHECKER_CATEGORY_SYSTEM');
Text::script('COM_HEALTHCHECKER_CATEGORY_DATABASE');
Text::script('COM_HEALTHCHECKER_CATEGORY_SECURITY');
Text::script('COM_HEALTHCHECKER_CATEGORY_USERS');
Text::script('COM_HEALTHCHECKER_CATEGORY_EXTENSIONS');
Text::script('COM_HEALTHCHECKER_CATEGORY_PERFORMANCE');
Text::script('COM_HEALTHCHECKER_CATEGORY_SEO');
Text::script('COM_HEALTHCHECKER_CATEGORY_CONTENT');

$token = Session::getFormToken();
$metadataUrl = Route::_('index.php?option=com_healthchecker&task=ajax.metadata&format=json&' . $token . '=1', false);
$checkUrl = Route::_('index.php?option=com_healthchecker&task=ajax.check&format=json&' . $token . '=1', false);
$categoryUrl = Route::_('index.php?option=com_healthchecker&task=ajax.category&format=json&' . $token . '=1', false);

?>
<form action="<?php echo Route::_('index.php?option=com_healthchecker&view=report'); ?>" method="post" name="adminForm" id="adminForm"
      data-metadata-url="<?php echo htmlspecialchars($metadataUrl); ?>"
      data-check-url="<?php echo htmlspecialchars($checkUrl); ?>"
      data-category-url="<?php echo htmlspecialchars($categoryUrl); ?>"
      data-text-loading="<?php echo htmlspecialchars(Text::_('COM_HEALTHCHECKER_LOADING')); ?>"
      data-text-last-checked="<?php echo htmlspecialchars(Text::_('COM_HEALTHCHECKER_LAST_CHECKED')); ?>"
      data-text-running-checks="<?php echo htmlspecialchars(Text::_('COM_HEALTHCHECKER_RUNNING_CHECKS')); ?>"
      data-text-critical="<?php echo htmlspecialchars(Text::_('COM_HEALTHCHECKER_CRITICAL')); ?>"
      data-text-warning="<?php echo htmlspecialchars(Text::_('COM_HEALTHCHECKER_WARNING')); ?>"
      data-text-good="<?php echo htmlspecialchars(Text::_('COM_HEALTHCHECKER_GOOD')); ?>"
      data-text-checking="<?php echo htmlspecialchars(Text::_('COM_HEALTHCHECKER_CHECKING')); ?>">

    <?php
    // Allow plugins to inject content (banners, notices) before the report.
    // Security note: This HTML comes from installed Joomla plugins which are trusted code
    // (they require administrator installation privileges). No user input flows here.
    ?>
    <?php if (!empty($this->beforeReportHtml)): ?>
        <?php echo $this->beforeReportHtml; ?>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-bg-danger h-100 status-filter-card" data-status="critical" role="button" style="view-transition-name: healthchecker-critical; cursor: pointer;">
                <div class="card-body text-center">
                    <div class="display-4" id="critical-count">-</div>
                    <div class="fs-5">
                        <span class="fa fa-times-circle" aria-hidden="true"></span>
                        <?php echo Text::_('COM_HEALTHCHECKER_CRITICAL'); ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-bg-warning h-100 status-filter-card" data-status="warning" role="button" style="view-transition-name: healthchecker-warning; cursor: pointer;">
                <div class="card-body text-center">
                    <div class="display-4 text-dark" id="warning-count">-</div>
                    <div class="fs-5 text-dark">
                        <span class="fa fa-exclamation-triangle" aria-hidden="true"></span>
                        <?php echo Text::_('COM_HEALTHCHECKER_WARNING'); ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-bg-success h-100 status-filter-card" data-status="good" role="button" style="view-transition-name: healthchecker-good; cursor: pointer;">
                <div class="card-body text-center">
                    <div class="display-4" id="good-count">-</div>
                    <div class="fs-5">
                        <span class="fa fa-check-circle" aria-hidden="true"></span>
                        <?php echo Text::_('COM_HEALTHCHECKER_GOOD'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
            <div class="input-group">
                <label class="input-group-text" for="filter_search">
                    <span class="fa fa-search" aria-hidden="true"></span>
                </label>
                <input type="text" class="form-control" id="filter_search" placeholder="<?php echo Text::_('COM_HEALTHCHECKER_SEARCH_PLACEHOLDER'); ?>" autocomplete="off">
            </div>
        </div>
        <div class="col-md-4">
            <div class="input-group">
                <label class="input-group-text" for="filter_status">
                    <?php echo Text::_('COM_HEALTHCHECKER_FILTER_STATUS'); ?>
                </label>
                <select class="form-select" id="filter_status" name="filter_status">
                    <option value=""><?php echo Text::_('COM_HEALTHCHECKER_ALL_STATUSES'); ?></option>
                    <option value="hide_good"><?php echo Text::_('COM_HEALTHCHECKER_HIDE_GOOD'); ?></option>
                    <option value="critical"><?php echo Text::_('COM_HEALTHCHECKER_CRITICAL'); ?></option>
                    <option value="warning"><?php echo Text::_('COM_HEALTHCHECKER_WARNING'); ?></option>
                    <option value="good"><?php echo Text::_('COM_HEALTHCHECKER_GOOD'); ?></option>
                </select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="input-group">
                <label class="input-group-text" for="filter_category">
                    <?php echo Text::_('COM_HEALTHCHECKER_FILTER_CATEGORY'); ?>
                </label>
                <select class="form-select" id="filter_category" name="filter_category">
                    <option value=""><?php echo Text::_('COM_HEALTHCHECKER_ALL_CATEGORIES'); ?></option>
                </select>
            </div>
        </div>
    </div>

    <div id="health-check-loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden"><?php echo Text::_('COM_HEALTHCHECKER_LOADING'); ?></span>
        </div>
        <div class="mt-3 fs-5"><?php echo Text::_('COM_HEALTHCHECKER_RUNNING_CHECKS'); ?></div>
        <div class="text-muted" id="loading-progress"></div>
    </div>

    <div id="health-check-error" class="alert alert-danger d-none">
        <h4 class="alert-heading">
            <span class="fa fa-exclamation-circle" aria-hidden="true"></span>
            <?php echo Text::_('COM_HEALTHCHECKER_ERROR'); ?>
        </h4>
        <p id="health-check-error-message"></p>
        <button type="button" id="retry-health-check" class="btn btn-outline-danger">
            <span class="fa fa-redo" aria-hidden="true"></span>
            <?php echo Text::_('COM_HEALTHCHECKER_RETRY'); ?>
        </button>
    </div>

    <div id="health-check-results" class="d-none">
        <div id="healthCheckCategories"></div>

        <div class="text-muted mt-3">
            <small id="last-checked"></small>
        </div>

        <div id="third-party-providers" class="card mt-4 d-none">
            <div class="card-header">
                <h5 class="mb-0"><?php echo Text::_('COM_HEALTHCHECKER_THIRD_PARTY_PROVIDERS'); ?></h5>
            </div>
            <div class="card-body">
                <div class="row" id="providers-list"></div>
            </div>
        </div>
    </div>

    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
