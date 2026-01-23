<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Component\Check;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HealthCheckResult::class)]
class HealthCheckResultTest extends TestCase
{
    public function testConstructorSetsAllProperties(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Critical,
            title: 'PHP Version Check',
            description: 'PHP version is too old',
            slug: 'core.php_version',
            category: 'system',
            provider: 'core',
        );

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertSame('PHP Version Check', $healthCheckResult->title);
        $this->assertSame('PHP version is too old', $healthCheckResult->description);
        $this->assertSame('core.php_version', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testProviderDefaultsToCore(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Good,
            title: 'Test Check',
            description: 'Test description',
            slug: 'test.check',
            category: 'system',
        );

        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testPropertiesAreReadonly(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Good,
            title: 'Test',
            description: 'Test',
            slug: 'test.check',
            category: 'system',
        );

        $this->expectException(\Error::class);
        // @phpstan-ignore-next-line - Testing readonly property
        $healthCheckResult->title = 'Modified';
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Warning,
            title: 'Memory Limit Check',
            description: 'Memory limit is low',
            slug: 'core.memory_limit',
            category: 'system',
            provider: 'core',
        );

        $array = $healthCheckResult->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('title', $array);
        $this->assertArrayHasKey('description', $array);
        $this->assertArrayHasKey('slug', $array);
        $this->assertArrayHasKey('category', $array);
        $this->assertArrayHasKey('provider', $array);
    }

    public function testToArrayContainsCorrectValues(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Critical,
            title: 'Database Connection',
            description: 'Cannot connect to database',
            slug: 'core.database_connection',
            category: 'database',
            provider: 'core',
        );

        $array = $healthCheckResult->toArray();

        $this->assertSame('critical', $array['status']);
        $this->assertSame('Database Connection', $array['title']);
        $this->assertSame('Cannot connect to database', $array['description']);
        $this->assertSame('core.database_connection', $array['slug']);
        $this->assertSame('database', $array['category']);
        $this->assertSame('core', $array['provider']);
    }

    public function testToArrayConvertsEnumToString(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Good,
            title: 'Test',
            description: 'Test',
            slug: 'test.check',
            category: 'system',
        );

        $array = $healthCheckResult->toArray();

        $this->assertIsString($array['status']);
        $this->assertSame('good', $array['status']);
    }

    public function testWithThirdPartyProvider(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Warning,
            title: 'Backup Check',
            description: 'Last backup is old',
            slug: 'akeeba_backup.last_backup',
            category: 'system',
            provider: 'akeeba_backup',
        );

        $this->assertSame('akeeba_backup', $healthCheckResult->provider);

        $array = $healthCheckResult->toArray();
        $this->assertSame('akeeba_backup', $array['provider']);
    }

    public function testResultIsImmutable(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Good,
            title: 'Test',
            description: 'Test',
            slug: 'test.check',
            category: 'system',
        );

        $array1 = $healthCheckResult->toArray();
        $array2 = $healthCheckResult->toArray();

        $this->assertEquals($array1, $array2);
        $this->assertSame($healthCheckResult->title, 'Test');
    }

    public function testCanSerializeToJson(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Warning,
            title: 'SSL Check',
            description: 'SSL certificate expires soon',
            slug: 'core.ssl_check',
            category: 'security',
            provider: 'core',
        );

        $json = json_encode($healthCheckResult->toArray());
        $this->assertIsString($json);

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertSame('warning', $decoded['status']);
        $this->assertSame('SSL Check', $decoded['title']);
    }

    public function testFromArrayReconstructsResultCorrectly(): void
    {
        $data = [
            'status' => 'critical',
            'title' => 'Test Title',
            'description' => 'Test Description',
            'slug' => 'test.from_array',
            'category' => 'security',
            'provider' => 'test_provider',
        ];

        $result = HealthCheckResult::fromArray($data);

        $this->assertInstanceOf(HealthCheckResult::class, $result);
        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertSame('Test Title', $result->title);
        $this->assertSame('Test Description', $result->description);
        $this->assertSame('test.from_array', $result->slug);
        $this->assertSame('security', $result->category);
        $this->assertSame('test_provider', $result->provider);
    }

    public function testFromArrayWithWarningStatus(): void
    {
        $data = [
            'status' => 'warning',
            'title' => 'Warning Test',
            'description' => 'Warning Description',
            'slug' => 'test.warning',
            'category' => 'system',
            'provider' => 'core',
        ];

        $result = HealthCheckResult::fromArray($data);

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testFromArrayWithGoodStatus(): void
    {
        $data = [
            'status' => 'good',
            'title' => 'Good Test',
            'description' => 'Good Description',
            'slug' => 'test.good',
            'category' => 'database',
            'provider' => 'core',
        ];

        $result = HealthCheckResult::fromArray($data);

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testFromArrayDefaultsProviderToCore(): void
    {
        $data = [
            'status' => 'good',
            'title' => 'Test',
            'description' => 'Test',
            'slug' => 'test.default_provider',
            'category' => 'system',
            // provider is missing
        ];

        $result = HealthCheckResult::fromArray($data);

        $this->assertSame('core', $result->provider);
    }

    public function testFromArrayRoundtripsCorrectly(): void
    {
        $original = new HealthCheckResult(
            healthStatus: HealthStatus::Warning,
            title: 'Roundtrip Test',
            description: 'Testing roundtrip',
            slug: 'test.roundtrip',
            category: 'performance',
            provider: 'custom_plugin',
        );

        $array = $original->toArray();
        $reconstructed = HealthCheckResult::fromArray($array);

        $this->assertSame($original->healthStatus, $reconstructed->healthStatus);
        $this->assertSame($original->title, $reconstructed->title);
        $this->assertSame($original->description, $reconstructed->description);
        $this->assertSame($original->slug, $reconstructed->slug);
        $this->assertSame($original->category, $reconstructed->category);
        $this->assertSame($original->provider, $reconstructed->provider);
    }

    public function testFromArrayWithJsonEncodedData(): void
    {
        $original = new HealthCheckResult(
            healthStatus: HealthStatus::Critical,
            title: 'JSON Test',
            description: 'Testing JSON round-trip',
            slug: 'test.json',
            category: 'security',
            provider: 'core',
        );

        // Simulate JSON serialization/deserialization (as happens in cache)
        $json = json_encode($original->toArray());
        $decoded = json_decode($json, true);
        $reconstructed = HealthCheckResult::fromArray($decoded);

        $this->assertSame($original->healthStatus, $reconstructed->healthStatus);
        $this->assertSame($original->title, $reconstructed->title);
        $this->assertSame($original->description, $reconstructed->description);
        $this->assertSame($original->slug, $reconstructed->slug);
        $this->assertSame($original->category, $reconstructed->category);
        $this->assertSame($original->provider, $reconstructed->provider);
    }
}
