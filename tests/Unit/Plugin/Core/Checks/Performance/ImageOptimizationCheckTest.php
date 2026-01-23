<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Performance;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Performance\ImageOptimizationCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ImageOptimizationCheck::class)]
class ImageOptimizationCheckTest extends TestCase
{
    private ImageOptimizationCheck $check;

    private string $imagesPath;

    protected function setUp(): void
    {
        $this->check = new ImageOptimizationCheck();
        $this->imagesPath = JPATH_ROOT . '/images';

        // Clean up and recreate the test directory
        $this->removeDirectory($this->imagesPath);
    }

    protected function tearDown(): void
    {
        // Clean up test files
        $this->removeDirectory($this->imagesPath);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('performance.image_optimization', $this->check->getSlug());
    }

    public function testGetCategoryReturnsPerformance(): void
    {
        $this->assertSame('performance', $this->check->getCategory());
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

    public function testRunReturnsGoodWhenImagesDirectoryNotFound(): void
    {
        // No images directory exists
        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('directory not found', $result->description);
    }

    public function testRunReturnsGoodWhenNoOversizedImages(): void
    {
        mkdir($this->imagesPath, 0777, true);

        // Create small images (under 500KB)
        $this->createTestImage($this->imagesPath . '/small.jpg', 100 * 1024); // 100KB
        $this->createTestImage($this->imagesPath . '/tiny.png', 50 * 1024);   // 50KB

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('No oversized images', $result->description);
    }

    public function testRunReturnsWarningWhenOversizedImagesFound(): void
    {
        mkdir($this->imagesPath, 0777, true);

        // Create one large image (over 500KB)
        $this->createTestImage($this->imagesPath . '/large.jpg', 600 * 1024); // 600KB

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('1 image(s) larger than 500KB', $result->description);
        $this->assertStringContainsString('large.jpg', $result->description);
    }

    public function testRunReportsMultipleOversizedImages(): void
    {
        mkdir($this->imagesPath, 0777, true);

        // Create several large images
        $this->createTestImage($this->imagesPath . '/photo1.jpg', 600 * 1024);
        $this->createTestImage($this->imagesPath . '/photo2.png', 700 * 1024);
        $this->createTestImage($this->imagesPath . '/photo3.gif', 550 * 1024);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('3 image(s) larger than 500KB', $result->description);
    }

    public function testRunReportsCountOnlyForManyOversizedImages(): void
    {
        mkdir($this->imagesPath, 0777, true);

        // Create more than 10 large images
        for ($i = 1; $i <= 12; $i++) {
            $this->createTestImage($this->imagesPath . "/large{$i}.jpg", 600 * 1024);
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('12 images larger than 500KB', $result->description);
        $this->assertStringContainsString('Consider optimizing', $result->description);
        // Should NOT list individual files when count > 10
        $this->assertStringNotContainsString('large1.jpg', $result->description);
    }

    public function testRunIgnoresNonImageFiles(): void
    {
        mkdir($this->imagesPath, 0777, true);

        // Create large non-image file
        $this->createTestImage($this->imagesPath . '/document.pdf', 600 * 1024);
        $this->createTestImage($this->imagesPath . '/archive.zip', 700 * 1024);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('No oversized images', $result->description);
    }

    public function testRunHandlesSubdirectories(): void
    {
        mkdir($this->imagesPath . '/subfolder', 0777, true);

        // Create large image in subdirectory
        $this->createTestImage($this->imagesPath . '/subfolder/nested.jpg', 600 * 1024);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('1 image(s) larger than 500KB', $result->description);
    }

    public function testRunHandlesWebpFormat(): void
    {
        mkdir($this->imagesPath, 0777, true);

        // Create large WebP image
        $this->createTestImage($this->imagesPath . '/modern.webp', 600 * 1024);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('modern.webp', $result->description);
    }

    public function testRunIsCaseInsensitiveForExtensions(): void
    {
        mkdir($this->imagesPath, 0777, true);

        // Create large image with uppercase extension
        $this->createTestImage($this->imagesPath . '/photo.JPG', 600 * 1024);
        $this->createTestImage($this->imagesPath . '/image.PNG', 700 * 1024);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('2 image(s) larger than 500KB', $result->description);
    }

    public function testRunNeverReturnsCritical(): void
    {
        mkdir($this->imagesPath, 0777, true);

        // Even with many large images, should only return warning
        for ($i = 1; $i <= 20; $i++) {
            $this->createTestImage($this->imagesPath . "/huge{$i}.jpg", 2000 * 1024); // 2MB
        }

        $result = $this->check->run();

        $this->assertNotSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testRunHandlesEmptyDirectory(): void
    {
        mkdir($this->imagesPath, 0777, true);

        // Empty images directory
        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('No oversized images', $result->description);
    }

    public function testRunHandlesDeepNestedSubdirectories(): void
    {
        mkdir($this->imagesPath . '/level1/level2/level3', 0777, true);

        // Create large image in deeply nested directory
        $this->createTestImage($this->imagesPath . '/level1/level2/level3/deep.jpg', 600 * 1024);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('1 image(s)', $result->description);
    }

    public function testRunHandlesJpegExtension(): void
    {
        mkdir($this->imagesPath, 0777, true);

        // Test .jpeg extension (not just .jpg)
        $this->createTestImage($this->imagesPath . '/photo.jpeg', 600 * 1024);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('photo.jpeg', $result->description);
    }

    public function testRunHandlesGifFormat(): void
    {
        mkdir($this->imagesPath, 0777, true);

        $this->createTestImage($this->imagesPath . '/animation.gif', 600 * 1024);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('animation.gif', $result->description);
    }

    public function testRunShowsUpToFiveFilenamesForFewImages(): void
    {
        mkdir($this->imagesPath, 0777, true);

        // Create exactly 5 large images
        for ($i = 1; $i <= 5; $i++) {
            $this->createTestImage($this->imagesPath . "/photo{$i}.jpg", 600 * 1024);
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('5 image(s)', $result->description);
        // Should list filenames for small counts
        $this->assertStringContainsString('photo', $result->description);
    }

    public function testRunHandlesImagesAtExactThreshold(): void
    {
        mkdir($this->imagesPath, 0777, true);

        // Create image at exactly 500KB (should not be flagged)
        $this->createTestImage($this->imagesPath . '/exact.jpg', 500 * 1024);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunHandlesImagesJustOverThreshold(): void
    {
        mkdir($this->imagesPath, 0777, true);

        // Create image just over 500KB threshold
        $this->createTestImage($this->imagesPath . '/justover.jpg', 500 * 1024 + 1);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testRunHandlesMixedSizedImages(): void
    {
        mkdir($this->imagesPath, 0777, true);

        // Mix of small and large images
        $this->createTestImage($this->imagesPath . '/small1.jpg', 100 * 1024);
        $this->createTestImage($this->imagesPath . '/large1.jpg', 600 * 1024);
        $this->createTestImage($this->imagesPath . '/small2.png', 200 * 1024);
        $this->createTestImage($this->imagesPath . '/large2.png', 700 * 1024);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('2 image(s)', $result->description);
    }

    public function testRunReportsLimitReachedWithNoOversizedImages(): void
    {
        mkdir($this->imagesPath, 0777, true);

        // Create more than 1000 small images to trigger the scan limit
        for ($i = 1; $i <= 1005; $i++) {
            $this->createTestImage($this->imagesPath . "/small{$i}.jpg", 10 * 1024); // 10KB each
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('first 1000 files scanned', $result->description);
    }

    public function testRunReportsLimitReachedWithOversizedImages(): void
    {
        mkdir($this->imagesPath, 0777, true);

        // Create some large images first, then many small ones to reach the limit
        for ($i = 1; $i <= 3; $i++) {
            $this->createTestImage($this->imagesPath . "/large{$i}.jpg", 600 * 1024); // 600KB
        }

        // Create more files to exceed the 1000 limit
        for ($i = 1; $i <= 1000; $i++) {
            $this->createTestImage($this->imagesPath . "/small{$i}.png", 10 * 1024); // 10KB each
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('scan limited to 1000 files', $result->description);
        $this->assertStringContainsString('may have more oversized images', $result->description);
    }

    public function testRunHandlesSymlinks(): void
    {
        mkdir($this->imagesPath, 0777, true);
        mkdir($this->imagesPath . '/subdir', 0777, true);

        // Create a regular large image
        $this->createTestImage($this->imagesPath . '/large.jpg', 600 * 1024);

        // Create a symlink to a directory (which isFile() returns false for)
        $symlinkPath = $this->imagesPath . '/link';

        if (! @symlink($this->imagesPath . '/subdir', $symlinkPath)) {
            // If symlink creation fails (e.g., on Windows), skip the test
            $this->markTestSkipped('Symlinks not supported on this platform');
        }

        $result = $this->check->run();

        // Should still find the large image and report warning
        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('1 image(s)', $result->description);
    }

    /**
     * Create a test file with specified size
     */
    private function createTestImage(string $path, int $size): void
    {
        // Create directory if needed
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        // Create file with random content of specified size
        $fp = fopen($path, 'wb');
        if ($fp !== false) {
            // Write in chunks to avoid memory issues
            $remaining = $size;
            while ($remaining > 0) {
                $chunk = min($remaining, 8192);
                fwrite($fp, str_repeat("\0", $chunk));
                $remaining -= $chunk;
            }
            fclose($fp);
        }
    }

    /**
     * Recursively remove a directory
     */
    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . '/' . $file;

            // Handle symlinks first - they need unlink, not rmdir
            if (is_link($path)) {
                unlink($path);
            } elseif (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
