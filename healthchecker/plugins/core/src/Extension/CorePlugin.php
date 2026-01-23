<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Extension;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectCategoriesEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectChecksEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\HealthCheckerEvents;
use MySitesGuru\HealthChecker\Plugin\Core\Categories\CoreCategories;

\defined('_JEXEC') || die;

/**
 * Core Health Checker Plugin
 *
 * This plugin provides the built-in health checks for the Health Checker component.
 * It registers over 130 health checks across 8+ categories through an event-driven
 * auto-discovery system.
 *
 * The plugin operates by:
 * 1. Registering 8 core categories (System, Database, Security, Users, Extensions,
 *    Performance, SEO, Content) via the onCollectCategories event
 * 2. Auto-discovering all health check classes in the Checks/ directory by scanning
 *    subdirectories organized by category
 * 3. Instantiating check classes and injecting database dependency where needed
 * 4. Registering all discovered checks via the onCollectChecks event
 *
 * The discovery process scans:
 * - plugins/healthchecker/core/src/Checks/{Category}/*.php files
 * - Converts file paths to fully qualified class names
 * - Instantiates checks and injects dependencies (database via DatabaseAwareTrait)
 *
 * This plugin provides the "core" provider for all built-in health checks. Third-party
 * plugins can register their own checks by implementing the same event listeners with
 * their own provider slug.
 *
 * @subpackage  HealthChecker.Core
 * @since       1.0.0
 */
