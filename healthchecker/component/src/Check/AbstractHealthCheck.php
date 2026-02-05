<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace MySitesGuru\HealthChecker\Component\Administrator\Check;

use Joomla\CMS\Http\Http;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;

\defined('_JEXEC') || die;

/**
 * Abstract base class for health check implementations.
 *
 * This class provides common functionality for all health checks including:
 * - Automatic error handling via try/catch wrapper
 * - Helper methods for creating results (critical/warning/good)
 * - Database injection support
 * - Automatic title translation based on slug
 * - Default provider implementation
 *
 * Concrete health check classes should extend this class and implement:
 * - getSlug(): Return unique check identifier
 * - getCategory(): Return category slug
 * - performCheck(): Implement the actual check logic
 *
 * @since 1.0.0
 */
abstract class AbstractHealthCheck implements HealthCheckInterface
{
    /**
     * Joomla database instance for checks that need database access.
     *
     * This is injected via setDatabase() by the health check runner before
     * execution. It can be null if the check doesn't require database access.
     *
     * @since 1.0.0
     */
    protected ?DatabaseInterface $db = null;

    /**
     * HTTP client instance for checks that need to make HTTP requests.
     *
     * This is injected via setHttpClient() for testing purposes or can be
     * obtained via getHttpClient() which falls back to HttpFactory.
     *
     * @since 1.0.0
     */
    protected ?Http $httpClient = null;

    /**
     * Inject the Joomla database instance for use in database-dependent checks.
     *
     * This method is called by the HealthCheckRunner service before executing
     * the check to provide database access if needed.
     *
     * @param DatabaseInterface $database The Joomla database instance
     *
     * @since 1.0.0
     */
    public function setDatabase(DatabaseInterface $database): void
    {
        $this->db = $database;
    }

    /**
     * Get the injected database instance.
     *
     * Returns null if no database has been injected via setDatabase().
     *
     * @return DatabaseInterface|null The database instance or null
     *
     * @since 1.0.0
     */
    public function getDatabase(): ?DatabaseInterface
    {
        return $this->db;
    }

    /**
     * Get the database instance with null safety check.
     *
     * Throws an exception if no database has been injected. Use this method
     * in checks that require database access to ensure type safety.
     *
     * @return DatabaseInterface The database instance (never null)
     *
     * @since 1.0.0
     */
    protected function requireDatabase(): DatabaseInterface
    {
        if (! $this->db instanceof \Joomla\Database\DatabaseInterface) {
            throw new \RuntimeException(
                sprintf('Health check %s requires database access but no database was injected.', $this->getSlug()),
            );
        }

        return $this->db;
    }

    /**
     * Inject an HTTP client instance for testing or custom HTTP handling.
     *
     * This method allows tests to inject a mock HTTP client to avoid making
     * real network requests during unit testing.
     *
     * @param Http $http The HTTP client instance
     *
     * @since 1.0.0
     */
    public function setHttpClient(Http $http): void
    {
        $this->httpClient = $http;
    }

    /**
     * Get the HTTP client instance for making HTTP requests.
     *
     * Returns the injected HTTP client if one was set via setHttpClient(),
     * otherwise falls back to creating a new client via HttpFactory.
     *
     * @return Http The HTTP client instance
     *
     * @since 1.0.0
     */
    protected function getHttpClient(): Http
    {
        if ($this->httpClient instanceof Http) {
            return $this->httpClient;
        }

        return HttpFactory::getHttp();
    }

    /**
     * Get the unique slug identifier for this health check.
     *
     * Must be implemented by concrete check classes. The slug should be in the
     * format {provider}.{check_name} and use only lowercase letters, numbers,
     * and underscores.
     *
     * @return string The unique slug identifier
     *
     * @since 1.0.0
     */
    abstract public function getSlug(): string;

    /**
     * Get the category slug this check belongs to.
     *
     * Must be implemented by concrete check classes. Should return one of the
     * standard category slugs (system, database, security, users, extensions,
     * performance, seo, content) or a custom category slug registered by a plugin.
     *
     * @return string The category slug
     *
     * @since 1.0.0
     */
    abstract public function getCategory(): string;

    /**
     * Get the provider slug that owns this check.
     *
     * Default implementation returns "core" for built-in checks. Third-party
     * plugins should override this method to return their own provider slug.
     *
     * @return string The provider slug (defaults to "core")
     *
     * @since 1.0.0
     */
    public function getProvider(): string
    {
        return 'core';
    }

    /**
     * Get the documentation URL for this check.
     *
     * When implemented, this URL is displayed as a (?) icon next to the check
     * that opens the documentation in a new tab when clicked.
     *
     * Default implementation returns null (no documentation link).
     * Override this method to provide a link to documentation for your check.
     *
     * @return string|null The documentation URL or null if none
     *
     * @since 3.0.36
     */
    public function getDocsUrl(): ?string
    {
        return null;
    }

