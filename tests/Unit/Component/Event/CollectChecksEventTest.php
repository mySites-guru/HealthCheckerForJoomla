<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Component\Event;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckInterface;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectChecksEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\HealthCheckerEvents;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CollectChecksEvent::class)]
class CollectChecksEventTest extends TestCase
{
    public function testEventHasCorrectName(): void
    {
        $event = new CollectChecksEvent();
        $this->assertSame(HealthCheckerEvents::COLLECT_CHECKS->value, $event->getName());
    }

    public function testGetChecksReturnsEmptyArrayByDefault(): void
    {
        $event = new CollectChecksEvent();
        $this->assertSame([], $event->getChecks());
    }

    public function testAddResultAcceptsHealthCheckInterface(): void
    {
        $event = new CollectChecksEvent();
        $check = $this->createTestCheck();

        $event->addResult($check);

        $checks = $event->getChecks();
        $this->assertCount(1, $checks);
        $this->assertSame($check, $checks[0]);
    }

    public function testAddResultAcceptsMultipleChecks(): void
    {
        $event = new CollectChecksEvent();
        $check1 = $this->createTestCheck('test.check1');
        $check2 = $this->createTestCheck('test.check2');
        $check3 = $this->createTestCheck('test.check3');

        $event->addResult($check1);
        $event->addResult($check2);
        $event->addResult($check3);

        $checks = $event->getChecks();
        $this->assertCount(3, $checks);
        $this->assertSame($check1, $checks[0]);
        $this->assertSame($check2, $checks[1]);
        $this->assertSame($check3, $checks[2]);
    }

    public function testTypeCheckResultThrowsExceptionForInvalidType(): void
    {
        $event = new CollectChecksEvent();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('only accepts HealthCheckInterface instances');

        $event->typeCheckResult('not a check');
    }

    public function testTypeCheckResultThrowsExceptionForNull(): void
    {
        $event = new CollectChecksEvent();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('only accepts HealthCheckInterface instances');

        $event->typeCheckResult(null);
    }

    public function testTypeCheckResultThrowsExceptionForObject(): void
    {
        $event = new CollectChecksEvent();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('only accepts HealthCheckInterface instances');

        $event->typeCheckResult(new \stdClass());
    }

    public function testTypeCheckResultAcceptsValidCheck(): void
    {
        $event = new CollectChecksEvent();
        $check = $this->createTestCheck();

        // Should not throw an exception
        $event->typeCheckResult($check);

        $this->assertTrue(true); // If we get here, the test passed
    }

    public function testEventImplementsResultAwareInterface(): void
    {
        $event = new CollectChecksEvent();
        $this->assertInstanceOf(\Joomla\CMS\Event\Result\ResultAwareInterface::class, $event);
    }

    /**
     * Create a test check instance for testing
     */
    private function createTestCheck(string $slug = 'test.check'): HealthCheckInterface
    {
        return new class ($slug) extends AbstractHealthCheck {
            public function __construct(
                private readonly string $checkSlug,
            ) {}

            public function getSlug(): string
            {
                return $this->checkSlug;
            }

            public function getCategory(): string
            {
                return 'system';
            }

            protected function performCheck(): HealthCheckResult
            {
                return $this->good('OK');
            }
        };
    }
}
