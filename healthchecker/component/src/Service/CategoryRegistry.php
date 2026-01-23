<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace MySitesGuru\HealthChecker\Component\Administrator\Service;

use MySitesGuru\HealthChecker\Component\Administrator\Category\HealthCategory;

\defined('_JEXEC') || die;

/**
 * Registry service for managing health check categories.
 *
 * This service provides centralized storage and retrieval of health check categories
 * used throughout the Health Checker component. Categories are registered by plugins
 * during the CollectCategoriesEvent phase and are used to organize health checks
 * in the UI.
 *
 * Categories are stored in-memory only (no database persistence) and are re-collected
 * on each request through the event dispatcher system.
 *
 * @since 1.0.0
 */
final class CategoryRegistry
{
    /**
     * Internal storage for registered categories, keyed by category slug.
     *
     * @var array<string, HealthCategory>
     */
    private array $categories = [];

    /**
     * Register a health check category in the registry.
     *
     * This method stores a category instance keyed by its slug. If a category
     * with the same slug already exists, it will be overwritten. This is by design
     * to allow plugins to override default categories if needed.
     *
     * @param HealthCategory $healthCategory The category instance to register
     *
     * @since 1.0.0
     */
    public function register(HealthCategory $healthCategory): void
    {
        $this->categories[$healthCategory->slug] = $healthCategory;
    }

    /**
     * Retrieve a category by its slug.
     *
     * Returns the HealthCategory instance if found, or null if no category
     * with the given slug exists in the registry.
     *
     * @param string $slug The category slug (e.g., 'system', 'security', 'database')
     *
     * @return HealthCategory|null The category instance or null if not found
     *
     * @since 1.0.0
     */
    public function get(string $slug): ?HealthCategory
    {
        return $this->categories[$slug] ?? null;
    }

    /**
     * Check if a category exists in the registry.
     *
     * This is a convenience method to determine if a category has been registered
     * without retrieving the full instance.
     *
     * @param string $slug The category slug to check
     *
     * @return bool True if the category exists, false otherwise
     *
     * @since 1.0.0
     */
    public function has(string $slug): bool
    {
        return isset($this->categories[$slug]);
    }

    /**
     * Get all registered categories as an associative array.
     *
     * Returns all categories keyed by their slugs in the order they were registered.
     * This method preserves the original registration order and slug keys.
     *
     * @return array<string, HealthCategory> Associative array of slug => HealthCategory
     *
     * @since 1.0.0
     */
    public function all(): array
    {
        return $this->categories;
    }

    /**
     * Get all categories sorted by their sort order.
     *
     * Returns categories as a zero-indexed array sorted by the sortOrder property
     * of each HealthCategory. This is the primary method used by the UI to display
     * categories in the correct order (e.g., System first, then Database, Security, etc.).
     *
     * The sort order values are defined in each HealthCategory instance (typically
     * in increments of 10: System=10, Database=20, Security=30, etc.).
     *
     * @return HealthCategory[] Zero-indexed array of categories sorted by sortOrder
     *
     * @since 1.0.0
     */
    public function getSorted(): array
    {
        $categories = array_values($this->categories);
        usort($categories, fn(HealthCategory $a, HealthCategory $b): int => $a->sortOrder <=> $b->sortOrder);

        return $categories;
    }
}
