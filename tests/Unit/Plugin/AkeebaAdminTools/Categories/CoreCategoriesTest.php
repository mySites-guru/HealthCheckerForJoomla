<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\AkeebaAdminTools\Categories;

use MySitesGuru\HealthChecker\Component\Administrator\Category\HealthCategory;
use MySitesGuru\HealthChecker\Plugin\Core\Categories\CoreCategories;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for CoreCategories located in Admin Tools plugin directory
 *
 * NOTE: This file (healthchecker/plugins/akeebaadmintools/src/Categories/CoreCategories.php)
 * appears to be a duplicate of the core plugin's CoreCategories class. It uses the
 * MySitesGuru\HealthChecker\Plugin\Core\Categories namespace rather than an Admin Tools
 * specific namespace, and is not actually used by the Admin Tools plugin. The Admin Tools
 * plugin registers its category directly in the plugin class.
 *
 * This test documents the file's current state. Consider removing this duplicate file
 * or updating its namespace if it was intended to be a separate Admin Tools categories class.
 */
#[CoversClass(CoreCategories::class)]
class CoreCategoriesTest extends TestCase
{
    public function testGetCategoriesReturnsArray(): void
    {
        $categories = CoreCategories::getCategories();

        $this->assertIsArray($categories);
    }

    public function testGetCategoriesReturnsEightCategories(): void
    {
        $categories = CoreCategories::getCategories();

        $this->assertCount(8, $categories);
    }

    public function testAllCategoriesAreHealthCategoryInstances(): void
    {
        $categories = CoreCategories::getCategories();

        foreach ($categories as $category) {
            $this->assertInstanceOf(HealthCategory::class, $category);
        }
    }

    public function testCategoriesHaveExpectedSlugs(): void
    {
        $categories = CoreCategories::getCategories();
        $slugs = array_map(static fn(HealthCategory $healthCategory): string => $healthCategory->slug, $categories);

        $expectedSlugs = ['system', 'database', 'security', 'users', 'extensions', 'performance', 'seo', 'content'];

        foreach ($expectedSlugs as $expectedSlug) {
            $this->assertContains($expectedSlug, $slugs);
        }
    }

    public function testCategoriesHaveExpectedSortOrder(): void
    {
        $categories = CoreCategories::getCategories();

        $expectedOrders = [
            'system' => 10,
            'database' => 20,
            'security' => 30,
            'users' => 40,
            'extensions' => 50,
            'performance' => 60,
            'seo' => 70,
            'content' => 80,
        ];

        foreach ($categories as $category) {
            $this->assertSame(
                $expectedOrders[$category->slug],
                $category->sortOrder,
                sprintf("Category '%s' has unexpected sort order", $category->slug),
            );
        }
    }

    public function testAllCategoriesHaveIcons(): void
    {
        $categories = CoreCategories::getCategories();

        foreach ($categories as $category) {
            $this->assertNotEmpty($category->icon, sprintf("Category '%s' has no icon", $category->slug));
            $this->assertStringStartsWith('fa-', $category->icon);
        }
    }

    public function testAllCategoriesHaveLabels(): void
    {
        $categories = CoreCategories::getCategories();

        foreach ($categories as $category) {
            $this->assertNotEmpty($category->label, sprintf("Category '%s' has no label", $category->slug));
            $this->assertStringStartsWith('COM_HEALTHCHECKER_CATEGORY_', $category->label);
        }
    }

    public function testSystemCategoryIsFirst(): void
    {
        $categories = CoreCategories::getCategories();

        $this->assertSame('system', $categories[0]->slug);
        $this->assertSame(10, $categories[0]->sortOrder);
    }

    public function testContentCategoryIsLast(): void
    {
        $categories = CoreCategories::getCategories();

        $lastCategory = $categories[count($categories) - 1];
        $this->assertSame('content', $lastCategory->slug);
        $this->assertSame(80, $lastCategory->sortOrder);
    }

    public function testCategoriesAreSortedBySortOrder(): void
    {
        $categories = CoreCategories::getCategories();

        $previousOrder = 0;
        foreach ($categories as $category) {
            $this->assertGreaterThan(
                $previousOrder,
                $category->sortOrder,
                'Categories are not in ascending sortOrder',
            );
            $previousOrder = $category->sortOrder;
        }
    }

    public function testNoCategoriesHaveLogoUrl(): void
    {
        // Core categories don't use custom logos (they use FontAwesome icons)
        $categories = CoreCategories::getCategories();

        foreach ($categories as $category) {
            $this->assertNull(
                $category->logoUrl,
                sprintf("Category '%s' has a logo URL but core categories should only use icons", $category->slug),
            );
        }
    }

    public function testSecurityCategoryHasShieldIcon(): void
    {
        $categories = CoreCategories::getCategories();
        $securityCategory = null;

        foreach ($categories as $category) {
            if ($category->slug === 'security') {
                $securityCategory = $category;

                break;
            }
        }

        $this->assertNotNull($securityCategory);
        $this->assertSame('fa-shield-alt', $securityCategory->icon);
    }

    public function testDatabaseCategoryHasDatabaseIcon(): void
    {
        $categories = CoreCategories::getCategories();
        $databaseCategory = null;

        foreach ($categories as $category) {
            if ($category->slug === 'database') {
                $databaseCategory = $category;

                break;
            }
        }

        $this->assertNotNull($databaseCategory);
        $this->assertSame('fa-database', $databaseCategory->icon);
    }
}
