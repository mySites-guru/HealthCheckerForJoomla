<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace MySitesGuru\HealthChecker\Component\Administrator\Service;

use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckInterface;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectCategoriesEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectChecksEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectProvidersEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\HealthCheckerEvents;
use MySitesGuru\HealthChecker\Component\Administrator\Provider\ProviderRegistry;

\defined('_JEXEC') || die;

/**
 * Core service for orchestrating health check execution and result management.
 *
 * This is the primary service responsible for:
 * - Dispatching events to collect providers, categories, and checks from plugins
 * - Executing health checks and managing their lifecycle
 * - Storing and retrieving results (in-memory, session-based, or cached)
 * - Providing aggregated data (counts, groupings, summaries)
 * - Supporting single-check and category-specific execution
 *
 * The runner follows a three-phase initialization process:
 * 1. Collect providers (plugins identify themselves)
 * 2. Collect categories (plugins register custom categories)
 * 3. Collect checks (plugins provide health check instances)
 *
 * Results are stored in-memory only and not persisted to the database.
 * Optional caching is available via Joomla's cache system.
 *
 * Dependencies:
 * - DispatcherInterface: For dispatching collection events to plugins
 * - CategoryRegistry: For storing and retrieving category definitions
 * - ProviderRegistry: For storing and retrieving provider metadata
 * - DatabaseInterface: Injected into AbstractHealthCheck instances for database queries
 * - CacheControllerFactoryInterface: For optional result caching
 *
 * @since 1.0.0
 */
final class HealthCheckRunner
{
    /**
     * In-memory storage of health check results from the last run.
     *
     * This array is populated by the run() method and cleared at the start
     * of each new run. Results are sorted by status (critical first) and
     * then by category sort order.
     *
     * @var HealthCheckResult[]
     */
    private array $results = [];

    /**
     * Timestamp of the last health check run.
     *
     * Set to the current time when run() is called. Used for displaying
     * "last run" information in the UI and for cache validation.
     */
    private ?\DateTimeImmutable $dateTimeImmutable = null;

    /**
     * Constructor with dependency injection.
     *
     * @param DispatcherInterface $dispatcher Event dispatcher for triggering collection events
     * @param CategoryRegistry $categoryRegistry Registry for storing and retrieving categories
     * @param ProviderRegistry $providerRegistry Registry for storing and retrieving provider metadata
     * @param DatabaseInterface $database Database connection injected into checks that need it
     * @param CacheControllerFactoryInterface $cacheControllerFactory Factory for creating cache controllers
     *
     * @since 1.0.0
     */
    public function __construct(
        private readonly DispatcherInterface $dispatcher,
        private readonly CategoryRegistry $categoryRegistry,
        private readonly ProviderRegistry $providerRegistry,
        private readonly DatabaseInterface $database,
        private readonly CacheControllerFactoryInterface $cacheControllerFactory,
    ) {}

    /**
     * Execute all health checks and store results.
     *
     * This is the primary method for running a complete health check suite. It:
     * 1. Clears any previous results
     * 2. Records the current timestamp
     * 3. Collects providers, categories, and checks via event dispatcher
     * 4. Injects database dependency into checks that extend AbstractHealthCheck
     * 5. Executes each check and stores its result
     * 6. Sorts results by status (critical first) and category order
     *
     * Results are stored in-memory in $this->results and can be retrieved
     * via getResults(), getResultsByCategory(), or getResultsByStatus().
     *
     * @since 1.0.0
     */
    public function run(): void
    {
        $this->results = [];
        $this->dateTimeImmutable = new \DateTimeImmutable();

        $this->collectProviders();
        $this->collectCategories();
        $checks = $this->collectChecks();

        foreach ($checks as $check) {
            if ($check instanceof AbstractHealthCheck) {
                $check->setDatabase($this->database);
            }

            $this->results[] = $check->run();
        }

        $this->sortResults();
    }

    /**
     * Dispatch event to collect provider metadata from plugins.
     *
     * This is phase 1 of the initialization process. Plugins respond to the
     * 'onHealthCheckerCollectProviders' event by adding ProviderMetadata instances
     * that identify themselves (name, description, URL, logo, etc.).
     *
     * Provider metadata is used in the UI to attribute checks to their source
     * (e.g., "Core", "Akeeba Backup", "My Custom Plugin").
     *
     * @since 1.0.0
     */
    private function collectProviders(): void
    {
        $collectProvidersEvent = new CollectProvidersEvent();
        $this->dispatcher->dispatch(HealthCheckerEvents::COLLECT_PROVIDERS->value, $collectProvidersEvent);

        foreach ($collectProvidersEvent->getProviders() as $providerMetadatum) {
            $this->providerRegistry->register($providerMetadatum);
        }
    }

