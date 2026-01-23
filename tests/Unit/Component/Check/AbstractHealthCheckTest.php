<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Component\Check;

use Joomla\Database\DatabaseInterface;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractHealthCheck::class)]
class AbstractHealthCheckTest extends TestCase
{
    public function testGetProviderDefaultsToCore(): void
    {
        $check = $this->createTestCheck();
        $this->assertSame('core', $check->getProvider());
    }

    public function testGetTitleReturnsLanguageKeyOrFallback(): void
    {
        $check = $this->createTestCheck();
        $title = $check->getTitle();

        // With our mock Text::_(), if translation doesn't exist, it falls back to the slug
        // The implementation checks if the translated value equals the key, and if so, returns the slug
        $this->assertNotEmpty($title);
        $this->assertSame('test.check', $title); // Fallback to slug when no translation
    }

    public function testSetDatabaseInjectsDatabase(): void
    {
        $check = $this->createTestCheck();
        $db = $this->createStub(DatabaseInterface::class);

        $check->setDatabase($db);

        $this->assertSame($db, $check->getDatabase());
    }

    public function testGetDatabaseReturnsNullByDefault(): void
    {
        $check = $this->createTestCheck();
        $this->assertNull($check->getDatabase());
    }

    public function testCriticalHelperCreatesCorrectResult(): void
    {
        $check = $this->createTestCheck();
        $result = $check->exposeCritical('Critical issue found');

        $this->assertInstanceOf(HealthCheckResult::class, $result);
        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertSame('Critical issue found', $result->description);
        $this->assertSame('test.check', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
    }

    public function testWarningHelperCreatesCorrectResult(): void
    {
        $check = $this->createTestCheck();
        $result = $check->exposeWarning('Warning message');

        $this->assertInstanceOf(HealthCheckResult::class, $result);
        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertSame('Warning message', $result->description);
    }

    public function testGoodHelperCreatesCorrectResult(): void
    {
        $check = $this->createTestCheck();
        $result = $check->exposeGood('Everything is fine');

        $this->assertInstanceOf(HealthCheckResult::class, $result);
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertSame('Everything is fine', $result->description);
    }

    public function testRunCallsPerformCheck(): void
    {
        $check = new class extends AbstractHealthCheck {
            public bool $performCheckCalled = false;

            public function getSlug(): string
            {
                return 'test.check';
            }

            public function getCategory(): string
            {
                return 'system';
            }

            protected function performCheck(): HealthCheckResult
            {
                $this->performCheckCalled = true;
                return $this->good('Check performed');
            }
        };

        $healthCheckResult = $check->run();

        $this->assertTrue($check->performCheckCalled);
        $this->assertInstanceOf(HealthCheckResult::class, $healthCheckResult);
        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunCatchesExceptionsAndReturnsWarning(): void
    {
        $check = new class extends AbstractHealthCheck {
            public function getSlug(): string
            {
                return 'test.check';
            }

            public function getCategory(): string
            {
                return 'system';
            }

            protected function performCheck(): HealthCheckResult
            {
                throw new \RuntimeException('Something went wrong');
            }
        };

        $healthCheckResult = $check->run();

        $this->assertInstanceOf(HealthCheckResult::class, $healthCheckResult);
        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('Something went wrong', $healthCheckResult->description);
    }

    public function testCustomProviderCanBeOverridden(): void
    {
        $check = new class extends AbstractHealthCheck {
            public function getSlug(): string
            {
                return 'custom.check';
            }

            public function getCategory(): string
            {
                return 'system';
            }

            public function getProvider(): string
            {
                return 'my_plugin';
            }

            protected function performCheck(): HealthCheckResult
            {
                return $this->good('OK');
            }
        };

        $this->assertSame('my_plugin', $check->getProvider());

        $healthCheckResult = $check->run();
        $this->assertSame('my_plugin', $healthCheckResult->provider);
    }

    public function testHelperMethodsUseTitleFromGetTitle(): void
    {
        $check = $this->createTestCheck();
        $result = $check->exposeGood('Test');

        // Title should be from getTitle() - which in our mock falls back to slug
        $this->assertSame('test.check', $result->title);
    }

    /**
     * Create a test check instance with exposed protected methods
     */
    private function createTestCheck(): object
    {
        return new class extends AbstractHealthCheck {
            public function getSlug(): string
            {
                return 'test.check';
            }

            public function getCategory(): string
            {
                return 'system';
            }

            protected function performCheck(): HealthCheckResult
            {
                return $this->good('OK');
            }

            // Expose protected methods for testing
            public function exposeCritical(string $description): HealthCheckResult
            {
                return $this->critical($description);
            }

            public function exposeWarning(string $description): HealthCheckResult
            {
                return $this->warning($description);
            }

            public function exposeGood(string $description): HealthCheckResult
            {
                return $this->good($description);
            }
        };
    }
}
