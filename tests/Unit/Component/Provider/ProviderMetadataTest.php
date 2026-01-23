<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Component\Provider;

use MySitesGuru\HealthChecker\Component\Administrator\Provider\ProviderMetadata;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProviderMetadata::class)]
class ProviderMetadataTest extends TestCase
{
    public function testConstructorWithRequiredParametersOnly(): void
    {
        $providerMetadata = new ProviderMetadata(slug: 'core', name: 'Health Checker for Joomla');

        $this->assertSame('core', $providerMetadata->slug);
        $this->assertSame('Health Checker for Joomla', $providerMetadata->name);
        $this->assertSame('', $providerMetadata->description);
        $this->assertNull($providerMetadata->url);
        $this->assertNull($providerMetadata->icon);
        $this->assertNull($providerMetadata->logoUrl);
        $this->assertNull($providerMetadata->version);
    }

    public function testConstructorWithAllParameters(): void
    {
        $providerMetadata = new ProviderMetadata(
            slug: 'akeeba_backup',
            name: 'Akeeba Backup',
            description: 'Backup monitoring and verification',
            url: 'https://www.akeeba.com',
            icon: 'fa-database',
            logoUrl: 'https://cdn.akeeba.com/logo.png',
            version: '1.2.3',
        );

        $this->assertSame('akeeba_backup', $providerMetadata->slug);
        $this->assertSame('Akeeba Backup', $providerMetadata->name);
        $this->assertSame('Backup monitoring and verification', $providerMetadata->description);
        $this->assertSame('https://www.akeeba.com', $providerMetadata->url);
        $this->assertSame('fa-database', $providerMetadata->icon);
        $this->assertSame('https://cdn.akeeba.com/logo.png', $providerMetadata->logoUrl);
        $this->assertSame('1.2.3', $providerMetadata->version);
    }

    public function testPropertiesAreReadonly(): void
    {
        $providerMetadata = new ProviderMetadata(slug: 'test', name: 'Test Provider');

        $this->expectException(\Error::class);
        // @phpstan-ignore-next-line - Testing readonly property
        $providerMetadata->slug = 'modified';
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $providerMetadata = new ProviderMetadata(slug: 'test', name: 'Test');

        $array = $providerMetadata->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('slug', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('description', $array);
        $this->assertArrayHasKey('url', $array);
        $this->assertArrayHasKey('icon', $array);
        $this->assertArrayHasKey('logoUrl', $array);
        $this->assertArrayHasKey('version', $array);
    }

    public function testToArrayContainsCorrectValues(): void
    {
        $providerMetadata = new ProviderMetadata(
            slug: 'my_plugin',
            name: 'My Plugin',
            description: 'Custom health checks',
            url: 'https://example.com',
            icon: 'fa-heart',
            logoUrl: 'https://example.com/logo.svg',
            version: '2.0.0',
        );

        $array = $providerMetadata->toArray();

        $this->assertSame('my_plugin', $array['slug']);
        $this->assertSame('My Plugin', $array['name']);
        $this->assertSame('Custom health checks', $array['description']);
        $this->assertSame('https://example.com', $array['url']);
        $this->assertSame('fa-heart', $array['icon']);
        $this->assertSame('https://example.com/logo.svg', $array['logoUrl']);
        $this->assertSame('2.0.0', $array['version']);
    }

    public function testToArrayIncludesNullValues(): void
    {
        $providerMetadata = new ProviderMetadata(slug: 'minimal', name: 'Minimal Provider');

        $array = $providerMetadata->toArray();

        $this->assertNull($array['url']);
        $this->assertNull($array['icon']);
        $this->assertNull($array['logoUrl']);
        $this->assertNull($array['version']);
        $this->assertSame('', $array['description']);
    }

    public function testProviderIsImmutable(): void
    {
        $providerMetadata = new ProviderMetadata(slug: 'test', name: 'Test', version: '1.0.0');

        $array1 = $providerMetadata->toArray();
        $array2 = $providerMetadata->toArray();

        $this->assertEquals($array1, $array2);
        $this->assertSame($providerMetadata->version, '1.0.0');
    }

    public function testCanSerializeToJson(): void
    {
        $providerMetadata = new ProviderMetadata(
            slug: 'json_test',
            name: 'JSON Test',
            description: 'Testing JSON serialization',
            url: 'https://test.com',
            version: '3.1.4',
        );

        $json = json_encode($providerMetadata->toArray());
        $this->assertIsString($json);

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertSame('json_test', $decoded['slug']);
        $this->assertSame('JSON Test', $decoded['name']);
        $this->assertSame('3.1.4', $decoded['version']);
    }

    public function testCoreProviderExample(): void
    {
        $providerMetadata = new ProviderMetadata(
            slug: 'core',
            name: 'Health Checker for Joomla',
            description: 'Built-in health checks',
            url: 'https://github.com/mySites-guru/HealthCheckerForJoomla',
            icon: 'fa-heartbeat',
            version: '1.0.0',
        );

        $this->assertSame('core', $providerMetadata->slug);
        $this->assertSame('Built-in health checks', $providerMetadata->description);
    }

    public function testThirdPartyProviderExample(): void
    {
        $providerMetadata = new ProviderMetadata(
            slug: 'akeeba_admin_tools',
            name: 'Akeeba Admin Tools',
            description: 'Security monitoring and protection',
            url: 'https://www.akeeba.com/products/admin-tools.html',
            icon: 'fa-shield-halved',
            logoUrl: 'https://cdn.akeeba.com/images/admintools.svg',
            version: '7.5.2',
        );

        $array = $providerMetadata->toArray();

        $this->assertSame('akeeba_admin_tools', $array['slug']);
        $this->assertSame('Security monitoring and protection', $array['description']);
        $this->assertNotNull($array['logoUrl']);
    }
}
