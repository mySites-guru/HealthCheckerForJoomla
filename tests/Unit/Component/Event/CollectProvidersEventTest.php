<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Component\Event;

use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectProvidersEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Provider\ProviderMetadata;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CollectProvidersEvent::class)]
class CollectProvidersEventTest extends TestCase
{
    public function testEventHasCorrectName(): void
    {
        $collectProvidersEvent = new CollectProvidersEvent();
        $this->assertSame('onHealthCheckerCollectProviders', $collectProvidersEvent->getName());
    }

    public function testGetProvidersReturnsEmptyArrayInitially(): void
    {
        $collectProvidersEvent = new CollectProvidersEvent();
        $this->assertIsArray($collectProvidersEvent->getProviders());
        $this->assertEmpty($collectProvidersEvent->getProviders());
    }

    public function testCanAddProviderMetadata(): void
    {
        $collectProvidersEvent = new CollectProvidersEvent();
        $providerMetadata = new ProviderMetadata('test', 'Test Provider');

        $collectProvidersEvent->addResult($providerMetadata);
        $providers = $collectProvidersEvent->getProviders();

        $this->assertCount(1, $providers);
        $this->assertContains($providerMetadata, $providers);
    }

    public function testCanAddMultipleProviders(): void
    {
        $collectProvidersEvent = new CollectProvidersEvent();

        $provider1 = new ProviderMetadata('provider1', 'Provider 1');
        $provider2 = new ProviderMetadata('provider2', 'Provider 2');
        $provider3 = new ProviderMetadata('provider3', 'Provider 3');

        $collectProvidersEvent->addResult($provider1);
        $collectProvidersEvent->addResult($provider2);
        $collectProvidersEvent->addResult($provider3);

        $providers = $collectProvidersEvent->getProviders();

        $this->assertCount(3, $providers);
        $this->assertContains($provider1, $providers);
        $this->assertContains($provider2, $providers);
        $this->assertContains($provider3, $providers);
    }

    public function testThrowsExceptionWhenAddingInvalidType(): void
    {
        $collectProvidersEvent = new CollectProvidersEvent();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('only accepts ProviderMetadata instances');

        $collectProvidersEvent->addResult('invalid data');
    }

    public function testThrowsExceptionWhenAddingArray(): void
    {
        $collectProvidersEvent = new CollectProvidersEvent();

        $this->expectException(\InvalidArgumentException::class);
        $collectProvidersEvent->addResult([
            'slug' => 'test',
            'name' => 'Test',
        ]);
    }

    public function testThrowsExceptionWhenAddingObject(): void
    {
        $collectProvidersEvent = new CollectProvidersEvent();

        $this->expectException(\InvalidArgumentException::class);
        $collectProvidersEvent->addResult(new \stdClass());
    }

    public function testPreservesProviderOrderWhenAdding(): void
    {
        $collectProvidersEvent = new CollectProvidersEvent();

        $provider1 = new ProviderMetadata('first', 'First');
        $provider2 = new ProviderMetadata('second', 'Second');
        $provider3 = new ProviderMetadata('third', 'Third');

        $collectProvidersEvent->addResult($provider1);
        $collectProvidersEvent->addResult($provider2);
        $collectProvidersEvent->addResult($provider3);

        $providers = $collectProvidersEvent->getProviders();

        $this->assertSame($provider1, $providers[0]);
        $this->assertSame($provider2, $providers[1]);
        $this->assertSame($provider3, $providers[2]);
    }

    public function testMultipleCallsToGetProvidersReturnSameResults(): void
    {
        $collectProvidersEvent = new CollectProvidersEvent();
        $providerMetadata = new ProviderMetadata('test', 'Test');

        $collectProvidersEvent->addResult($providerMetadata);

        $providers1 = $collectProvidersEvent->getProviders();
        $providers2 = $collectProvidersEvent->getProviders();

        $this->assertEquals($providers1, $providers2);
    }
}
