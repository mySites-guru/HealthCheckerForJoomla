<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Security;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Security\IndexFileCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(IndexFileCheck::class)]
class IndexFileCheckTest extends TestCase
{
    private const INDEX_FILES = [
        'index.html',
        'index.htm',
        'default.html',
        'Default.html',
        'default.htm',
        'Default.htm',
    ];

    private IndexFileCheck $indexFileCheck;

    protected function setUp(): void
    {
        $this->indexFileCheck = new IndexFileCheck();

        if (! is_dir(JPATH_ROOT)) {
            mkdir(JPATH_ROOT, 0777, true);
        }

        // Clean up any existing index files
        foreach (self::INDEX_FILES as $filename) {
            $path = JPATH_ROOT . '/' . $filename;
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    protected function tearDown(): void
    {
        foreach (self::INDEX_FILES as $filename) {
            $path = JPATH_ROOT . '/' . $filename;
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('security.index_file', $this->indexFileCheck->getSlug());
    }

    public function testGetCategoryReturnsSecurity(): void
    {
        $this->assertSame('security', $this->indexFileCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->indexFileCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->indexFileCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsGoodWhenNoIndexFilesExist(): void
    {
        $healthCheckResult = $this->indexFileCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('No placeholder index files', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenIndexHtmlExists(): void
    {
        file_put_contents(JPATH_ROOT . '/index.html', '<html></html>');

        $healthCheckResult = $this->indexFileCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('index.html', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenMultipleFilesExist(): void
    {
        file_put_contents(JPATH_ROOT . '/index.html', '<html></html>');
        file_put_contents(JPATH_ROOT . '/default.htm', '<html></html>');

        $healthCheckResult = $this->indexFileCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('index.html', $healthCheckResult->description);
        $this->assertStringContainsString('default.htm', $healthCheckResult->description);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function indexFileProvider(): array
    {
        return [
            'index.html' => ['index.html'],
            'index.htm' => ['index.htm'],
            'default.html' => ['default.html'],
            'Default.html' => ['Default.html'],
            'default.htm' => ['default.htm'],
            'Default.htm' => ['Default.htm'],
        ];
    }

    #[DataProvider('indexFileProvider')]
    public function testEachIndexFileTriggersWarning(string $filename): void
    {
        file_put_contents(JPATH_ROOT . '/' . $filename, '<html></html>');

        $healthCheckResult = $this->indexFileCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString($filename, $healthCheckResult->description);
    }

    public function testRunNeverReturnsCritical(): void
    {
        // With files present
        file_put_contents(JPATH_ROOT . '/index.html', '<html></html>');
        $result = $this->indexFileCheck->run();
        $this->assertNotSame(HealthStatus::Critical, $result->healthStatus);

        // Without files
        unlink(JPATH_ROOT . '/index.html');
        $result = $this->indexFileCheck->run();
        $this->assertNotSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testRunResultContainsSlug(): void
    {
        $healthCheckResult = $this->indexFileCheck->run();

        $this->assertSame('security.index_file', $healthCheckResult->slug);
    }

    public function testRunResultHasProvider(): void
    {
        $healthCheckResult = $this->indexFileCheck->run();

        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testRunResultHasCategory(): void
    {
        $healthCheckResult = $this->indexFileCheck->run();

        $this->assertSame('security', $healthCheckResult->category);
    }
}
