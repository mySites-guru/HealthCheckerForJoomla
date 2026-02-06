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
use MySitesGuru\HealthChecker\Component\Administrator\Service\DescriptionSanitizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HealthCheckResult::class)]
#[CoversClass(DescriptionSanitizer::class)]
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

        $healthCheckResult = HealthCheckResult::fromArray($data);

        $this->assertInstanceOf(HealthCheckResult::class, $healthCheckResult);
        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertSame('Test Title', $healthCheckResult->title);
        $this->assertSame('Test Description', $healthCheckResult->description);
        $this->assertSame('test.from_array', $healthCheckResult->slug);
        $this->assertSame('security', $healthCheckResult->category);
        $this->assertSame('test_provider', $healthCheckResult->provider);
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

        $healthCheckResult = HealthCheckResult::fromArray($data);

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
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

        $healthCheckResult = HealthCheckResult::fromArray($data);

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
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

        $healthCheckResult = HealthCheckResult::fromArray($data);

        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testFromArrayRoundtripsCorrectly(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Warning,
            title: 'Roundtrip Test',
            description: 'Testing roundtrip',
            slug: 'test.roundtrip',
            category: 'performance',
            provider: 'custom_plugin',
        );

        $array = $healthCheckResult->toArray();
        $reconstructed = HealthCheckResult::fromArray($array);

        $this->assertSame($healthCheckResult->healthStatus, $reconstructed->healthStatus);
        $this->assertSame($healthCheckResult->title, $reconstructed->title);
        $this->assertSame($healthCheckResult->description, $reconstructed->description);
        $this->assertSame($healthCheckResult->slug, $reconstructed->slug);
        $this->assertSame($healthCheckResult->category, $reconstructed->category);
        $this->assertSame($healthCheckResult->provider, $reconstructed->provider);
    }

    public function testFromArrayWithJsonEncodedData(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Critical,
            title: 'JSON Test',
            description: 'Testing JSON round-trip',
            slug: 'test.json',
            category: 'security',
            provider: 'core',
        );

        // Simulate JSON serialization/deserialization (as happens in cache)
        $json = json_encode($healthCheckResult->toArray());
        $decoded = json_decode($json, true);
        $reconstructed = HealthCheckResult::fromArray($decoded);

        $this->assertSame($healthCheckResult->healthStatus, $reconstructed->healthStatus);
        $this->assertSame($healthCheckResult->title, $reconstructed->title);
        $this->assertSame($healthCheckResult->description, $reconstructed->description);
        $this->assertSame($healthCheckResult->slug, $reconstructed->slug);
        $this->assertSame($healthCheckResult->category, $reconstructed->category);
        $this->assertSame($healthCheckResult->provider, $reconstructed->provider);
    }

    public function testConstructorWithDocsUrl(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Good,
            title: 'Test Check',
            description: 'Test description',
            slug: 'test.check',
            category: 'system',
            provider: 'core',
            docsUrl: 'https://example.com/docs',
        );

        $this->assertSame('https://example.com/docs', $healthCheckResult->docsUrl);
        $this->assertNull($healthCheckResult->actionUrl);
    }

    public function testConstructorWithActionUrl(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Good,
            title: 'Test Check',
            description: 'Test description',
            slug: 'test.check',
            category: 'system',
            provider: 'core',
            docsUrl: null,
            actionUrl: '/administrator/index.php?option=com_test',
        );

        $this->assertNull($healthCheckResult->docsUrl);
        $this->assertSame('/administrator/index.php?option=com_test', $healthCheckResult->actionUrl);
    }

    public function testConstructorWithBothUrls(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Good,
            title: 'Test Check',
            description: 'Test description',
            slug: 'test.check',
            category: 'system',
            provider: 'core',
            docsUrl: 'https://example.com/docs',
            actionUrl: '/administrator/index.php?option=com_test',
        );

        $this->assertSame('https://example.com/docs', $healthCheckResult->docsUrl);
        $this->assertSame('/administrator/index.php?option=com_test', $healthCheckResult->actionUrl);
    }

    public function testUrlsDefaultToNull(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Good,
            title: 'Test',
            description: 'Test',
            slug: 'test.check',
            category: 'system',
        );

        $this->assertNull($healthCheckResult->docsUrl);
        $this->assertNull($healthCheckResult->actionUrl);
    }

    public function testToArrayIncludesUrls(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Good,
            title: 'Test',
            description: 'Test',
            slug: 'test.check',
            category: 'system',
            provider: 'core',
            docsUrl: 'https://example.com/docs',
            actionUrl: '/admin',
        );

        $array = $healthCheckResult->toArray();

        $this->assertArrayHasKey('docsUrl', $array);
        $this->assertArrayHasKey('actionUrl', $array);
        $this->assertSame('https://example.com/docs', $array['docsUrl']);
        $this->assertSame('/admin', $array['actionUrl']);
    }

    public function testToArrayWithNullUrls(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Good,
            title: 'Test',
            description: 'Test',
            slug: 'test.check',
            category: 'system',
        );

        $array = $healthCheckResult->toArray();

        $this->assertArrayHasKey('docsUrl', $array);
        $this->assertArrayHasKey('actionUrl', $array);
        $this->assertNull($array['docsUrl']);
        $this->assertNull($array['actionUrl']);
    }

    public function testFromArrayWithUrls(): void
    {
        $data = [
            'status' => 'good',
            'title' => 'Test',
            'description' => 'Test',
            'slug' => 'test.urls',
            'category' => 'system',
            'provider' => 'core',
            'docsUrl' => 'https://example.com/docs',
            'actionUrl' => '/admin/page',
        ];

        $healthCheckResult = HealthCheckResult::fromArray($data);

        $this->assertSame('https://example.com/docs', $healthCheckResult->docsUrl);
        $this->assertSame('/admin/page', $healthCheckResult->actionUrl);
    }

    public function testFromArrayDefaultsUrlsToNull(): void
    {
        $data = [
            'status' => 'good',
            'title' => 'Test',
            'description' => 'Test',
            'slug' => 'test.no_urls',
            'category' => 'system',
        ];

        $healthCheckResult = HealthCheckResult::fromArray($data);

        $this->assertNull($healthCheckResult->docsUrl);
        $this->assertNull($healthCheckResult->actionUrl);
    }

    public function testFromArrayRoundtripWithUrls(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Warning,
            title: 'Roundtrip URL Test',
            description: 'Testing URL roundtrip',
            slug: 'test.url_roundtrip',
            category: 'performance',
            provider: 'custom',
            docsUrl: 'https://docs.example.com/check',
            actionUrl: '/administrator/index.php?option=com_custom',
        );

        $array = $healthCheckResult->toArray();
        $reconstructed = HealthCheckResult::fromArray($array);

        $this->assertSame($healthCheckResult->docsUrl, $reconstructed->docsUrl);
        $this->assertSame($healthCheckResult->actionUrl, $reconstructed->actionUrl);
    }

    public function testToArrayStripsHtmlFromTitle(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Good,
            title: '<script>alert(1)</script>PHP Version Check',
            description: 'Test description',
            slug: 'test.check',
            category: 'system',
        );

        $array = $healthCheckResult->toArray();

        $this->assertSame('PHP Version Check', $array['title']);
        $this->assertStringNotContainsString('<script>', $array['title']);
    }

    public function testToArrayStripsAllHtmlTagsFromTitle(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Good,
            title: '<strong>Bold</strong> and <em>italic</em> title',
            description: 'Test description',
            slug: 'test.check',
            category: 'system',
        );

        $array = $healthCheckResult->toArray();

        $this->assertSame('Bold and italic title', $array['title']);
        $this->assertStringNotContainsString('<strong>', $array['title']);
        $this->assertStringNotContainsString('<em>', $array['title']);
    }

    public function testToArraySanitizesDescriptionAllowingSafeTags(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Good,
            title: 'Test',
            description: '<p>This is <strong>important</strong></p>',
            slug: 'test.check',
            category: 'system',
        );

        $array = $healthCheckResult->toArray();

        $this->assertStringContainsString('<p>', $array['description']);
        $this->assertStringContainsString('<strong>', $array['description']);
    }

    public function testToArraySanitizesDescriptionStrippingDangerousTags(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Good,
            title: 'Test',
            description: '<p>Safe</p><script>alert(1)</script>',
            slug: 'test.check',
            category: 'system',
        );

        $array = $healthCheckResult->toArray();

        $this->assertStringContainsString('<p>', $array['description']);
        $this->assertStringNotContainsString('<script>', $array['description']);
        $this->assertStringNotContainsString('alert', $array['description']);
    }

    public function testToArraySanitizesDescriptionStrippingAnchorTags(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Good,
            title: 'Test',
            description: 'Click <a href="https://evil.com">here</a> for more info.',
            slug: 'test.check',
            category: 'system',
        );

        $array = $healthCheckResult->toArray();

        $this->assertStringNotContainsString('<a ', $array['description']);
        $this->assertStringNotContainsString('href=', $array['description']);
        $this->assertStringContainsString('here', $array['description']);
    }
}
