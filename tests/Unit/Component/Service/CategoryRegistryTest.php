<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Component\Service;

use MySitesGuru\HealthChecker\Component\Administrator\Category\HealthCategory;
use MySitesGuru\HealthChecker\Component\Administrator\Service\CategoryRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CategoryRegistry::class)]
class CategoryRegistryTest extends TestCase
{
    public function testRegistryStartsEmpty(): void
    {
        $categoryRegistry = new CategoryRegistry();
        $this->assertEmpty($categoryRegistry->all());
    }

    public function testCanRegisterCategory(): void
    {
        $categoryRegistry = new CategoryRegistry();
        $healthCategory = new HealthCategory('system', 'System', 'fa-server', 10);

        $categoryRegistry->register($healthCategory);

        $this->assertTrue($categoryRegistry->has('system'));
        $this->assertSame($healthCategory, $categoryRegistry->get('system'));
    }

    public function testRegisteringOverwritesExistingCategory(): void
    {
        $categoryRegistry = new CategoryRegistry();

        $category1 = new HealthCategory('test', 'First', 'fa-1', 10);
        $category2 = new HealthCategory('test', 'Second', 'fa-2', 20);

        $categoryRegistry->register($category1);
        $categoryRegistry->register($category2);

        $retrieved = $categoryRegistry->get('test');
        $this->assertSame('Second', $retrieved->label);
        $this->assertSame(20, $retrieved->sortOrder);
    }

    public function testGetReturnsNullForNonExistentCategory(): void
    {
        $categoryRegistry = new CategoryRegistry();
        $this->assertNull($categoryRegistry->get('nonexistent'));
    }

    public function testHasReturnsFalseForNonExistentCategory(): void
    {
        $categoryRegistry = new CategoryRegistry();
        $this->assertFalse($categoryRegistry->has('nonexistent'));
    }

    public function testAllReturnsAllCategories(): void
    {
        $categoryRegistry = new CategoryRegistry();

        $cat1 = new HealthCategory('system', 'System', 'fa-server', 10);
        $cat2 = new HealthCategory('database', 'Database', 'fa-database', 20);
        $cat3 = new HealthCategory('security', 'Security', 'fa-shield-halved', 30);

        $categoryRegistry->register($cat1);
        $categoryRegistry->register($cat2);
        $categoryRegistry->register($cat3);

        $all = $categoryRegistry->all();

        $this->assertCount(3, $all);
        $this->assertArrayHasKey('system', $all);
        $this->assertArrayHasKey('database', $all);
        $this->assertArrayHasKey('security', $all);
    }

    public function testAllReturnsAssociativeArray(): void
    {
        $categoryRegistry = new CategoryRegistry();
        $healthCategory = new HealthCategory('test', 'Test', 'fa-test', 50);

        $categoryRegistry->register($healthCategory);
        $all = $categoryRegistry->all();

        $this->assertIsArray($all);
        $this->assertArrayHasKey('test', $all);
        $this->assertInstanceOf(HealthCategory::class, $all['test']);
    }

    public function testGetSortedReturnsCategoriesSortedBySortOrder(): void
    {
        $categoryRegistry = new CategoryRegistry();

        // Register in random order
        $cat3 = new HealthCategory('cat3', 'Third', 'fa-3', 30);
        $cat1 = new HealthCategory('cat1', 'First', 'fa-1', 10);
        $cat2 = new HealthCategory('cat2', 'Second', 'fa-2', 20);

        $categoryRegistry->register($cat3);
        $categoryRegistry->register($cat1);
        $categoryRegistry->register($cat2);

        $sorted = $categoryRegistry->getSorted();

        $this->assertCount(3, $sorted);
        $this->assertSame('cat1', $sorted[0]->slug);
        $this->assertSame('cat2', $sorted[1]->slug);
        $this->assertSame('cat3', $sorted[2]->slug);
    }

    public function testGetSortedReturnsZeroIndexedArray(): void
    {
        $categoryRegistry = new CategoryRegistry();

        $cat1 = new HealthCategory('a', 'A', 'fa-a', 20);
        $cat2 = new HealthCategory('b', 'B', 'fa-b', 10);

        $categoryRegistry->register($cat1);
        $categoryRegistry->register($cat2);

        $sorted = $categoryRegistry->getSorted();

        $this->assertArrayHasKey(0, $sorted);
        $this->assertArrayHasKey(1, $sorted);
        $this->assertArrayNotHasKey('a', $sorted);
        $this->assertArrayNotHasKey('b', $sorted);
    }

    public function testGetSortedHandlesTiesBySortOrder(): void
    {
        $categoryRegistry = new CategoryRegistry();

        $cat1 = new HealthCategory('first', 'First', 'fa-1', 10);
        $cat2 = new HealthCategory('second', 'Second', 'fa-2', 10);
        $cat3 = new HealthCategory('third', 'Third', 'fa-3', 20);

        $categoryRegistry->register($cat1);
        $categoryRegistry->register($cat2);
        $categoryRegistry->register($cat3);

        $sorted = $categoryRegistry->getSorted();

        // When sort order is tied, order is preserved from registration
        $this->assertSame(10, $sorted[0]->sortOrder);
        $this->assertSame(10, $sorted[1]->sortOrder);
        $this->assertSame(20, $sorted[2]->sortOrder);
    }

    public function testCanRegisterStandardEightCategories(): void
    {
        $categoryRegistry = new CategoryRegistry();

        $categories = [
            new HealthCategory('system', 'System & Hosting', 'fa-server', 10),
            new HealthCategory('database', 'Database', 'fa-database', 20),
            new HealthCategory('security', 'Security', 'fa-shield-halved', 30),
            new HealthCategory('users', 'Users', 'fa-users', 40),
            new HealthCategory('extensions', 'Extensions', 'fa-puzzle-piece', 50),
            new HealthCategory('performance', 'Performance', 'fa-gauge-high', 60),
            new HealthCategory('seo', 'SEO', 'fa-magnifying-glass', 70),
            new HealthCategory('content', 'Content Quality', 'fa-file-lines', 80),
        ];

        foreach ($categories as $category) {
            $categoryRegistry->register($category);
        }

        $this->assertCount(8, $categoryRegistry->all());

        $sorted = $categoryRegistry->getSorted();
        $this->assertSame('system', $sorted[0]->slug);
        $this->assertSame('database', $sorted[1]->slug);
        $this->assertSame('security', $sorted[2]->slug);
        $this->assertSame('users', $sorted[3]->slug);
        $this->assertSame('extensions', $sorted[4]->slug);
        $this->assertSame('performance', $sorted[5]->slug);
        $this->assertSame('seo', $sorted[6]->slug);
        $this->assertSame('content', $sorted[7]->slug);
    }
}
