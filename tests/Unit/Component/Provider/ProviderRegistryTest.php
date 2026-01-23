<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Component\Provider;

use MySitesGuru\HealthChecker\Component\Administrator\Provider\ProviderMetadata;
use MySitesGuru\HealthChecker\Component\Administrator\Provider\ProviderRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProviderRegistry::class)]
class ProviderRegistryTest extends TestCase
{
    public function testCoreProviderIsRegisteredAutomatically(): void
    {
        $providerRegistry = new ProviderRegistry();

        $this->assertTrue($providerRegistry->has('core'));
        $this->assertNotNull($providerRegistry->get('core'));
    }

    public function testCoreProviderHasCorrectMetadata(): void
    {
        $providerRegistry = new ProviderRegistry();
        $core = $providerRegistry->get('core');

        $this->assertInstanceOf(ProviderMetadata::class, $core);
        $this->assertSame('core', $core->slug);
        $this->assertSame('Health Checker for Joomla', $core->name);
        $this->assertNotEmpty($core->description);
    }

    public function testCanRegisterNewProvider(): void
    {
        $providerRegistry = new ProviderRegistry();
        $providerMetadata = new ProviderMetadata('test_plugin', 'Test Plugin');

        $providerRegistry->register($providerMetadata);

        $this->assertTrue($providerRegistry->has('test_plugin'));
        $this->assertSame($providerMetadata, $providerRegistry->get('test_plugin'));
    }

    public function testRegisteringOverwritesExistingProvider(): void
    {
        $providerRegistry = new ProviderRegistry();

        $provider1 = new ProviderMetadata('plugin', 'First', 'First description');
        $provider2 = new ProviderMetadata('plugin', 'Second', 'Second description');

        $providerRegistry->register($provider1);
        $providerRegistry->register($provider2);

        $retrieved = $providerRegistry->get('plugin');
        $this->assertSame('Second', $retrieved->name);
        $this->assertSame('Second description', $retrieved->description);
    }

    public function testGetReturnsNullForNonExistentProvider(): void
    {
        $providerRegistry = new ProviderRegistry();
        $this->assertNull($providerRegistry->get('nonexistent'));
    }

    public function testHasReturnsFalseForNonExistentProvider(): void
    {
        $providerRegistry = new ProviderRegistry();
        $this->assertFalse($providerRegistry->has('nonexistent'));
    }

    public function testAllReturnsAllProviders(): void
    {
        $providerRegistry = new ProviderRegistry();

        $provider1 = new ProviderMetadata('plugin1', 'Plugin 1');
        $provider2 = new ProviderMetadata('plugin2', 'Plugin 2');

        $providerRegistry->register($provider1);
        $providerRegistry->register($provider2);

        $all = $providerRegistry->all();

        $this->assertCount(3, $all); // core + 2 new
        $this->assertArrayHasKey('core', $all);
        $this->assertArrayHasKey('plugin1', $all);
        $this->assertArrayHasKey('plugin2', $all);
    }

    public function testAllReturnsAssociativeArray(): void
    {
        $providerRegistry = new ProviderRegistry();
        $all = $providerRegistry->all();

        $this->assertIsArray($all);
        $this->assertArrayHasKey('core', $all);
        $this->assertInstanceOf(ProviderMetadata::class, $all['core']);
    }

    public function testGetThirdPartyExcludesCore(): void
    {
        $providerRegistry = new ProviderRegistry();

        $provider1 = new ProviderMetadata('akeeba_backup', 'Akeeba Backup');
        $provider2 = new ProviderMetadata('my_plugin', 'My Plugin');

        $providerRegistry->register($provider1);
        $providerRegistry->register($provider2);

        $thirdParty = $providerRegistry->getThirdParty();

        $this->assertCount(2, $thirdParty);
        $this->assertContains($provider1, $thirdParty);
        $this->assertContains($provider2, $thirdParty);

        // Ensure core is not in the result
        foreach ($thirdParty as $provider) {
            $this->assertNotSame('core', $provider->slug);
        }
    }

    public function testGetThirdPartyReturnsEmptyArrayWhenOnlyCoreExists(): void
    {
        $providerRegistry = new ProviderRegistry();
        $thirdParty = $providerRegistry->getThirdParty();

        $this->assertIsArray($thirdParty);
        $this->assertEmpty($thirdParty);
    }

    public function testCanRegisterMultipleProvidersWithDifferentMetadata(): void
    {
        $providerRegistry = new ProviderRegistry();

        $provider1 = new ProviderMetadata(
            slug: 'akeeba_backup',
            name: 'Akeeba Backup',
            description: 'Backup monitoring',
            url: 'https://www.akeeba.com',
            icon: 'fa-database',
            version: '1.0.0',
        );

        $provider2 = new ProviderMetadata(
            slug: 'admin_tools',
            name: 'Admin Tools',
            description: 'Security monitoring',
            url: 'https://www.akeeba.com/products/admin-tools.html',
            icon: 'fa-shield-halved',
            logoUrl: 'https://cdn.akeeba.com/logo.png',
            version: '2.0.0',
        );

        $providerRegistry->register($provider1);
        $providerRegistry->register($provider2);

        $retrieved1 = $providerRegistry->get('akeeba_backup');
        $retrieved2 = $providerRegistry->get('admin_tools');

        $this->assertSame('1.0.0', $retrieved1->version);
        $this->assertSame('2.0.0', $retrieved2->version);
        $this->assertNotNull($retrieved2->logoUrl);
        $this->assertNull($retrieved1->logoUrl);
    }
}
