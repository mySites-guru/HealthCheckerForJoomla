<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Seo;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Seo\AltTextCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AltTextCheck::class)]
class AltTextCheckTest extends TestCase
{
    private AltTextCheck $altTextCheck;

    protected function setUp(): void
    {
        $this->altTextCheck = new AltTextCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('seo.alt_text', $this->altTextCheck->getSlug());
    }

    public function testGetCategoryReturnsSeo(): void
    {
        $this->assertSame('seo', $this->altTextCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->altTextCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->altTextCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->altTextCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunWithNoArticlesReturnsGood(): void
    {
        $database = MockDatabaseFactory::createWithObjectList([]);
        $this->altTextCheck->setDatabase($database);

        $healthCheckResult = $this->altTextCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('No images with missing alt text', $healthCheckResult->description);
    }

    public function testRunWithArticlesWithNoImagesReturnsGood(): void
    {
        $articles = [
            (object) [
                'id' => 1,
                'title' => 'Test Article',
                'introtext' => '<p>No images here</p>',
                'fulltext' => '',
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($articles);
        $this->altTextCheck->setDatabase($database);

        $healthCheckResult = $this->altTextCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunWithAllImagesHavingAltTextReturnsGood(): void
    {
        $articles = [
            (object) [
                'id' => 1,
                'title' => 'Test Article',
                'introtext' => '<p><img src="image1.jpg" alt="Description"></p>',
                'fulltext' => '<img src="image2.jpg" alt="Another description">',
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($articles);
        $this->altTextCheck->setDatabase($database);

        $healthCheckResult = $this->altTextCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunWithFewMissingAltTextsReturnsWarning(): void
    {
        $articles = [
            (object) [
                'id' => 1,
                'title' => 'Test Article',
                'introtext' => '<p><img src="image1.jpg"></p>',
                'fulltext' => '',
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($articles);
        $this->altTextCheck->setDatabase($database);

        $healthCheckResult = $this->altTextCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('may be missing', $healthCheckResult->description);
    }

    public function testRunWithManyMissingAltTextsReturnsWarning(): void
    {
        // Create articles with 15 images without alt text
        $articles = [];
        for ($i = 1; $i <= 15; $i++) {
            $articles[] = (object) [
                'id' => $i,
                'title' => 'Article ' . $i,
                'introtext' => '<img src="image.jpg">',
                'fulltext' => '',
            ];
        }

        $database = MockDatabaseFactory::createWithObjectList($articles);
        $this->altTextCheck->setDatabase($database);

        $healthCheckResult = $this->altTextCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('approximately', $healthCheckResult->description);
        $this->assertStringContainsString('accessibility', $healthCheckResult->description);
    }

    public function testRunWithEmptyAltTextReturnsWarning(): void
    {
        $articles = [
            (object) [
                'id' => 1,
                'title' => 'Test Article',
                'introtext' => '<img src="image.jpg" alt="">',
                'fulltext' => '',
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($articles);
        $this->altTextCheck->setDatabase($database);

        $healthCheckResult = $this->altTextCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunWithWhitespaceOnlyAltTextReturnsWarning(): void
    {
        $articles = [
            (object) [
                'id' => 1,
                'title' => 'Test Article',
                'introtext' => '<img src="image.jpg" alt="   ">',
                'fulltext' => '',
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($articles);
        $this->altTextCheck->setDatabase($database);

        $healthCheckResult = $this->altTextCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }
}
