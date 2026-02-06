<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use HealthChecker\Tests\Utilities\MockHttpFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\PhpEolCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpEolCheck::class)]
class PhpEolCheckTest extends TestCase
{
    private PhpEolCheck $phpEolCheck;

    protected function setUp(): void
    {
        $this->phpEolCheck = new PhpEolCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.php_eol', $this->phpEolCheck->getSlug());
    }

    public function testGetCategoryReturnsSystem(): void
    {
        $this->assertSame('system', $this->phpEolCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->phpEolCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->phpEolCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsGoodWhenVersionUnderActiveSupport(): void
    {
        // Create EOL data where current PHP version has plenty of time left
        $cycle = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
        $supportDate = (new \DateTime('+1 year'))->format('Y-m-d');
        $eolDate = (new \DateTime('+2 years'))->format('Y-m-d');

        $eolData = [
            [
                'cycle' => $cycle,
                'support' => $supportDate,
                'eol' => $eolDate,
            ],
        ];

        $httpClient = MockHttpFactory::createWithJsonResponse(200, $eolData);
        $this->phpEolCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->phpEolCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('active support', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenApproachingSupportEnd(): void
    {
        // Create EOL data where support ends within 90 days
        $cycle = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
        $supportDate = (new \DateTime('+30 days'))->format('Y-m-d');
        $eolDate = (new \DateTime('+1 year'))->format('Y-m-d');

        $eolData = [
            [
                'cycle' => $cycle,
                'support' => $supportDate,
                'eol' => $eolDate,
            ],
        ];

        $httpClient = MockHttpFactory::createWithJsonResponse(200, $eolData);
        $this->phpEolCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->phpEolCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('ends in', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenInSecurityOnlyMode(): void
    {
        // Create EOL data where active support has ended but EOL is in the future
        $cycle = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
        $supportDate = (new \DateTime('-30 days'))->format('Y-m-d');
        $eolDate = (new \DateTime('+6 months'))->format('Y-m-d');

        $eolData = [
            [
                'cycle' => $cycle,
                'support' => $supportDate,
                'eol' => $eolDate,
            ],
        ];

        $httpClient = MockHttpFactory::createWithJsonResponse(200, $eolData);
        $this->phpEolCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->phpEolCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('security-only', $healthCheckResult->description);
    }

    public function testRunReturnsCriticalWhenPastEol(): void
    {
        // Create EOL data where PHP version is past EOL
        $cycle = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
        $supportDate = (new \DateTime('-1 year'))->format('Y-m-d');
        $eolDate = (new \DateTime('-30 days'))->format('Y-m-d');

        $eolData = [
            [
                'cycle' => $cycle,
                'support' => $supportDate,
                'eol' => $eolDate,
            ],
        ];

        $httpClient = MockHttpFactory::createWithJsonResponse(200, $eolData);
        $this->phpEolCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->phpEolCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('end-of-life', $healthCheckResult->description);
        $this->assertStringContainsString('immediately', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenApiError(): void
    {
        $httpClient = MockHttpFactory::createWithGetResponse(500, '');
        $this->phpEolCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->phpEolCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('Unable to fetch', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenConnectionFails(): void
    {
        $httpClient = MockHttpFactory::createThatThrows('Connection refused');
        $this->phpEolCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->phpEolCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('Unable to fetch', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenVersionNotFoundInApi(): void
    {
        // Return EOL data that doesn't include the current PHP version
        $eolData = [
            [
                'cycle' => '7.4',
                'support' => '2020-11-28',
                'eol' => '2022-11-28',
            ],
        ];

        $httpClient = MockHttpFactory::createWithJsonResponse(200, $eolData);
        $this->phpEolCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->phpEolCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('not found', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenInvalidJsonResponse(): void
    {
        $httpClient = MockHttpFactory::createWithGetResponse(200, 'not valid json');
        $this->phpEolCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->phpEolCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testResultDescriptionContainsPhpVersion(): void
    {
        $cycle = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
        $supportDate = (new \DateTime('+1 year'))->format('Y-m-d');
        $eolDate = (new \DateTime('+2 years'))->format('Y-m-d');

        $eolData = [
            [
                'cycle' => $cycle,
                'support' => $supportDate,
                'eol' => $eolDate,
            ],
        ];

        $httpClient = MockHttpFactory::createWithJsonResponse(200, $eolData);
        $this->phpEolCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->phpEolCheck->run();

        $this->assertStringContainsString(PHP_VERSION, $healthCheckResult->description);
    }

    public function testResultMetadata(): void
    {
        $httpClient = MockHttpFactory::createThatThrows('Network error');
        $this->phpEolCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->phpEolCheck->run();

        $this->assertSame('system.php_eol', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testRunReturnsWarningWhenDateParsingFails(): void
    {
        // Create EOL data with invalid date values that cannot be parsed
        $cycle = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
        $eolData = [
            [
                'cycle' => $cycle,
                'support' => 'invalid-date-format',
                'eol' => 'also-not-a-date',
            ],
        ];

        $httpClient = MockHttpFactory::createWithJsonResponse(200, $eolData);
        $this->phpEolCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->phpEolCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('could not be parsed', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenBooleanEolDate(): void
    {
        // Some versions return boolean `false` for EOL meaning "not yet determined"
        $cycle = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
        $eolData = [
            [
                'cycle' => $cycle,
                'support' => (new \DateTime('+1 year'))->format('Y-m-d'),
                'eol' => false,  // PHP 8.x returns false when EOL not yet determined
            ],
        ];

        $httpClient = MockHttpFactory::createWithJsonResponse(200, $eolData);
        $this->phpEolCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->phpEolCheck->run();

        // Should return warning because false cannot be parsed as a date
        // The warning message will contain the underlying TypeError message from DateTime
        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        // The error message contains 'DateTime' from the TypeError
        $this->assertStringContainsString('DateTime', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenSupportDateBoolean(): void
    {
        // API might return boolean for support date
        $cycle = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
        $eolData = [
            [
                'cycle' => $cycle,
                'support' => false,
                'eol' => (new \DateTime('+2 years'))->format('Y-m-d'),
            ],
        ];

        $httpClient = MockHttpFactory::createWithJsonResponse(200, $eolData);
        $this->phpEolCheck->setHttpClient($httpClient);

        $healthCheckResult = $this->phpEolCheck->run();

        // Should return warning because false cannot be parsed as a date
        // The warning message will contain the underlying TypeError message from DateTime
        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        // The error message contains 'DateTime' from the TypeError
        $this->assertStringContainsString('DateTime', $healthCheckResult->description);
    }
}
