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

    public function testRequireDatabaseThrowsExceptionWhenNotInjected(): void
    {
        $check = $this->createTestCheckWithRequireDatabase();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('requires database access but no database was injected');

        $check->callRequireDatabase();
    }

    public function testRequireDatabaseReturnsDatabaseWhenInjected(): void
    {
        $check = $this->createTestCheckWithRequireDatabase();
        $db = $this->createStub(DatabaseInterface::class);
        $check->setDatabase($db);

        $result = $check->callRequireDatabase();

        $this->assertSame($db, $result);
    }

    public function testSetHttpClientInjectsHttpClient(): void
    {
        $check = $this->createTestCheckWithHttpClient();
        $http = $this->createStub(\Joomla\CMS\Http\Http::class);

        $check->setHttpClient($http);
        $result = $check->callGetHttpClient();

        $this->assertSame($http, $result);
    }

    public function testGetHttpClientReturnsInjectedClient(): void
    {
        $check = $this->createTestCheckWithHttpClient();
        $http = $this->createStub(\Joomla\CMS\Http\Http::class);
        $check->setHttpClient($http);

        $result = $check->callGetHttpClient();

        $this->assertInstanceOf(\Joomla\CMS\Http\Http::class, $result);
    }

    public function testGetHttpClientFallsBackToHttpFactory(): void
    {
        $check = $this->createTestCheckWithHttpClient();

        // Don't inject any HTTP client, verify it falls back to HttpFactory
        $result = $check->callGetHttpClient();

        $this->assertInstanceOf(\Joomla\CMS\Http\Http::class, $result);
    }

    public function testGetDocsUrlReturnsNullByDefault(): void
    {
        $check = $this->createTestCheck();
        $this->assertNull($check->getDocsUrl());
    }

    public function testGetActionUrlReturnsNullByDefault(): void
    {
        $check = $this->createTestCheck();
        $this->assertNull($check->getActionUrl());
    }

    public function testCustomDocsUrlCanBeOverridden(): void
    {
        $check = new class extends AbstractHealthCheck {
            public function getSlug(): string
            {
                return 'custom.docs_url';
            }

            public function getCategory(): string
            {
                return 'system';
            }

            public function getDocsUrl(): ?string
            {
                return 'https://example.com/docs/custom';
            }

            protected function performCheck(): HealthCheckResult
            {
                return $this->good('OK');
            }
        };

        $this->assertSame('https://example.com/docs/custom', $check->getDocsUrl());

        $result = $check->run();
        $this->assertSame('https://example.com/docs/custom', $result->docsUrl);
    }

    public function testCustomActionUrlCanBeOverridden(): void
    {
        $check = new class extends AbstractHealthCheck {
            public function getSlug(): string
            {
                return 'custom.action_url';
            }

            public function getCategory(): string
            {
                return 'system';
            }

            public function getActionUrl(?HealthStatus $status = null): ?string
            {
                return '/administrator/index.php?option=com_custom';
            }

            protected function performCheck(): HealthCheckResult
            {
                return $this->good('OK');
            }
        };

        $this->assertSame('/administrator/index.php?option=com_custom', $check->getActionUrl());

        $result = $check->run();
        $this->assertSame('/administrator/index.php?option=com_custom', $result->actionUrl);
    }

    public function testConditionalActionUrlBasedOnStatus(): void
    {
        $check = new class extends AbstractHealthCheck {
            public function getSlug(): string
            {
                return 'custom.conditional_action';
            }

            public function getCategory(): string
            {
                return 'system';
            }

            public function getActionUrl(?HealthStatus $status = null): ?string
            {
                // Only show action URL for failed checks, not for Good status
                if ($status === HealthStatus::Good) {
                    return null;
                }
                return '/administrator/index.php?option=com_fix';
            }

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

            protected function performCheck(): HealthCheckResult
            {
                return $this->good('OK');
            }
        };

        // Action URL should be present for Critical status
        $criticalResult = $check->exposeCritical('Critical issue');
        $this->assertSame('/administrator/index.php?option=com_fix', $criticalResult->actionUrl);

        // Action URL should be present for Warning status
        $warningResult = $check->exposeWarning('Warning issue');
        $this->assertSame('/administrator/index.php?option=com_fix', $warningResult->actionUrl);

        // Action URL should be null for Good status
        $goodResult = $check->exposeGood('All good');
        $this->assertNull($goodResult->actionUrl);
    }

    public function testHelperMethodsIncludeUrls(): void
    {
        $check = new class extends AbstractHealthCheck {
            public function getSlug(): string
            {
                return 'test.with_urls';
            }

            public function getCategory(): string
            {
                return 'system';
            }

            public function getDocsUrl(): ?string
            {
                return 'https://docs.test.com';
            }

            public function getActionUrl(?HealthStatus $status = null): ?string
            {
                return '/admin/test';
            }

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

            protected function performCheck(): HealthCheckResult
            {
                return $this->good('OK');
            }
        };

        $criticalResult = $check->exposeCritical('Critical issue');
        $this->assertSame('https://docs.test.com', $criticalResult->docsUrl);
        $this->assertSame('/admin/test', $criticalResult->actionUrl);

        $warningResult = $check->exposeWarning('Warning issue');
        $this->assertSame('https://docs.test.com', $warningResult->docsUrl);
        $this->assertSame('/admin/test', $warningResult->actionUrl);

        $goodResult = $check->exposeGood('All good');
        $this->assertSame('https://docs.test.com', $goodResult->docsUrl);
        $this->assertSame('/admin/test', $goodResult->actionUrl);
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

    /**
     * Create a test check with exposed requireDatabase method
     */
    private function createTestCheckWithRequireDatabase(): object
    {
        return new class extends AbstractHealthCheck {
            public function getSlug(): string
            {
                return 'test.require_database';
            }

            public function getCategory(): string
            {
                return 'system';
            }

            protected function performCheck(): HealthCheckResult
            {
                return $this->good('OK');
            }

            public function callRequireDatabase(): DatabaseInterface
            {
                return $this->requireDatabase();
            }
        };
    }

    /**
     * Create a test check with exposed getHttpClient method
     */
    private function createTestCheckWithHttpClient(): object
    {
        return new class extends AbstractHealthCheck {
            public function getSlug(): string
            {
                return 'test.http_client';
            }

            public function getCategory(): string
            {
                return 'system';
            }

            protected function performCheck(): HealthCheckResult
            {
                return $this->good('OK');
            }

            public function callGetHttpClient(): \Joomla\CMS\Http\Http
            {
                return $this->getHttpClient();
            }
        };
    }
}