    /**
     * Dispatch event to collect health check categories from plugins.
     *
     * This is phase 2 of the initialization process. Plugins respond to the
     * 'onHealthCheckerCollectCategories' event by adding HealthCategory instances
     * that define how checks should be grouped in the UI.
     *
     * Categories include metadata like display name, icon, sort order, and slug.
     * Default categories (system, database, security, etc.) are provided by the
     * core plugin, but third-party plugins can add custom categories.
     *
     * @since 1.0.0
     */
    private function collectCategories(): void
    {
        $collectCategoriesEvent = new CollectCategoriesEvent();
        $this->dispatcher->dispatch(HealthCheckerEvents::COLLECT_CATEGORIES->value, $collectCategoriesEvent);

        foreach ($collectCategoriesEvent->getCategories() as $healthCategory) {
            $this->categoryRegistry->register($healthCategory);
        }
    }

    /**
     * Dispatch event to collect health check instances from plugins.
     *
     * This is phase 3 of the initialization process. Plugins respond to the
     * 'onHealthCheckerCollectChecks' event by adding HealthCheckInterface
     * instances that will be executed.
     *
     * Each check must implement HealthCheckInterface and define:
     * - A unique slug (e.g., 'core.php_version')
     * - A category slug (e.g., 'system')
     * - A provider slug (e.g., 'core')
     * - A run() method that returns a HealthCheckResult
     *
     * This method is public to allow external code to discover available checks
     * without running them (e.g., for the metadata endpoint in the API controller).
     *
     * @return HealthCheckInterface[] Array of health check instances ready to execute
     *
     * @since 1.0.0
     */
    public function collectChecks(): array
    {
        $collectChecksEvent = new CollectChecksEvent();
        $this->dispatcher->dispatch(HealthCheckerEvents::COLLECT_CHECKS->value, $collectChecksEvent);

        return $collectChecksEvent->getChecks();
    }

    /**
     * Initialize metadata (providers, categories) without running checks.
     *
     * This method performs phases 1 and 2 of initialization (collect providers
     * and categories) but skips check execution. Used by methods that need
     * metadata but don't need to run checks, such as getMetadata() or when
     * preparing for category-specific check runs.
     *
     * @since 1.0.0
     */
    public function initialize(): void
    {
        $this->collectProviders();
        $this->collectCategories();
    }

    /**
     * Execute a single health check by its slug.
     *
     * This method provides targeted check execution for AJAX endpoints and
     * API calls. It performs full initialization (providers, categories, checks)
     * but only executes the check matching the given slug.
     *
     * The result is NOT stored in $this->results - it's returned directly
     * to the caller.
     *
     * @param string $slug The unique check slug (e.g., 'core.php_version')
     *
     * @return HealthCheckResult|null The check result, or null if no check with that slug exists
     *
     * @since 1.0.0
     */
    public function runSingleCheck(string $slug): ?HealthCheckResult
    {
        $this->collectProviders();
        $this->collectCategories();
        $checks = $this->collectChecks();

        foreach ($checks as $check) {
            if ($check->getSlug() === $slug) {
                if ($check instanceof AbstractHealthCheck) {
                    $check->setDatabase($this->database);
                }

                return $check->run();
            }
        }

        return null;
    }

