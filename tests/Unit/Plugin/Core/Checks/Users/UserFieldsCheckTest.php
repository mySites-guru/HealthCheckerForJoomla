<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Users;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Users\UserFieldsCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserFieldsCheck::class)]
class UserFieldsCheckTest extends TestCase
{
    private UserFieldsCheck $userFieldsCheck;

    protected function setUp(): void
    {
        $this->userFieldsCheck = new UserFieldsCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('users.user_fields', $this->userFieldsCheck->getSlug());
    }

    public function testGetCategoryReturnsUsers(): void
    {
        $this->assertSame('users', $this->userFieldsCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->userFieldsCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->userFieldsCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->userFieldsCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunWithNoCustomFieldsReturnsGood(): void
    {
        // First query: total fields = 0
        // Second query: published fields = 0
        $database = MockDatabaseFactory::createWithSequentialResults([0, 0]);
        $this->userFieldsCheck->setDatabase($database);

        $healthCheckResult = $this->userFieldsCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('No custom user fields', $healthCheckResult->description);
    }

    public function testRunWithAllFieldsPublishedReturnsGood(): void
    {
        // First query: total fields = 5
        // Second query: published fields = 5
        $database = MockDatabaseFactory::createWithSequentialResults([5, 5]);
        $this->userFieldsCheck->setDatabase($database);

        $healthCheckResult = $this->userFieldsCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('5 custom user field', $healthCheckResult->description);
        $this->assertStringContainsString('published', $healthCheckResult->description);
    }

    public function testRunWithSomeUnpublishedFieldsReturnsGood(): void
    {
        // First query: total fields = 5
        // Second query: published fields = 3
        $database = MockDatabaseFactory::createWithSequentialResults([5, 3]);
        $this->userFieldsCheck->setDatabase($database);

        $healthCheckResult = $this->userFieldsCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('5 custom user field', $healthCheckResult->description);
        $this->assertStringContainsString('3 published', $healthCheckResult->description);
        $this->assertStringContainsString('2 unpublished', $healthCheckResult->description);
    }

    public function testRunWithAllFieldsUnpublishedReturnsGood(): void
    {
        // First query: total fields = 3
        // Second query: published fields = 0
        $database = MockDatabaseFactory::createWithSequentialResults([3, 0]);
        $this->userFieldsCheck->setDatabase($database);

        $healthCheckResult = $this->userFieldsCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('3 custom user field', $healthCheckResult->description);
        $this->assertStringContainsString('0 published', $healthCheckResult->description);
        $this->assertStringContainsString('3 unpublished', $healthCheckResult->description);
    }

    public function testRunWithSingleFieldReturnsGood(): void
    {
        // First query: total fields = 1
        // Second query: published fields = 1
        $database = MockDatabaseFactory::createWithSequentialResults([1, 1]);
        $this->userFieldsCheck->setDatabase($database);

        $healthCheckResult = $this->userFieldsCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('1 custom user field', $healthCheckResult->description);
    }
}
