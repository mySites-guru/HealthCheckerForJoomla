<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace MySitesGuru\HealthChecker\Component\Administrator\Check;

\defined('_JEXEC') || die;

/**
 * Interface for health check implementations.
 *
 * All health checks in the Health Checker system must implement this interface.
 * The interface defines the contract for check identification, categorization,
 * and execution.
 *
 * @since 1.0.0
 */
interface HealthCheckInterface
{
    /**
     * Get the unique slug identifier for this health check.
     *
     * The slug is used to uniquely identify the check and must be in the format:
     * {provider}.{check_name} (e.g., "core.php_version", "akeeba_backup.last_backup").
     * Use lowercase letters, numbers, and underscores only.
     *
     * @return string The unique slug identifier
     *
     * @since 1.0.0
     */
    public function getSlug(): string;

    /**
     * Get the human-readable title of this health check.
     *
     * This title is displayed in the UI and should be localized using Joomla's
     * language system. By default, the title is derived from a language key based
     * on the slug: COM_HEALTHCHECKER_CHECK_{SLUG_UPPERCASE}_TITLE
     *
     * @return string The translated title of the check
     *
     * @since 1.0.0
     */
    public function getTitle(): string;

    /**
     * Get the category slug this check belongs to.
     *
     * Categories organize checks in the UI. Standard categories include:
     * system, database, security, users, extensions, performance, seo, content.
     * Third-party plugins can define custom categories.
     *
     * @return string The category slug (e.g., "system", "security")
     *
     * @since 1.0.0
     */
    public function getCategory(): string;

    /**
     * Get the provider slug that owns this check.
     *
     * The provider identifies which plugin or component created this check.
     * Core checks return "core", while third-party plugins should return their
     * own unique provider slug.
     *
     * @return string The provider slug (e.g., "core", "akeeba_backup")
     *
     * @since 1.0.0
     */
    public function getProvider(): string;

    /**
     * Execute the health check and return the result.
     *
     * This method performs the actual health check logic and returns a
     * HealthCheckResult object containing the status (good/warning/critical)
     * and descriptive information about the check outcome.
     *
     * @return HealthCheckResult The result of the health check
     *
     * @since 1.0.0
     */
    public function run(): HealthCheckResult;
}
