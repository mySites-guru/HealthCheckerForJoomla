<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

// Define Joomla constants
if (! defined('_JEXEC')) {
    define('_JEXEC', 1);
}

if (! defined('JPATH_ROOT')) {
    define('JPATH_ROOT', '/path/to/joomla');
}

if (! defined('JPATH_SITE')) {
    define('JPATH_SITE', '/path/to/joomla');
}

if (! defined('JPATH_ADMINISTRATOR')) {
    define('JPATH_ADMINISTRATOR', '/path/to/joomla/administrator');
}

if (! defined('JVERSION')) {
    define('JVERSION', '5.0.0');
}

// Load individual stub files
require_once __DIR__ . '/stubs/psr-container.php';
require_once __DIR__ . '/stubs/joomla-di.php';
require_once __DIR__ . '/stubs/joomla-database.php';
require_once __DIR__ . '/stubs/joomla-event.php';
require_once __DIR__ . '/stubs/joomla-cms.php';

// Verify classes loaded (for debugging)
if (! class_exists(\Joomla\CMS\Factory::class)) {
    throw new \RuntimeException('Joomla\CMS\Factory class not loaded from stubs!');
}

if (! interface_exists(\Joomla\Event\SubscriberInterface::class)) {
    throw new \RuntimeException('Joomla\Event\SubscriberInterface not loaded from stubs!');
}

if (! trait_exists(\Joomla\Database\DatabaseAwareTrait::class)) {
    throw new \RuntimeException('Joomla\Database\DatabaseAwareTrait not loaded from stubs!');
}
