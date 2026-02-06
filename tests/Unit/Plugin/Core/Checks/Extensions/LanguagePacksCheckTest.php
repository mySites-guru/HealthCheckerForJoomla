<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Extensions;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions\LanguagePacksCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LanguagePacksCheck::class)]
class LanguagePacksCheckTest extends TestCase
{
    private LanguagePacksCheck $languagePacksCheck;

    protected function setUp(): void
    {
        $this->languagePacksCheck = new LanguagePacksCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('extensions.language_packs', $this->languagePacksCheck->getSlug());
    }

    public function testGetCategoryReturnsExtensions(): void
    {
        $this->assertSame('extensions', $this->languagePacksCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->languagePacksCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->languagePacksCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->languagePacksCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsGoodWithLanguageCounts(): void
    {
        // This check makes two queries - site languages and admin languages
        $database = MockDatabaseFactory::createWithSequentialResults([2, 1]);
        $this->languagePacksCheck->setDatabase($database);

        $healthCheckResult = $this->languagePacksCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('2 site language(s)', $healthCheckResult->description);
        $this->assertStringContainsString('1 admin language(s)', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWithNoLanguages(): void
    {
        $database = MockDatabaseFactory::createWithSequentialResults([0, 0]);
        $this->languagePacksCheck->setDatabase($database);

        $healthCheckResult = $this->languagePacksCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('0 site language(s)', $healthCheckResult->description);
        $this->assertStringContainsString('0 admin language(s)', $healthCheckResult->description);
    }
}
