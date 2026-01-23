<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

// Define _JEXEC to allow Joomla files to load
if (! defined('_JEXEC')) {
    define('_JEXEC', 1);
}

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load Joomla stubs - these provide minimal implementations of Joomla classes
require_once __DIR__ . '/stubs/psr-container.php';
require_once __DIR__ . '/stubs/joomla-di.php';
require_once __DIR__ . '/stubs/joomla-database.php';
require_once __DIR__ . '/stubs/joomla-event.php';
require_once __DIR__ . '/stubs/joomla-cms.php';

// Load test utilities and mocks
require_once __DIR__ . '/Utilities/JoomlaMocks.php';
require_once __DIR__ . '/Utilities/MockFactory.php';
