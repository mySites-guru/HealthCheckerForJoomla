<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Performance;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Performance\MediaManagerThumbnailsCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MediaManagerThumbnailsCheck::class)]
class MediaManagerThumbnailsCheckTest extends TestCase
{
    private MediaManagerThumbnailsCheck $mediaManagerThumbnailsCheck;

    protected function setUp(): void
    {
        $this->mediaManagerThumbnailsCheck = new MediaManagerThumbnailsCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('performance.media_manager_thumbnails', $this->mediaManagerThumbnailsCheck->getSlug());
    }

    public function testGetCategoryReturnsPerformance(): void
    {
        $this->assertSame('performance', $this->mediaManagerThumbnailsCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->mediaManagerThumbnailsCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->mediaManagerThumbnailsCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->mediaManagerThumbnailsCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('database', strtolower($healthCheckResult->description));
    }

    public function testRunWithPluginNotFoundReturnsWarning(): void
    {
        $database = MockDatabaseFactory::createWithObject(null);
        $this->mediaManagerThumbnailsCheck->setDatabase($database);

        $healthCheckResult = $this->mediaManagerThumbnailsCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('not found', strtolower($healthCheckResult->description));
    }

    public function testRunWithPluginDisabledReturnsWarning(): void
    {
        $plugin = (object) [
            'enabled' => 0,
            'params' => json_encode([
                'thumbnail_size' => 200,
            ]),
        ];
        $database = MockDatabaseFactory::createWithObject($plugin);
        $this->mediaManagerThumbnailsCheck->setDatabase($database);

        $healthCheckResult = $this->mediaManagerThumbnailsCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('disabled', strtolower($healthCheckResult->description));
    }

    public function testRunWithThumbnailsEnabledReturnsGood(): void
    {
        $plugin = (object) [
            'enabled' => 1,
            'params' => json_encode([
                'thumbnail_size' => 200,
            ]),
        ];
        $database = MockDatabaseFactory::createWithObject($plugin);
        $this->mediaManagerThumbnailsCheck->setDatabase($database);

        $healthCheckResult = $this->mediaManagerThumbnailsCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('200', $healthCheckResult->description);
    }

    public function testRunWithThumbnailsDisabledReturnsWarning(): void
    {
        $plugin = (object) [
            'enabled' => 1,
            'params' => json_encode([
                'thumbnail_size' => 0,
            ]),
        ];
        $database = MockDatabaseFactory::createWithObject($plugin);
        $this->mediaManagerThumbnailsCheck->setDatabase($database);

        $healthCheckResult = $this->mediaManagerThumbnailsCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('disabled', strtolower($healthCheckResult->description));
    }

    public function testRunWithInvalidParamsReturnsWarning(): void
    {
        $plugin = (object) [
            'enabled' => 1,
            'params' => 'invalid-json{',
        ];
        $database = MockDatabaseFactory::createWithObject($plugin);
        $this->mediaManagerThumbnailsCheck->setDatabase($database);

        $healthCheckResult = $this->mediaManagerThumbnailsCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('read', strtolower($healthCheckResult->description));
    }

    public function testRunWithNegativeThumbnailSizeReturnsWarning(): void
    {
        $plugin = (object) [
            'enabled' => 1,
            'params' => json_encode([
                'thumbnail_size' => -100,
            ]),
        ];
        $database = MockDatabaseFactory::createWithObject($plugin);
        $this->mediaManagerThumbnailsCheck->setDatabase($database);

        $healthCheckResult = $this->mediaManagerThumbnailsCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunWithMissingThumbnailSizeParamReturnsWarning(): void
    {
        $plugin = (object) [
            'enabled' => 1,
            'params' => json_encode([
                'other_param' => 'value',
            ]),
        ];
        $database = MockDatabaseFactory::createWithObject($plugin);
        $this->mediaManagerThumbnailsCheck->setDatabase($database);

        $healthCheckResult = $this->mediaManagerThumbnailsCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testCheckNeverReturnsCritical(): void
    {
        $healthCheckResult = $this->mediaManagerThumbnailsCheck->run();

        $this->assertNotSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
    }
}
