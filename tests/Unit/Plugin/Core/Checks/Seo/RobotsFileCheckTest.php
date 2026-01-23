<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Seo;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Seo\RobotsFileCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RobotsFileCheck::class)]
class RobotsFileCheckTest extends TestCase
{
    private RobotsFileCheck $check;

    private string $robotsPath;

    protected function setUp(): void
    {
        $this->check = new RobotsFileCheck();
        $this->robotsPath = JPATH_ROOT . '/robots.txt';

        // Ensure JPATH_ROOT exists
        if (! is_dir(JPATH_ROOT)) {
            mkdir(JPATH_ROOT, 0777, true);
        }

        // Clean up any existing robots.txt
        if (file_exists($this->robotsPath)) {
            unlink($this->robotsPath);
        }
    }

    protected function tearDown(): void
    {
        // Clean up robots.txt after each test
        if (file_exists($this->robotsPath)) {
            unlink($this->robotsPath);
        }
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('seo.robots_file', $this->check->getSlug());
    }

    public function testGetCategoryReturnsSeo(): void
    {
        $this->assertSame('seo', $this->check->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->check->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->check->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsWarningWhenRobotsFileNotFound(): void
    {
        // No robots.txt file exists
        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('not found', $result->description);
    }

    public function testRunReturnsGoodWhenValidRobotsFileExists(): void
    {
        $robotsContent = <<<'ROBOTS'
User-agent: *
Allow: /
Disallow: /administrator/
Disallow: /tmp/
Sitemap: https://example.com/sitemap.xml
ROBOTS;
        file_put_contents($this->robotsPath, $robotsContent);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('present', $result->description);
    }

    public function testRunReturnsWarningWhenDisallowRootFound(): void
    {
        // Create robots.txt that blocks entire site
        $robotsContent = <<<'ROBOTS'
User-agent: *
Disallow: /
ROBOTS;
        file_put_contents($this->robotsPath, $robotsContent);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('blocking', $result->description);
    }

    public function testRunReturnsGoodWhenDisallowSubdirectoryOnly(): void
    {
        // Disallowing subdirectories should be fine
        $robotsContent = <<<'ROBOTS'
User-agent: *
Disallow: /admin/
Disallow: /private/
ROBOTS;
        file_put_contents($this->robotsPath, $robotsContent);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunHandlesDisallowRootWithWhitespace(): void
    {
        // "Disallow: /" followed by whitespace and newline
        $robotsContent = "User-agent: *\nDisallow: /\n";
        file_put_contents($this->robotsPath, $robotsContent);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('blocking', $result->description);
    }

    public function testRunHandlesCaseInsensitiveDisallow(): void
    {
        // Test case insensitive matching
        $robotsContent = "User-agent: *\nDISALLOW: /\n";
        file_put_contents($this->robotsPath, $robotsContent);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testRunReturnsGoodWhenEmptyDisallow(): void
    {
        // Empty Disallow allows everything
        $robotsContent = <<<'ROBOTS'
User-agent: *
Disallow:
ROBOTS;
        file_put_contents($this->robotsPath, $robotsContent);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunReturnsGoodWhenDisallowSlashWithSpace(): void
    {
        // "Disallow: / " (with trailing space) is different from "Disallow: /"
        // The code explicitly checks for "Disallow: / " to avoid false positives
        $robotsContent = "User-agent: *\nDisallow: / something\n";
        file_put_contents($this->robotsPath, $robotsContent);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunNeverReturnsCritical(): void
    {
        // Even with blocking robots.txt, should only return warning
        $robotsContent = "User-agent: *\nDisallow: /";
        file_put_contents($this->robotsPath, $robotsContent);

        $result = $this->check->run();

        $this->assertNotSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testRunHandlesMultipleUserAgentsWithDisallowRoot(): void
    {
        // If any user-agent blocks root, it should warn
        $robotsContent = <<<'ROBOTS'
User-agent: Googlebot
Disallow:

User-agent: *
Disallow: /
ROBOTS;
        file_put_contents($this->robotsPath, $robotsContent);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }
}
