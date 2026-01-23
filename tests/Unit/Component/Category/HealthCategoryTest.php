<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Component\Category;

use MySitesGuru\HealthChecker\Component\Administrator\Category\HealthCategory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HealthCategory::class)]
class HealthCategoryTest extends TestCase
{
    public function testConstructorSetsAllRequiredProperties(): void
    {
        $healthCategory = new HealthCategory(
            slug: 'system',
            label: 'System & Hosting',
            icon: 'fa-server',
            sortOrder: 10,
        );

        $this->assertSame('system', $healthCategory->slug);
        $this->assertSame('System & Hosting', $healthCategory->label);
        $this->assertSame('fa-server', $healthCategory->icon);
        $this->assertSame(10, $healthCategory->sortOrder);
        $this->assertNull($healthCategory->logoUrl);
    }

    public function testSortOrderDefaultsTo50(): void
    {
        $healthCategory = new HealthCategory(slug: 'custom', label: 'Custom Category', icon: 'fa-custom');

        $this->assertSame(50, $healthCategory->sortOrder);
    }

    public function testLogoUrlCanBeSet(): void
    {
        $healthCategory = new HealthCategory(
            slug: 'security',
            label: 'Security',
            icon: 'fa-shield-halved',
            sortOrder: 30,
            logoUrl: 'https://example.com/logo.png',
        );

        $this->assertSame('https://example.com/logo.png', $healthCategory->logoUrl);
    }

    public function testPropertiesAreReadonly(): void
    {
        $healthCategory = new HealthCategory(slug: 'test', label: 'Test', icon: 'fa-test');

        $this->expectException(\Error::class);
        // @phpstan-ignore-next-line - Testing readonly property
        $healthCategory->slug = 'modified';
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $healthCategory = new HealthCategory(
            slug: 'database',
            label: 'Database',
            icon: 'fa-database',
            sortOrder: 20,
        );

        $array = $healthCategory->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('slug', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertArrayHasKey('icon', $array);
        $this->assertArrayHasKey('sortOrder', $array);
        $this->assertArrayHasKey('logoUrl', $array);
    }

    public function testToArrayContainsCorrectValues(): void
    {
        $healthCategory = new HealthCategory(
            slug: 'performance',
            label: 'Performance',
            icon: 'fa-gauge-high',
            sortOrder: 60,
            logoUrl: 'https://cdn.example.com/perf.svg',
        );

        $array = $healthCategory->toArray();

        $this->assertSame('performance', $array['slug']);
        $this->assertSame('Performance', $array['label']);
        $this->assertSame('fa-gauge-high', $array['icon']);
        $this->assertSame(60, $array['sortOrder']);
        $this->assertSame('https://cdn.example.com/perf.svg', $array['logoUrl']);
    }

    public function testToArrayTranslatesLanguageKey(): void
    {
        // Our mock Text::_() returns the key unchanged
        $healthCategory = new HealthCategory(
            slug: 'seo',
            label: 'COM_HEALTHCHECKER_CATEGORY_SEO',
            icon: 'fa-magnifying-glass',
            sortOrder: 70,
        );

        $array = $healthCategory->toArray();

        // With our mock, translation just returns the key
        $this->assertSame('COM_HEALTHCHECKER_CATEGORY_SEO', $array['label']);
    }

    public function testCategoryIsImmutable(): void
    {
        $healthCategory = new HealthCategory(
            slug: 'content',
            label: 'Content Quality',
            icon: 'fa-file-lines',
            sortOrder: 80,
        );

        $array1 = $healthCategory->toArray();
        $array2 = $healthCategory->toArray();

        $this->assertEquals($array1, $array2);
        $this->assertSame($healthCategory->slug, 'content');
    }

    public function testCanSerializeToJson(): void
    {
        $healthCategory = new HealthCategory(slug: 'users', label: 'Users', icon: 'fa-users', sortOrder: 40);

        $json = json_encode($healthCategory->toArray());
        $this->assertIsString($json);

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertSame('users', $decoded['slug']);
        $this->assertSame('Users', $decoded['label']);
    }

    public function testSortOrderAllowsInsertion(): void
    {
        $category1 = new HealthCategory('cat1', 'Category 1', 'fa-1', 10);
        $category2 = new HealthCategory('cat2', 'Category 2', 'fa-2', 15);
        $category3 = new HealthCategory('cat3', 'Category 3', 'fa-3', 20);

        $categories = [$category3, $category1, $category2];

        usort($categories, fn($a, $b): int => $a->sortOrder <=> $b->sortOrder);

        $this->assertSame('cat1', $categories[0]->slug);
        $this->assertSame('cat2', $categories[1]->slug);
        $this->assertSame('cat3', $categories[2]->slug);
    }
}
