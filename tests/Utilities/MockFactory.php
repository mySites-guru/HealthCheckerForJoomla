<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Utilities;

use MySitesGuru\HealthChecker\Component\Administrator\Category\HealthCategory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Component\Administrator\Provider\ProviderMetadata;

/**
 * Factory for creating test fixtures and mock objects
 */
class MockFactory
{
    /**
     * Create a mock HealthCheckResult
     *
     * @param HealthStatus $status      The health status
     * @param string       $title       The title
     * @param string       $description The description
     * @param string       $slug        The slug
     * @param string       $category    The category
     * @param string       $provider    The provider
     */
    public static function createResult(
        HealthStatus $status = HealthStatus::Good,
        string $title = 'Test Check',
        string $description = 'Test description',
        string $slug = 'test.check',
        string $category = 'system',
        string $provider = 'core',
    ): HealthCheckResult {
        return new HealthCheckResult(
            healthStatus: $status,
            title: $title,
            description: $description,
            slug: $slug,
            category: $category,
            provider: $provider,
        );
    }

    /**
     * Create a mock HealthCategory
     *
     * @param string $slug      The category slug
     * @param string $label     The category label
     * @param string $icon      The icon class
     * @param int    $sortOrder The sort order
     */
    public static function createCategory(
        string $slug = 'test',
        string $label = 'Test Category',
        string $icon = 'fa-test',
        int $sortOrder = 100,
    ): HealthCategory {
        return new HealthCategory(slug: $slug, label: $label, icon: $icon, sortOrder: $sortOrder);
    }

    /**
     * Create a mock ProviderMetadata
     *
     * @param string      $slug        The provider slug
     * @param string      $name        The provider name
     * @param string      $description The description
     * @param string|null $url         The URL
     * @param string|null $icon        The icon
     * @param string|null $logoUrl     The logo URL
     * @param string|null $version     The version
     */
    public static function createProvider(
        string $slug = 'test',
        string $name = 'Test Provider',
        string $description = 'Test provider description',
        ?string $url = null,
        ?string $icon = null,
        ?string $logoUrl = null,
        ?string $version = null,
    ): ProviderMetadata {
        return new ProviderMetadata(
            slug: $slug,
            name: $name,
            description: $description,
            url: $url,
            icon: $icon,
            logoUrl: $logoUrl,
            version: $version,
        );
    }
}
