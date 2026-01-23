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
    private MySitesGuruConnectionCheck $check;

    private string $tempDir;

    protected function setUp(): void
    {
        $this->check = new MySitesGuruConnectionCheck();
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
        $this->assertSame('mysitesguru.connection', $this->check->getSlug());
    }

    public function testGetCategoryReturnsMySitesGuru(): void
    {
        $this->assertSame('mysitesguru', $this->check->getCategory());
    }

    public function testGetProviderReturnsMySitesGuru(): void
    {
        $this->assertSame('mysitesguru', $this->check->getProvider());
    }

    public function testGetTitleReturnsNonEmptyString(): void
    {
        $title = $this->check->getTitle();

        $this->assertNotEmpty($title);
    }

    public function testRunReturnsWarningWhenBfnetworkFolderNotFound(): void
    {
        // Point to a non-existent directory
        $this->check->setBfnetworkPath('/non/existent/path/bfnetwork');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('not connected', $result->description);
        $this->assertStringContainsString('mysites.guru', $result->description);
    }

    public function testRunReturnsGoodWhenBfnetworkFolderExists(): void
    {
        // Create temp directory to simulate bfnetwork folder
        mkdir($this->tempDir, 0755, true);
        $this->check->setBfnetworkPath($this->tempDir);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('connected', $result->description);
        $this->assertStringContainsString('24/7', $result->description);
    }

    public function testResultHasCorrectSlug(): void
    {
        $this->check->setBfnetworkPath('/non/existent/path');

        $result = $this->check->run();

        $this->assertSame('mysitesguru.connection', $result->slug);
    }

    public function testResultHasCorrectCategory(): void
    {
        $this->check->setBfnetworkPath('/non/existent/path');

        $result = $this->check->run();

        $this->assertSame('mysitesguru', $result->category);
    }

    public function testResultHasCorrectProvider(): void
    {
        $this->check->setBfnetworkPath('/non/existent/path');

        $result = $this->check->run();

        $this->assertSame('mysitesguru', $result->provider);
    }

    public function testWarningDescriptionContainsLearnMoreLink(): void
    {
        $this->check->setBfnetworkPath('/non/existent/path');

        $result = $this->check->run();

        $this->assertStringContainsString('https://mysites.guru', $result->description);
    }

    public function testGoodDescriptionMentionsAutomatedMonitoring(): void
    {
        mkdir($this->tempDir, 0755, true);
        $this->check->setBfnetworkPath($this->tempDir);

        $result = $this->check->run();

        $this->assertStringContainsString('automatically', $result->description);
        $this->assertStringContainsString('alerts', $result->description);
    }
}