    /**
     * Get the action URL for this check.
     *
     * When implemented, the entire result row becomes clickable and navigates
     * to this URL (in the same window) when clicked. Useful for linking to
     * the relevant configuration page for the check.
     *
     * The optional $status parameter allows checks to conditionally return
     * an action URL based on the result status. For example, a check might
     * only return an action URL when the status is Critical or Warning,
     * returning null for Good status since no action is needed.
     *
     * Default implementation returns null (row not clickable).
     * Override this method to make the result row link to an action page.
     *
     * @param HealthStatus|null $status The result status (Critical/Warning/Good), or null for backwards compatibility
     *
     * @return string|null The action URL or null if not clickable
     *
     * @since 3.0.36
     */
    public function getActionUrl(?HealthStatus $status = null): ?string
    {
        return null;
    }

    /**
     * Get the human-readable translated title for this check.
     *
     * Automatically derives a language key from the slug and attempts to translate it.
     * The language key format is: COM_HEALTHCHECKER_CHECK_{SLUG_UPPERCASE}_TITLE
     *
     * For example, slug "core.php_version" becomes key "COM_HEALTHCHECKER_CHECK_CORE_PHP_VERSION_TITLE"
     *
     * If no translation exists, falls back to returning the slug itself.
     *
     * @return string The translated title or slug as fallback
     *
     * @since 1.0.0
     */
    public function getTitle(): string
    {
        $key = 'COM_HEALTHCHECKER_CHECK_' . strtoupper(str_replace('.', '_', $this->getSlug())) . '_TITLE';
        $translated = Text::_($key);

        return $translated !== $key ? $translated : $this->getSlug();
    }

    /**
     * Execute the health check with automatic error handling.
     *
     * This method wraps performCheck() in a try/catch block to handle any
     * exceptions gracefully. If an exception is thrown, it returns a WARNING
     * result with the error message.
     *
     * This method is final and cannot be overridden. Implement performCheck()
     * instead to define check logic.
     *
     * @return HealthCheckResult The result of the health check
     *
     * @since 1.0.0
     */
    final public function run(): HealthCheckResult
    {
        try {
            return $this->performCheck();
        } catch (\Throwable $throwable) {
            return $this->warning(Text::sprintf('COM_HEALTHCHECKER_CHECK_ERROR', $throwable->getMessage()));
        }
    }

    /**
     * Perform the actual health check logic.
     *
     * This method must be implemented by concrete check classes to perform the
     * actual check and return a HealthCheckResult. Use the helper methods
     * critical(), warning(), or good() to create results.
     *
     * This method is called by run() which provides automatic error handling.
     *
     * @return HealthCheckResult The result of the check
     *
     * @since 1.0.0
     */
    abstract protected function performCheck(): HealthCheckResult;

    /**
     * Create a CRITICAL status health check result.
     *
     * Use this helper when the check identifies a critical issue that requires
     * immediate attention. Critical issues indicate the site is broken, severely
     * compromised, or data is at risk.
     *
     * @param string $description Human-readable description of the critical issue
     *
     * @return HealthCheckResult A critical status result
     *
     * @since 1.0.0
     */
    protected function critical(string $description): HealthCheckResult
    {
        return new HealthCheckResult(
            healthStatus: HealthStatus::Critical,
            title: $this->getTitle(),
            description: $description,
            slug: $this->getSlug(),
            category: $this->getCategory(),
            provider: $this->getProvider(),
            docsUrl: $this->getDocsUrl(),
            actionUrl: $this->getActionUrl(HealthStatus::Critical),
        );
    }

    /**
     * Create a WARNING status health check result.
     *
     * Use this helper when the check identifies an issue that should be addressed
     * but doesn't prevent the site from functioning. Warnings indicate potential
     * problems or areas for improvement.
     *
     * @param string $description Human-readable description of the warning
     *
     * @return HealthCheckResult A warning status result
     *
     * @since 1.0.0
     */
    protected function warning(string $description): HealthCheckResult
    {
        return new HealthCheckResult(
            healthStatus: HealthStatus::Warning,
            title: $this->getTitle(),
            description: $description,
            slug: $this->getSlug(),
            category: $this->getCategory(),
            provider: $this->getProvider(),
            docsUrl: $this->getDocsUrl(),
            actionUrl: $this->getActionUrl(HealthStatus::Warning),
        );
    }

    /**
     * Create a GOOD status health check result.
     *
     * Use this helper when the check completes successfully and finds no issues.
     * Good status indicates everything is optimal for this particular check.
     *
     * @param string $description Human-readable description of the good status
     *
     * @return HealthCheckResult A good status result
     *
     * @since 1.0.0
     */
    protected function good(string $description): HealthCheckResult
    {
        return new HealthCheckResult(
            healthStatus: HealthStatus::Good,
            title: $this->getTitle(),
            description: $description,
            slug: $this->getSlug(),
            category: $this->getCategory(),
            provider: $this->getProvider(),
            docsUrl: $this->getDocsUrl(),
            actionUrl: $this->getActionUrl(HealthStatus::Good),
        );
    }
}