    /**
     * Execute all health checks in a specific category.
     *
     * This method runs only the checks belonging to the specified category slug.
     * Unlike run(), it returns an array of serialized results (toArray() format)
     * rather than storing them in $this->results.
     *
     * Results are keyed by check slug for easy lookup. If a check throws an
     * exception during execution, it's caught and returned as a warning-status
     * result with the error message in the description.
     *
     * This is used by the AJAX API to allow partial execution of checks
     * (e.g., run only "Security" category checks).
     *
     * @param string $category Category slug (e.g., 'system', 'security', 'database')
     *
     * @return array<string, array<string, mixed>> Array of results keyed by check slug
     *
     * @since 1.0.0
     */
    public function runCategory(string $category): array
    {
        $this->initialize();
        $checks = $this->collectChecks();

        $results = [];
        foreach ($checks as $check) {
            if ($check->getCategory() === $category) {
                try {
                    $result = $check->run();
                    $results[$result->slug] = $result->toArray();
                } catch (\Exception $e) {
                    // If a check fails, return error result
                    $results[$check->getSlug()] = [
                        'slug' => $check->getSlug(),
                        'title' => $check->getTitle(),
                        'category' => $check->getCategory(),
                        'provider' => $check->getProvider(),
                        'healthStatus' => 'warning',
                        'description' => 'Check failed: ' . $e->getMessage(),
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Get system metadata without executing any checks.
     *
     * This method provides discovery information for API consumers and the UI:
     * - Available categories (with names, icons, sort orders)
     * - Registered providers (with branding and attribution data)
     * - Available checks (with slugs, titles, and category assignments)
     *
     * No checks are executed - this is purely metadata collection. Used by:
     * - AJAX endpoints to build UI before running checks
     * - API documentation/schema generation
     * - Third-party integrations that need to know what checks are available
     *
     * @return array<string, mixed> Associative array with 'categories', 'providers', and 'checks' keys
     *
     * @since 1.0.0
     */
    public function getMetadata(): array
    {
        $this->initialize();
        $checks = $this->collectChecks();

        // If no checks are available, the health checker plugins are likely disabled
        if ($checks === []) {
            throw new \RuntimeException(Text::_('COM_HEALTHCHECKER_NO_CHECKS_AVAILABLE'));
        }

        $checkList = [];
        foreach ($checks as $check) {
            $checkList[] = [
                'slug' => $check->getSlug(),
                'category' => $check->getCategory(),
                'title' => $check->getTitle(),
            ];
        }

        return [
            'categories' => array_map(
                fn(\MySitesGuru\HealthChecker\Component\Administrator\Category\HealthCategory $healthCategory): array => $healthCategory->toArray(),
                $this->categoryRegistry->all(),
            ),
            'providers' => array_map(
                fn(\MySitesGuru\HealthChecker\Component\Administrator\Provider\ProviderMetadata $providerMetadata): array => $providerMetadata->toArray(),
                $this->providerRegistry->all(),
            ),
            'checks' => $checkList,
        ];
    }

    /**
     * Sort results by status (critical first) and then by category order.
     *
     * This internal method is called after all checks have been executed.
     * Results are sorted using a two-tier approach:
     *
     * 1. Primary: Status sort order (critical=1, warning=2, good=3)
     * 2. Secondary: Category sort order (system=10, database=20, etc.)
     *
     * This ensures critical issues always appear first in the UI, regardless
     * of which category they belong to, while maintaining logical category
     * grouping within each status level.
     *
     * @since 1.0.0
     */
    private function sortResults(): void
    {
        usort($this->results, function (HealthCheckResult $a, HealthCheckResult $b): int {
            $statusOrder = $a->healthStatus->getSortOrder() <=> $b->healthStatus->getSortOrder();
            if ($statusOrder !== 0) {
                return $statusOrder;
            }

            $categoryOrderA = $this->categoryRegistry->get($a->category)
                ->sortOrder ?? 999;
            $categoryOrderB = $this->categoryRegistry->get($b->category)
                ->sortOrder ?? 999;

            return $categoryOrderA <=> $categoryOrderB;
        });
    }

    /**
     * Get all results from the last check run.
     *
     * Returns results in sorted order (critical first, then by category).
     * If no checks have been run yet, returns an empty array.
     *
     * @return HealthCheckResult[] Array of all check results
     *
     * @since 1.0.0
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Get results grouped by category in category sort order.
     *
     * Returns an associative array where keys are category slugs and values
     * are arrays of HealthCheckResult instances belonging to that category.
     *
     * Categories are ordered according to their sortOrder property (System first,
     * then Database, Security, etc.). Categories with results that aren't in the
     * registry are appended at the end.
     *
     * This is the primary method used by the UI to render results grouped by category.
     *
     * @return array<string, HealthCheckResult[]> Category slug => array of results
     *
     * @since 1.0.0
     */
    public function getResultsByCategory(): array
    {
        $grouped = [];

        foreach ($this->results as $result) {
            $grouped[$result->category][] = $result;
        }

        $sortedCategories = $this->categoryRegistry->getSorted();
        $sorted = [];

        foreach ($sortedCategories as $sortedCategory) {
            if (isset($grouped[$sortedCategory->slug])) {
                $sorted[$sortedCategory->slug] = $grouped[$sortedCategory->slug];
            }
        }

        foreach ($grouped as $slug => $results) {
            if (! isset($sorted[$slug])) {
                $sorted[$slug] = $results;
            }
        }

        return $sorted;
    }

    /**
     * Get results grouped by health status.
     *
     * Returns an associative array with three keys ('critical', 'warning', 'good'),
     * each containing an array of HealthCheckResult instances with that status.
     *
     * All three status keys are always present, even if empty. This ensures
     * consistent structure for API consumers and UI rendering.
     *
     * Used by filtering features in the UI and for generating status-specific reports.
     *
     * @return array<string, HealthCheckResult[]> Status value => array of results
     *
     * @since 1.0.0
     */
    public function getResultsByStatus(): array
    {
        $grouped = [
            HealthStatus::Critical->value => [],
            HealthStatus::Warning->value => [],
            HealthStatus::Good->value => [],
        ];

        foreach ($this->results as $result) {
            $grouped[$result->healthStatus->value][] = $result;
        }

        return $grouped;
    }

    /**
     * Get count of checks with critical status.
     *
     * @return int Number of critical results
     *
     * @since 1.0.0
     */
    public function getCriticalCount(): int
    {
        return count(
            array_filter(
                $this->results,
                fn(\MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult $healthCheckResult): bool => $healthCheckResult->healthStatus === HealthStatus::Critical,
            ),
        );
    }

    /**
     * Get count of checks with warning status.
     *
     * @return int Number of warning results
     *
     * @since 1.0.0
     */
    public function getWarningCount(): int
    {
        return count(
            array_filter(
                $this->results,
                fn(\MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult $healthCheckResult): bool => $healthCheckResult->healthStatus === HealthStatus::Warning,
            ),
        );
    }

    /**
     * Get count of checks with good status.
     *
     * @return int Number of good results
     *
     * @since 1.0.0
     */
    public function getGoodCount(): int
    {
        return count(
            array_filter(
                $this->results,
                fn(\MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult $healthCheckResult): bool => $healthCheckResult->healthStatus === HealthStatus::Good,
            ),
        );
    }

    /**
     * Get total count of all check results.
     *
     * @return int Total number of results from last run
     *
     * @since 1.0.0
     */
    public function getTotalCount(): int
    {
        return count($this->results);
    }

    /**
     * Get timestamp of the last health check run.
     *
     * Returns the DateTimeImmutable instance created when run() was last called.
     * Returns null if checks have never been run in this instance.
     *
     * @return \DateTimeImmutable|null Timestamp of last run, or null if never run
     *
     * @since 1.0.0
     */
    public function getLastRun(): ?\DateTimeImmutable
    {
        return $this->dateTimeImmutable;
    }

    /**
     * Get the category registry instance.
     *
     * Provides access to all registered categories. Used by controllers and
     * views that need to render category information or validate category slugs.
     *
     * @return CategoryRegistry The category registry service
     *
     * @since 1.0.0
     */
    public function getCategoryRegistry(): CategoryRegistry
    {
        return $this->categoryRegistry;
    }

    /**
     * Get the provider registry instance.
     *
     * Provides access to all registered providers. Used by UI components that
     * need to display provider attribution, logos, or links.
     *
     * @return ProviderRegistry The provider registry service
     *
     * @since 1.0.0
     */
    public function getProviderRegistry(): ProviderRegistry
    {
        return $this->providerRegistry;
    }

    /**
     * Execute health checks with optional caching.
     *
     * This method provides the same functionality as run() but with caching support.
     * When a valid cache TTL is provided:
     * - First checks if cached results exist and are still valid
     * - If cache hit: Loads results from cache instead of running checks
     * - If cache miss: Runs checks and stores results in cache
     *
     * Cache storage includes both the results array and the timestamp, so
     * subsequent calls within the TTL window return the exact same data.
     *
     * If $cacheTtl is null or <= 0, this falls back to standard run() behavior.
     *
     * @param int|null $cacheTtl Cache time-to-live in seconds (null or <=0 = no cache)
     *
     * @since 1.0.0
     */
    public function runWithCache(?int $cacheTtl = null): void
    {
        if ($cacheTtl === null || $cacheTtl <= 0) {
            $this->run();
            return;
        }

        $cache = $this->cacheControllerFactory->createCacheController('output', [
            'defaultgroup' => 'com_healthchecker',
            'caching' => true,
            'lifetime' => $cacheTtl / 60, // Convert seconds to minutes
        ]);

        $cacheId = 'healthcheck_results';

        // Try to get cached data
        $cachedData = $cache->get($cacheId);

        if ($cachedData !== false && $cachedData !== null) {
            // Cache hit - use cached data
            // Security: Use JSON instead of unserialize to prevent object injection attacks
            $data = json_decode((string) $cachedData, true);
            if (is_array($data) && isset($data['results']) && isset($data['lastRun'])) {
                // Reconstruct HealthCheckResult objects from cached array data
                /** @var array<array{status: string, title: string, description: string, slug: string, category: string, provider?: string}> $cachedResults */
                $cachedResults = $data['results'];
                $this->results = array_map(HealthCheckResult::fromArray(...), $cachedResults);
                $this->dateTimeImmutable = new \DateTimeImmutable($data['lastRun']);
                return;
            }
        }

        // Cache miss or invalid data - run checks and store
        $this->run();
        $data = [
            'results' => array_map(
                fn(HealthCheckResult $healthCheckResult): array => $healthCheckResult->toArray(),
                $this->results,
            ),
            'lastRun' => $this->dateTimeImmutable?->format('c'),
        ];
        // Security: Use JSON instead of serialize to prevent object injection attacks
        $cache->store(json_encode($data), $cacheId);
    }

    /**
     * Get summary statistics with optional caching.
     *
     * This is a convenience method that combines check execution with summary
     * data extraction. It's primarily used by the dashboard widget to display
     * quick stats without rendering full results.
     *
     * The method:
     * 1. Runs checks (with or without cache, depending on $cacheTtl)
     * 2. Extracts count statistics (critical, warning, good, total)
     * 3. Returns a structured array with stats and timestamp
     *
     * The returned array has a fixed structure for API consistency.
     *
     * @param int|null $cacheTtl Cache time-to-live in seconds (null or <=0 = no cache)
     *
     * @return array{critical: int, warning: int, good: int, total: int, lastRun: ?string} Summary statistics
     *
     * @since 1.0.0
     */
    public function getStatsWithCache(?int $cacheTtl = null): array
    {
        if ($cacheTtl === null || $cacheTtl <= 0) {
            $this->run();
        } else {
            $this->runWithCache($cacheTtl);
        }

        return [
            'critical' => $this->getCriticalCount(),
            'warning' => $this->getWarningCount(),
            'good' => $this->getGoodCount(),
            'total' => $this->getTotalCount(),
            'lastRun' => $this->dateTimeImmutable?->format('c'),
        ];
    }

    /**
     * Clear all cached health check results.
     *
     * Removes all cached data from the 'com_healthchecker' cache group.
     * This includes results from both runWithCache() and getStatsWithCache().
     *
     * Called when:
     * - User manually triggers a fresh check run
     * - Configuration changes that might affect check results
     * - Extensions are installed/uninstalled that could add/remove checks
     *
     * @since 1.0.0
     */
    public function clearCache(): void
    {
        $cache = $this->cacheControllerFactory->createCacheController('output', [
            'defaultgroup' => 'com_healthchecker',
            'caching' => true,
        ]);
        $cache->clean();
    }

    /**
     * Serialize the complete runner state to an array.
     *
     * Returns a comprehensive structure containing:
     * - lastRun: ISO 8601 timestamp of last check execution
     * - summary: Aggregated counts (critical, warning, good, total)
     * - categories: All registered categories with metadata
     * - providers: All registered providers with attribution data
     * - results: All check results with full details
     *
     * This is the primary method for JSON API responses and data exports.
     * The structure matches the expected format for the Health Checker API.
     *
     * @return array<string, mixed> Complete runner state as associative array
     *
     * @since 1.0.0
     */
    public function toArray(): array
    {
        return [
            'lastRun' => $this->dateTimeImmutable?->format('c'),
            'summary' => [
                'critical' => $this->getCriticalCount(),
                'warning' => $this->getWarningCount(),
                'good' => $this->getGoodCount(),
                'total' => $this->getTotalCount(),
            ],
            'categories' => array_map(
                fn(\MySitesGuru\HealthChecker\Component\Administrator\Category\HealthCategory $healthCategory): array => $healthCategory->toArray(),
                $this->categoryRegistry->all(),
            ),
            'providers' => array_map(
                fn(\MySitesGuru\HealthChecker\Component\Administrator\Provider\ProviderMetadata $providerMetadata): array => $providerMetadata->toArray(),
                $this->providerRegistry->all(),
            ),
            'results' => array_map(
                fn(\MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult $healthCheckResult): array => $healthCheckResult->toArray(),
                $this->results,
            ),
        ];
    }
}
