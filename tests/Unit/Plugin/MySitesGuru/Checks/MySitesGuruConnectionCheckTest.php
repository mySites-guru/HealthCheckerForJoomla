<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\MySitesGuru\Checks;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\MySitesGuru\Checks\MySitesGuruConnectionCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MySitesGuruConnectionCheck::class)]
class MySitesGuruConnectionCheckTest extends TestCase
{
    private MySitesGuruConnectionCheck $mySitesGuruConnectionCheck;

    private string $tempDir;

    protected function setUp(): void
    {
        $this->mySitesGuruConnectionCheck = new MySitesGuruConnectionCheck();
        $this->tempDir = sys_get_temp_dir() . '/healthchecker_test_' . uniqid();
    }

    protected function tearDown(): void
    {
        // Clean up temp directory
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('mysitesguru.connection', $this->mySitesGuruConnectionCheck->getSlug());
    }

    public function testGetCategoryReturnsMySitesGuru(): void
    {
        $this->assertSame('mysitesguru', $this->mySitesGuruConnectionCheck->getCategory());
    }

    public function testGetProviderReturnsMySitesGuru(): void
    {
        $this->assertSame('mysitesguru', $this->mySitesGuruConnectionCheck->getProvider());
    }

    public function testGetTitleReturnsNonEmptyString(): void
    {
        $title = $this->mySitesGuruConnectionCheck->getTitle();

        $this->assertNotEmpty($title);
    }

    public function testRunReturnsWarningWhenBfnetworkFolderNotFound(): void
    {
        // Point to a non-existent directory
        $this->mySitesGuruConnectionCheck->setBfnetworkPath('/non/existent/path/bfnetwork');

        $healthCheckResult = $this->mySitesGuruConnectionCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('not connected', $healthCheckResult->description);
        $this->assertStringContainsString('mysites.guru', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWhenBfnetworkFolderExists(): void
    {
        // Create temp directory to simulate bfnetwork folder
        mkdir($this->tempDir, 0755, true);
        $this->mySitesGuruConnectionCheck->setBfnetworkPath($this->tempDir);

        $healthCheckResult = $this->mySitesGuruConnectionCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('connected', $healthCheckResult->description);
        $this->assertStringContainsString('24/7', $healthCheckResult->description);
    }

    public function testResultHasCorrectSlug(): void
    {
        $this->mySitesGuruConnectionCheck->setBfnetworkPath('/non/existent/path');

        $healthCheckResult = $this->mySitesGuruConnectionCheck->run();

        $this->assertSame('mysitesguru.connection', $healthCheckResult->slug);
    }

    public function testResultHasCorrectCategory(): void
    {
        $this->mySitesGuruConnectionCheck->setBfnetworkPath('/non/existent/path');

        $healthCheckResult = $this->mySitesGuruConnectionCheck->run();

        $this->assertSame('mysitesguru', $healthCheckResult->category);
    }

    public function testResultHasCorrectProvider(): void
    {
        $this->mySitesGuruConnectionCheck->setBfnetworkPath('/non/existent/path');

        $healthCheckResult = $this->mySitesGuruConnectionCheck->run();

        $this->assertSame('mysitesguru', $healthCheckResult->provider);
    }

    public function testWarningDescriptionContainsLearnMoreLink(): void
    {
        $this->mySitesGuruConnectionCheck->setBfnetworkPath('/non/existent/path');

        $healthCheckResult = $this->mySitesGuruConnectionCheck->run();

        $this->assertStringContainsString('https://mysites.guru', $healthCheckResult->description);
    }

    public function testGoodDescriptionMentionsAutomatedMonitoring(): void
    {
        mkdir($this->tempDir, 0755, true);
        $this->mySitesGuruConnectionCheck->setBfnetworkPath($this->tempDir);

        $healthCheckResult = $this->mySitesGuruConnectionCheck->run();

        $this->assertStringContainsString('automatically', $healthCheckResult->description);
        $this->assertStringContainsString('alerts', $healthCheckResult->description);
    }
}
