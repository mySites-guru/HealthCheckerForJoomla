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
        $this->assertStringContainsString('check_error', strtolower($healthCheckResult->description));
    }

    public function testRunWithPluginNotFoundReturnsWarning(): void
    {
        $database = MockDatabaseFactory::createWithObject(null);
        $this->mediaManagerThumbnailsCheck->setDatabase($database);

        $healthCheckResult = $this->mediaManagerThumbnailsCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString(
            'media_manager_thumbnails_warning',
            strtolower($healthCheckResult->description),
        );
    }

    public function testRunWithPluginDisabledReturnsWarning(): void
    {
        $plugin = (object) [
            'enabled' => 0,
            'params' => json_encode([
                'directories' => [
                    'directories0' => [
                        'directory' => 'images',
                        'thumbs' => 1,
                    ],
                ],
            ]),
        ];
        $database = MockDatabaseFactory::createWithObject($plugin);
        $this->mediaManagerThumbnailsCheck->setDatabase($database);

        $healthCheckResult = $this->mediaManagerThumbnailsCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString(
            'media_manager_thumbnails_warning',
            strtolower($healthCheckResult->description),
        );
    }

    public function testRunWithAllThumbnailsEnabledReturnsGood(): void
    {
        $plugin = (object) [
            'enabled' => 1,
            'params' => json_encode([
                'directories' => [
                    'directories0' => [
                        'directory' => 'images',
                        'thumbs' => 1,
                    ],
                    'directories1' => [
                        'directory' => 'files',
                        'thumbs' => 1,
                    ],
                ],
            ]),
        ];
        $database = MockDatabaseFactory::createWithObject($plugin);
        $this->mediaManagerThumbnailsCheck->setDatabase($database);

        $healthCheckResult = $this->mediaManagerThumbnailsCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('MEDIA_MANAGER_THUMBNAILS_GOOD', $healthCheckResult->description);
    }

    public function testRunWithSingleDirectoryThumbnailsEnabledReturnsGood(): void
    {
        $plugin = (object) [
            'enabled' => 1,
            'params' => json_encode([
                'directories' => [
                    'directories0' => [
                        'directory' => 'images',
                        'thumbs' => 1,
                    ],
                ],
            ]),
        ];
        $database = MockDatabaseFactory::createWithObject($plugin);
        $this->mediaManagerThumbnailsCheck->setDatabase($database);

        $healthCheckResult = $this->mediaManagerThumbnailsCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunWithAllThumbnailsDisabledReturnsWarning(): void
    {
        $plugin = (object) [
            'enabled' => 1,
            'params' => json_encode([
                'directories' => [
                    'directories0' => [
                        'directory' => 'images',
                        'thumbs' => 0,
                    ],
                    'directories1' => [
                        'directory' => 'files',
                        'thumbs' => 0,
                    ],
                ],
            ]),
        ];
        $database = MockDatabaseFactory::createWithObject($plugin);
        $this->mediaManagerThumbnailsCheck->setDatabase($database);

        $healthCheckResult = $this->mediaManagerThumbnailsCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString(
            'media_manager_thumbnails_warning',
            strtolower($healthCheckResult->description),
        );
    }

    public function testRunWithPartialThumbnailsDisabledReturnsWarning(): void
    {
        $plugin = (object) [
            'enabled' => 1,
            'params' => json_encode([
                'directories' => [
                    'directories0' => [
                        'directory' => 'images',
                        'thumbs' => 1,
                    ],
                    'directories1' => [
                        'directory' => 'files',
                        'thumbs' => 0,
                    ],
                ],
            ]),
        ];
        $database = MockDatabaseFactory::createWithObject($plugin);
        $this->mediaManagerThumbnailsCheck->setDatabase($database);

        $healthCheckResult = $this->mediaManagerThumbnailsCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString(
            'media_manager_thumbnails_warning',
            strtolower($healthCheckResult->description),
        );
    }

    public function testRunWithEmptyDirectoriesReturnsWarning(): void
    {
        $plugin = (object) [
            'enabled' => 1,
            'params' => json_encode([
                'directories' => [],
            ]),
        ];
        $database = MockDatabaseFactory::createWithObject($plugin);
        $this->mediaManagerThumbnailsCheck->setDatabase($database);

        $healthCheckResult = $this->mediaManagerThumbnailsCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunWithMissingDirectoriesKeyReturnsWarning(): void
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
        $this->assertStringContainsString(
            'media_manager_thumbnails_warning',
            strtolower($healthCheckResult->description),
        );
    }

    public function testCheckNeverReturnsCritical(): void
    {
        $healthCheckResult = $this->mediaManagerThumbnailsCheck->run();

        $this->assertNotSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
    }
}
