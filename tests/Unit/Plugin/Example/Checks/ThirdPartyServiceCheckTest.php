<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Example\Checks;

use HealthChecker\Tests\Utilities\MockHttpFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Example\Checks\ThirdPartyServiceCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ThirdPartyServiceCheck::class)]
class ThirdPartyServiceCheckTest extends TestCase
{
    private ThirdPartyServiceCheck $thirdPartyServiceCheck;

    protected function setUp(): void
    {
        $this->thirdPartyServiceCheck = new ThirdPartyServiceCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('example.thirdparty_service', $this->thirdPartyServiceCheck->getSlug());
    }

    public function testGetCategoryReturnsThirdparty(): void
    {
        $this->assertSame('thirdparty', $this->thirdPartyServiceCheck->getCategory());
    }

    public function testGetProviderReturnsExample(): void
    {
        $this->assertSame('example', $this->thirdPartyServiceCheck->getProvider());
    }

    public function testGetTitleReturnsNonEmptyString(): void
    {
        $title = $this->thirdPartyServiceCheck->getTitle();

        $this->assertNotEmpty($title);
    }

    public function testRunReturnsGoodWhenServiceReachable(): void
    {
        $httpClient = MockHttpFactory::createWithHeadResponse(200);
        $this->thirdPartyServiceCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->thirdPartyServiceCheck->run();

        $this->assertInstanceOf(HealthCheckResult::class, $healthCheckResult);
        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('reachable', $healthCheckResult->description);
        $this->assertStringContainsString('normally', $healthCheckResult->description);
    }

    public function testRunReturnsCriticalWhenServiceUnreachable(): void
    {
        $httpClient = MockHttpFactory::createThatThrows('Connection refused');
        $this->thirdPartyServiceCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->thirdPartyServiceCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('Cannot reach', $healthCheckResult->description);
    }

    public function testRunReturnsCriticalWhenHttpError(): void
    {
        $httpClient = MockHttpFactory::createWithHeadResponse(500);
        $this->thirdPartyServiceCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->thirdPartyServiceCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('Cannot reach', $healthCheckResult->description);
    }

    public function testRunReturnsCriticalWhenResponseCodeZero(): void
    {
        $httpClient = MockHttpFactory::createWithHeadResponse(0);
        $this->thirdPartyServiceCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->thirdPartyServiceCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
    }

    public function testResultHasCorrectSlug(): void
    {
        $httpClient = MockHttpFactory::createWithHeadResponse(200);
        $this->thirdPartyServiceCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->thirdPartyServiceCheck->run();

        $this->assertSame('example.thirdparty_service', $healthCheckResult->slug);
    }

    public function testResultHasCorrectCategory(): void
    {
        $httpClient = MockHttpFactory::createWithHeadResponse(200);
        $this->thirdPartyServiceCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->thirdPartyServiceCheck->run();

        $this->assertSame('thirdparty', $healthCheckResult->category);
    }

    public function testResultHasCorrectProvider(): void
    {
        $httpClient = MockHttpFactory::createWithHeadResponse(200);
        $this->thirdPartyServiceCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->thirdPartyServiceCheck->run();

        $this->assertSame('example', $healthCheckResult->provider);
    }

    public function testResultDescriptionContainsExampleCheckMarker(): void
    {
        $httpClient = MockHttpFactory::createWithHeadResponse(200);
        $this->thirdPartyServiceCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->thirdPartyServiceCheck->run();

        $this->assertStringContainsString('[EXAMPLE CHECK]', $healthCheckResult->description);
    }

    public function testResultDescriptionContainsDisableInstructions(): void
    {
        $httpClient = MockHttpFactory::createWithHeadResponse(200);
        $this->thirdPartyServiceCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->thirdPartyServiceCheck->run();

        $this->assertStringContainsString('Health Checker - Example Provider', $healthCheckResult->description);
        $this->assertStringContainsString('Extensions', $healthCheckResult->description);
        $this->assertStringContainsString('Plugins', $healthCheckResult->description);
    }

    public function testResultDescriptionMentionsJoomlaApi(): void
    {
        $httpClient = MockHttpFactory::createWithHeadResponse(200);
        $this->thirdPartyServiceCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->thirdPartyServiceCheck->run();

        $this->assertStringContainsString('Joomla API', $healthCheckResult->description);
    }

    public function testRunReturnsCorrectStatusForClientError(): void
    {
        $httpClient = MockHttpFactory::createWithHeadResponse(404);
        $this->thirdPartyServiceCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->thirdPartyServiceCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsGoodFor3xxRedirect(): void
    {
        // 3xx responses are still successful - server responded
        $httpClient = MockHttpFactory::createWithHeadResponse(301);
        $this->thirdPartyServiceCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->thirdPartyServiceCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsWarningWhenResponseIsSlow(): void
    {
        // Create HTTP client that simulates slow response (>3 seconds threshold)
        $httpClient = MockHttpFactory::createWithSlowHeadResponse(200, 3.5);
        $this->thirdPartyServiceCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->thirdPartyServiceCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('slowly', $healthCheckResult->description);
    }
}
