<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Component\Event;

use HealthChecker\Tests\Utilities\MockFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectCategoriesEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\HealthCheckerEvents;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CollectCategoriesEvent::class)]
class CollectCategoriesEventTest extends TestCase
{
    public function testEventHasCorrectName(): void
    {
        $collectCategoriesEvent = new CollectCategoriesEvent();
        $this->assertSame(HealthCheckerEvents::COLLECT_CATEGORIES->value, $collectCategoriesEvent->getName());
    }

    public function testGetCategoriesReturnsEmptyArrayByDefault(): void
    {
        $collectCategoriesEvent = new CollectCategoriesEvent();
        $this->assertSame([], $collectCategoriesEvent->getCategories());
    }

    public function testAddResultAcceptsHealthCategory(): void
    {
        $collectCategoriesEvent = new CollectCategoriesEvent();
        $healthCategory = MockFactory::createCategory('test', 'Test Category');

        $collectCategoriesEvent->addResult($healthCategory);

        $categories = $collectCategoriesEvent->getCategories();
        $this->assertCount(1, $categories);
        $this->assertSame($healthCategory, $categories[0]);
    }

    public function testAddResultAcceptsMultipleCategories(): void
    {
        $collectCategoriesEvent = new CollectCategoriesEvent();
        $healthCategory = MockFactory::createCategory('system', 'System');
        $category2 = MockFactory::createCategory('database', 'Database');
        $category3 = MockFactory::createCategory('security', 'Security');

        $collectCategoriesEvent->addResult($healthCategory);
        $collectCategoriesEvent->addResult($category2);
        $collectCategoriesEvent->addResult($category3);

        $categories = $collectCategoriesEvent->getCategories();
        $this->assertCount(3, $categories);
        $this->assertSame($healthCategory, $categories[0]);
        $this->assertSame($category2, $categories[1]);
        $this->assertSame($category3, $categories[2]);
    }

    public function testTypeCheckResultThrowsExceptionForString(): void
    {
        $collectCategoriesEvent = new CollectCategoriesEvent();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('only accepts HealthCategory instances');

        $collectCategoriesEvent->typeCheckResult('not a category');
    }

    public function testTypeCheckResultThrowsExceptionForNull(): void
    {
        $collectCategoriesEvent = new CollectCategoriesEvent();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('only accepts HealthCategory instances');

        $collectCategoriesEvent->typeCheckResult(null);
    }

    public function testTypeCheckResultThrowsExceptionForStdClass(): void
    {
        $collectCategoriesEvent = new CollectCategoriesEvent();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('only accepts HealthCategory instances');

        $collectCategoriesEvent->typeCheckResult(new \stdClass());
    }

    public function testTypeCheckResultThrowsExceptionForArray(): void
    {
        $collectCategoriesEvent = new CollectCategoriesEvent();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('only accepts HealthCategory instances');

        $collectCategoriesEvent->typeCheckResult([
            'slug' => 'test',
            'label' => 'Test',
        ]);
    }

    public function testTypeCheckResultAcceptsValidCategory(): void
    {
        $collectCategoriesEvent = new CollectCategoriesEvent();
        $healthCategory = MockFactory::createCategory('test', 'Test Category');

        // Should not throw an exception
        $collectCategoriesEvent->typeCheckResult($healthCategory);

        $this->assertTrue(true); // If we get here, the test passed
    }

    public function testEventImplementsResultAwareInterface(): void
    {
        $collectCategoriesEvent = new CollectCategoriesEvent();
        $this->assertInstanceOf(\Joomla\CMS\Event\Result\ResultAwareInterface::class, $collectCategoriesEvent);
    }

    public function testCategoriesPreserveOrder(): void
    {
        $collectCategoriesEvent = new CollectCategoriesEvent();

        // Add categories in specific order
        $collectCategoriesEvent->addResult(MockFactory::createCategory('third', 'Third', sortOrder: 300));
        $collectCategoriesEvent->addResult(MockFactory::createCategory('first', 'First', sortOrder: 100));
        $collectCategoriesEvent->addResult(MockFactory::createCategory('second', 'Second', sortOrder: 200));

        $categories = $collectCategoriesEvent->getCategories();

        // Should preserve insertion order, not sort order
        $this->assertSame('third', $categories[0]->slug);
        $this->assertSame('first', $categories[1]->slug);
        $this->assertSame('second', $categories[2]->slug);
    }
}
