<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  mod_healthchecker
 *
 * @copyright   (C) 2026 mySites.guru / Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') || die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

/**
 * @var array $healthStats Module config from helper
 * @var \Joomla\Registry\Registry $params Module parameters
 * @var \stdClass $module Module object
 */

/** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle('mod_healthchecker.card', 'mod_healthchecker/module-card.css');
$wa->registerAndUseScript('mod_healthchecker.stats', 'mod_healthchecker/module-stats.js', [], ['defer' => true]);

$reportUrl = Route::_('index.php?option=com_healthchecker&view=report');

$cacheParams = '';
if (isset($healthStats['enableCache']) && $healthStats['enableCache']) {
    $cacheParams = '&cache=1&cache_ttl=' . $healthStats['cacheDuration'];
}
$statsUrl = Route::_('index.php?option=com_healthchecker&task=ajax.stats&format=json&' . Session::getFormToken() . '=1' . $cacheParams, false);

$moduleId = 'mod-healthchecker-' . $module->id;

// Debug: Check cache setting
$showRefreshButton = !empty($healthStats['enableCache']) || $params->get('enable_cache', '1') === '1';
?>
<div class="mod-healthchecker p-3<?php echo $params->get('moduleclass_sfx', ''); ?>" id="<?php echo $moduleId; ?>" data-stats-url="<?php echo htmlspecialchars($statsUrl); ?>" data-last-checked-text="<?php echo htmlspecialchars(Text::_('MOD_HEALTHCHECKER_LAST_CHECKED')); ?>">
    <!-- Loading State -->
    <div id="<?php echo $moduleId; ?>-loading" class="text-center py-3">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden"><?php echo Text::_('MOD_HEALTHCHECKER_LOADING'); ?></span>
        </div>
        <div class="mt-2 small text-muted"><?php echo Text::_('MOD_HEALTHCHECKER_RUNNING_CHECKS'); ?></div>
    </div>

    <!-- Error State -->
    <div id="<?php echo $moduleId; ?>-error" class="alert alert-warning d-none">
        <span class="icon-warning" aria-hidden="true"></span>
        <?php echo Text::_('MOD_HEALTHCHECKER_ERROR_LOADING'); ?>
        <small class="d-block" id="<?php echo $moduleId; ?>-error-message"></small>
    </div>

    <!-- Results State -->
    <div id="<?php echo $moduleId; ?>-results" class="d-none">
        <div class="row g-3">
            <div class="col">
                <a href="<?php echo $reportUrl; ?>" class="text-decoration-none d-block rounded-3 h-100 bg-danger text-white healthchecker-card" style="view-transition-name: healthchecker-critical;">
                    <div class="text-center p-3">
                        <div class="fs-1 fw-bold" id="<?php echo $moduleId; ?>-critical">-</div>
                        <div class="small">
                            <span class="fa fa-times-circle" aria-hidden="true"></span>
                            <?php echo Text::_('MOD_HEALTHCHECKER_CRITICAL'); ?>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="<?php echo $reportUrl; ?>" class="text-decoration-none d-block rounded-3 h-100 bg-warning healthchecker-card" style="view-transition-name: healthchecker-warning;">
                    <div class="text-center p-3">
                        <div class="fs-1 fw-bold text-dark" id="<?php echo $moduleId; ?>-warning">-</div>
                        <div class="small text-dark">
                            <span class="fa fa-exclamation-triangle" aria-hidden="true"></span>
                            <?php echo Text::_('MOD_HEALTHCHECKER_WARNING'); ?>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="<?php echo $reportUrl; ?>" class="text-decoration-none d-block rounded-3 h-100 bg-success text-white healthchecker-card" style="view-transition-name: healthchecker-good;">
                    <div class="text-center p-3">
                        <div class="fs-1 fw-bold" id="<?php echo $moduleId; ?>-good">-</div>
                        <div class="small">
                            <span class="fa fa-check-circle" aria-hidden="true"></span>
                            <?php echo Text::_('MOD_HEALTHCHECKER_GOOD'); ?>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <div class="mt-3 text-center d-flex gap-2 justify-content-center flex-wrap">
        <a href="<?php echo $reportUrl; ?>" class="btn btn-outline-primary btn-sm">
            <span class="icon-clipboard" aria-hidden="true"></span>
            <?php echo Text::_('MOD_HEALTHCHECKER_VIEW_REPORT'); ?>
        </a>
        <?php if ($showRefreshButton): ?>
        <button type="button" id="<?php echo $moduleId; ?>-refresh" class="btn btn-outline-secondary btn-sm" title="<?php echo Text::_('MOD_HEALTHCHECKER_REFRESH_CACHE'); ?>">
            <span class="icon-refresh" aria-hidden="true"></span>
            <span class="visually-hidden"><?php echo Text::_('MOD_HEALTHCHECKER_REFRESH_CACHE'); ?></span>
        </button>
        <?php endif; ?>
    </div>

    <div id="<?php echo $moduleId; ?>-timestamp" class="mt-2 text-center text-muted small d-none"></div>
</div>
