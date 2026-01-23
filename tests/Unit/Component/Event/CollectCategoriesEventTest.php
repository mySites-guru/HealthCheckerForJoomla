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
        $event = new CollectCategoriesEvent();
        $this->assertSame(HealthCheckerEvents::COLLECT_CATEGORIES->value, $event->getName());
    }

    public function testGetCategoriesReturnsEmptyArrayByDefault(): void
    {
        $event = new CollectCategoriesEvent();
        $this->assertSame([], $event->getCategories());
    }

    public function testAddResultAcceptsHealthCategory(): void
    {
        $event = new CollectCategoriesEvent();
        $category = MockFactory::createCategory('test', 'Test Category');

        $event->addResult($category);

        $categories = $event->getCategories();
        $this->assertCount(1, $categories);
        $this->assertSame($category, $categories[0]);
    }

    public function testAddResultAcceptsMultipleCategories(): void
    {
        $event = new CollectCategoriesEvent();
        $category1 = MockFactory::createCategory('system', 'System');
        $category2 = MockFactory::createCategory('database', 'Database');
        $category3 = MockFactory::createCategory('security', 'Security');

        $event->addResult($category1);
        $event->addResult($category2);
        $event->addResult($category3);

        $categories = $event->getCategories();
        $this->assertCount(3, $categories);
        $this->assertSame($category1, $categories[0]);
        $this->assertSame($category2, $categories[1]);
        $this->assertSame($category3, $categories[2]);
    }

    public function testTypeCheckResultThrowsExceptionForString(): void
    {
        $event = new CollectCategoriesEvent();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('only accepts HealthCategory instances');

        $event->typeCheckResult('not a category');
    }

    public function testTypeCheckResultThrowsExceptionForNull(): void
    {
        $event = new CollectCategoriesEvent();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('only accepts HealthCategory instances');

        $event->typeCheckResult(null);
    }

    public function testTypeCheckResultThrowsExceptionForStdClass(): void
    {
        $event = new CollectCategoriesEvent();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('only accepts HealthCategory instances');

        $event->typeCheckResult(new \stdClass());
    }

    public function testTypeCheckResultThrowsExceptionForArray(): void
    {
        $event = new CollectCategoriesEvent();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('only accepts HealthCategory instances');

        $event->typeCheckResult([
            'slug' => 'test',
            'label' => 'Test',
        ]);
    }

    public function testTypeCheckResultAcceptsValidCategory(): void
    {
        $event = new CollectCategoriesEvent();
        $category = MockFactory::createCategory('test', 'Test Category');

        // Should not throw an exception
        $event->typeCheckResult($category);

        $this->assertTrue(true); // If we get here, the test passed
    }

    public function testEventImplementsResultAwareInterface(): void
    {
        $event = new CollectCategoriesEvent();
        $this->assertInstanceOf(\Joomla\CMS\Event\Result\ResultAwareInterface::class, $event);
    }

    public function testCategoriesPreserveOrder(): void
    {
        $event = new CollectCategoriesEvent();

        // Add categories in specific order
        $event->addResult(MockFactory::createCategory('third', 'Third', sortOrder: 300));
        $event->addResult(MockFactory::createCategory('first', 'First', sortOrder: 100));
        $event->addResult(MockFactory::createCategory('second', 'Second', sortOrder: 200));

        $categories = $event->getCategories();

        // Should preserve insertion order, not sort order
        $this->assertSame('third', $categories[0]->slug);
        $this->assertSame('first', $categories[1]->slug);
        $this->assertSame('second', $categories[2]->slug);
    }
}