final class CorePlugin extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;

    /**
     * Plugin parameters (used by isCheckEnabled method).
     *
     * @var mixed
     * @since 1.0.0
     */
    public $params;

    /**
     * Returns an array of events this plugin subscribes to and their handler methods.
     *
     * This plugin listens for two core Health Checker events:
     * - onHealthCheckerCollectCategories: Registers the 8 core health check categories
     * - onHealthCheckerCollectChecks: Auto-discovers and registers all health check classes
     *
     * @return array<string, string> Event name => handler method mapping
     *
     * @since 1.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            HealthCheckerEvents::COLLECT_CATEGORIES->value => HealthCheckerEvents::COLLECT_CATEGORIES->getHandlerMethod(),
            HealthCheckerEvents::COLLECT_CHECKS->value => HealthCheckerEvents::COLLECT_CHECKS->getHandlerMethod(),
        ];
    }

    /**
     * Event handler for collecting health check categories.
     *
     * Registers all 8 core categories (System, Database, Security, Users, Extensions,
     * Performance, SEO, Content) with the Health Checker component. Categories are
     * defined in CoreCategories and include slug, label, icon, and sort order.
     *
     * Each category includes:
     * - Unique slug (e.g., 'system', 'database')
     * - Translatable label language key
     * - FontAwesome icon class
     * - Sort order for UI display (10, 20, 30, etc.)
     *
     * @param CollectCategoriesEvent $collectCategoriesEvent The event object for collecting categories
     *
     * @since 1.0.0
     */
    public function onCollectCategories(CollectCategoriesEvent $collectCategoriesEvent): void
    {
        $categories = CoreCategories::getCategories();

        foreach ($categories as $category) {
            $collectCategoriesEvent->addResult($category);
        }
    }

    /**
     * Event handler for collecting health checks.
     *
     * Auto-discovers all health check classes in the Checks/ directory structure by:
     * 1. Scanning all category subdirectories (e.g., Checks/System/, Checks/Database/)
     * 2. Finding all PHP files in each category directory
     * 3. Converting file paths to fully qualified class names
     * 4. Instantiating check classes and injecting database dependency where needed
     * 5. Registering all discovered checks with the event
     *
     * The discovery process uses directory structure to organize checks by category,
     * but each check's getCategory() method determines its actual category assignment.
     *
     * Database dependency injection is handled automatically for checks that implement
     * the DatabaseAwareInterface (via setDatabase() method).
     *
     * @param CollectChecksEvent $collectChecksEvent The event object for collecting health checks
     *
     * @since 1.0.0
     */
    public function onCollectChecks(CollectChecksEvent $collectChecksEvent): void
    {
        $checks = $this->discoverChecks();

        // Filter out disabled checks based on plugin configuration
        foreach ($checks as $check) {
            if ($this->isCheckEnabled($check->getSlug())) {
                $collectChecksEvent->addResult($check);
            }
        }
    }

    /**
     * Auto-discovers all health check classes in the Checks directory.
     *
     * Scans the plugin's Checks/ directory structure to find all health check classes:
     * 1. Finds all category subdirectories (e.g., System/, Database/, Security/)
     * 2. Scans each category directory for PHP files
     * 3. Converts file paths to fully qualified class names using namespace convention
     * 4. Instantiates each check class
     * 5. Injects database dependency for checks that need it (via setDatabase method)
     *
     * Directory structure example:
     * - Checks/System/PhpVersionCheck.php → Joomla\Plugin\HealthChecker\Core\Checks\System\PhpVersionCheck
     * - Checks/Database/ConnectionCheck.php → Joomla\Plugin\HealthChecker\Core\Checks\Database\ConnectionCheck
     *
     * Database injection is performed if the check class has a setDatabase() method,
     * which is typical for checks that extend AbstractHealthCheck and use DatabaseAwareTrait.
     *
     * @return array<int, object> Array of instantiated health check objects
     *
     * @since 1.0.0
     */
    private function discoverChecks(): array
    {
        $checks = [];
        $checksDir = __DIR__ . '/../Checks';

        if (! is_dir($checksDir)) {
            return $checks;
        }

        $categoryDirs = glob($checksDir . '/*', GLOB_ONLYDIR);

        if ($categoryDirs === false) {
            return $checks;
        }

        foreach ($categoryDirs as $categoryDir) {
            $checkFiles = glob($categoryDir . '/*.php');

            if ($checkFiles === false) {
                continue;
            }

            foreach ($checkFiles as $checkFile) {
                $className = $this->getClassNameFromFile($checkFile);

                if ($className && class_exists($className)) {
                    $check = new $className();

                    if (method_exists($check, 'setDatabase')) {
                        $check->setDatabase($this->getDatabase());
                    }

                    $checks[] = $check;
                }
            }
        }

        return $checks;
    }

    /**
     * Converts a file path to a fully qualified class name.
     *
     * Transforms a file system path to a PSR-4 compliant class name by:
     * 1. Removing the base directory prefix (__DIR__ . '/../')
     * 2. Removing the .php extension
     * 3. Converting directory separators (/) to namespace separators (\)
     * 4. Prepending the plugin's base namespace
     *
     * Example transformation:
     * - Input: /path/to/Checks/System/PhpVersionCheck.php
     * - Output: Joomla\Plugin\HealthChecker\Core\Checks\System\PhpVersionCheck
     *
     * The resulting class name follows Joomla's namespace conventions and can be
     * used with class_exists() and instantiation via new.
     *
     * @param string $file The absolute file path to a health check PHP file
     *
     * @return string The fully qualified class name
     *
     * @since 1.0.0
     */
    private function getClassNameFromFile(string $file): string
    {
        $relativePath = str_replace(__DIR__ . '/../', '', $file);
        $relativePath = str_replace('.php', '', $relativePath);
        $relativePath = str_replace('/', '\\', $relativePath);

        return 'MySitesGuru\\HealthChecker\\Plugin\\Core\\' . $relativePath;
    }

    /**
     * Check if a specific health check is enabled in the plugin configuration.
     *
     * Reads the plugin parameters to determine if a check should be executed.
     * Checks are enabled by default if not explicitly disabled.
     *
     * @param string $slug     The check slug (e.g., 'system.php_version')
     *
     * @return bool True if the check is enabled, false otherwise
     * @since 1.0.0
     */
    private function isCheckEnabled(string $slug): bool
    {
        // Convert slug to param name (e.g., 'system.php_version' -> 'check_system_php_version')
        $paramName = 'check_' . str_replace('.', '_', $slug);

        // Get the toggle value (1 = enabled, 0 = disabled)
        // Default to 1 (enabled) if parameter not set
        return (bool) $this->params->get($paramName, 1);
    }
}
