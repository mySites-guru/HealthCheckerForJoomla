<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Categories;

use MySitesGuru\HealthChecker\Component\Administrator\Category\HealthCategory;

\defined('_JEXEC') || die;

/**
 * Core Health Check Categories Registry
 *
 * This class defines and provides the 8 core health check categories used by the
 * Health Checker component. Categories organize health checks into logical groups
 * in the UI and determine the display order and visual presentation.
 *
 * The 8 core categories are:
 * 1. System & Hosting (system) - PHP, server, hosting environment checks
 * 2. Database (database) - Database connection, tables, integrity checks
 * 3. Security (security) - Security configuration, SSL, headers, authentication
 * 4. Users (users) - User accounts, permissions, authentication policies
 * 5. Extensions (extensions) - Joomla core, extensions, updates, compatibility
 * 6. Performance (performance) - Caching, optimization, page speed, resources
 * 7. SEO (seo) - Search engine optimization, metadata, sitemaps, robots.txt
 * 8. Content Quality (content) - Articles, menus, media, content structure
 *
 * Each category includes:
 * - Unique slug identifier (lowercase, used in code and URLs)
 * - Translatable label (language key from component language file)
 * - FontAwesome icon class (for visual identification in UI)
 * - Sort order (numeric, determines display sequence: 10, 20, 30, etc.)
 *
 * Categories are registered via the CorePlugin's onCollectCategories event handler
 * and used throughout the component for filtering, grouping, and organizing health
 * check results.
 *
 * Third-party plugins can register additional custom categories by implementing
 * their own onHealthCheckerCollectCategories event handler.
 *
 * @subpackage  HealthChecker.Core
 * @since       1.0.0
 */
final class CoreCategories
{
    /**
     * Returns all 8 core health check categories.
     *
     * Provides the complete set of built-in health check categories used to organize
     * checks in the Health Checker UI. Each category is configured with:
     *
     * - slug: Unique identifier (e.g., 'system', 'database') used in code
     * - label: Language key for translatable category name (e.g., 'COM_HEALTHCHECKER_CATEGORY_SYSTEM')
     * - icon: FontAwesome icon class (e.g., 'fa-server', 'fa-database')
     * - sortOrder: Numeric value determining display order (10, 20, 30, etc.)
     *
     * Categories are sorted by sortOrder in ascending order, so System (10) appears
     * first, followed by Database (20), Security (30), and so on.
     *
     * The categories returned are:
     * - System & Hosting (10): PHP version, extensions, memory, server configuration
     * - Database (20): Connection, charset, orphaned tables, integrity
     * - Security (30): SSL, headers, authentication, API security
     * - Users (40): User accounts, super admins, passwords, duplicate emails
     * - Extensions (50): Joomla version, updates, unused extensions
     * - Performance (60): Caching, image optimization, lazy loading, redirects
     * - SEO (70): Metadata, sitemaps, robots.txt, alt text
     * - Content Quality (80): Articles, menus, media, scheduled content
     *
     * @return array<int, HealthCategory> Array of HealthCategory objects in sort order
     *
     * @since 1.0.0
     */
    public static function getCategories(): array
    {
        return [
            new HealthCategory(
                slug: 'system',
                label: 'COM_HEALTHCHECKER_CATEGORY_SYSTEM',
                icon: 'fa-server',
                sortOrder: 10,
            ),
            new HealthCategory(
                slug: 'database',
                label: 'COM_HEALTHCHECKER_CATEGORY_DATABASE',
                icon: 'fa-database',
                sortOrder: 20,
            ),
            new HealthCategory(
                slug: 'security',
                label: 'COM_HEALTHCHECKER_CATEGORY_SECURITY',
                icon: 'fa-shield-alt',
                sortOrder: 30,
            ),
            new HealthCategory(
                slug: 'users',
                label: 'COM_HEALTHCHECKER_CATEGORY_USERS',
                icon: 'fa-users',
                sortOrder: 40,
            ),
            new HealthCategory(
                slug: 'extensions',
                label: 'COM_HEALTHCHECKER_CATEGORY_EXTENSIONS',
                icon: 'fa-puzzle-piece',
                sortOrder: 50,
            ),
            new HealthCategory(
                slug: 'performance',
                label: 'COM_HEALTHCHECKER_CATEGORY_PERFORMANCE',
                icon: 'fa-tachometer-alt',
                sortOrder: 60,
            ),
            new HealthCategory(
                slug: 'seo',
                label: 'COM_HEALTHCHECKER_CATEGORY_SEO',
                icon: 'fa-search',
                sortOrder: 70,
            ),
            new HealthCategory(
                slug: 'content',
                label: 'COM_HEALTHCHECKER_CATEGORY_CONTENT',
                icon: 'fa-file-alt',
                sortOrder: 80,
            ),
        ];
    }
}
