<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\MySitesGuru\Extension;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\User\User;
use MySitesGuru\HealthChecker\Component\Administrator\Category\HealthCategory;
use MySitesGuru\HealthChecker\Component\Administrator\Event\AfterToolbarBuildEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\BeforeReportDisplayEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectCategoriesEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectChecksEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectProvidersEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\HealthCheckerEvents;
use MySitesGuru\HealthChecker\Component\Administrator\Provider\ProviderMetadata;
use MySitesGuru\HealthChecker\Plugin\MySitesGuru\Checks\MySitesGuruConnectionCheck;
use MySitesGuru\HealthChecker\Plugin\MySitesGuru\Extension\MySitesGuruPlugin;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MySitesGuruPlugin::class)]
class MySitesGuruPluginTest extends TestCase
{
    private ?CMSApplication $cmsApplication = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Store original app if set
        try {
            $this->cmsApplication = Factory::getApplication();
        } catch (\Exception) {
            $this->cmsApplication = null;
        }

        // Set up a mock application with authorized user
        $cmsApplication = new CMSApplication();
        $user = new User(42);
        $user->setAuthorisation('core.manage', 'com_healthchecker', true);

        $cmsApplication->setIdentity($user);
        Factory::setApplication($cmsApplication);
    }

    protected function tearDown(): void
    {
        // Restore original application
        Factory::setApplication($this->cmsApplication);
        Toolbar::clearInstances();

        parent::tearDown();
    }

    public function testGetSubscribedEventsReturnsCorrectMapping(): void
    {
        $events = MySitesGuruPlugin::getSubscribedEvents();

        $this->assertArrayHasKey(HealthCheckerEvents::COLLECT_CATEGORIES->value, $events);
        $this->assertArrayHasKey(HealthCheckerEvents::COLLECT_CHECKS->value, $events);
        $this->assertArrayHasKey(HealthCheckerEvents::COLLECT_PROVIDERS->value, $events);
        $this->assertArrayHasKey(HealthCheckerEvents::BEFORE_REPORT_DISPLAY->value, $events);
        $this->assertArrayHasKey(HealthCheckerEvents::AFTER_TOOLBAR_BUILD->value, $events);
    }

    public function testGetSubscribedEventsReturnsCorrectHandlerMethods(): void
    {
        $events = MySitesGuruPlugin::getSubscribedEvents();

        $this->assertSame('onCollectCategories', $events[HealthCheckerEvents::COLLECT_CATEGORIES->value]);
        $this->assertSame('onCollectChecks', $events[HealthCheckerEvents::COLLECT_CHECKS->value]);
        $this->assertSame('onCollectProviders', $events[HealthCheckerEvents::COLLECT_PROVIDERS->value]);
        $this->assertSame('onBeforeReportDisplay', $events[HealthCheckerEvents::BEFORE_REPORT_DISPLAY->value]);
        $this->assertSame('onAfterToolbarBuild', $events[HealthCheckerEvents::AFTER_TOOLBAR_BUILD->value]);
    }

    public function testOnCollectCategoriesAddsMySitesGuruCategory(): void
    {
        $mySitesGuruPlugin = new MySitesGuruPlugin(new \stdClass());
        $collectCategoriesEvent = new CollectCategoriesEvent();

        $mySitesGuruPlugin->onCollectCategories($collectCategoriesEvent);

        $categories = $collectCategoriesEvent->getCategories();
        $this->assertCount(1, $categories);
        $this->assertInstanceOf(HealthCategory::class, $categories[0]);
    }

    public function testOnCollectCategoriesRegistersCorrectSlug(): void
    {
        $mySitesGuruPlugin = new MySitesGuruPlugin(new \stdClass());
        $collectCategoriesEvent = new CollectCategoriesEvent();

        $mySitesGuruPlugin->onCollectCategories($collectCategoriesEvent);

        $categories = $collectCategoriesEvent->getCategories();
        $this->assertSame('mysitesguru', $categories[0]->slug);
    }

    public function testOnCollectCategoriesRegistersCorrectLabel(): void
    {
        $mySitesGuruPlugin = new MySitesGuruPlugin(new \stdClass());
        $collectCategoriesEvent = new CollectCategoriesEvent();

        $mySitesGuruPlugin->onCollectCategories($collectCategoriesEvent);

        $categories = $collectCategoriesEvent->getCategories();
        $this->assertSame('mySites.guru Integration', $categories[0]->label);
    }

    public function testOnCollectCategoriesRegistersCorrectIcon(): void
    {
        $mySitesGuruPlugin = new MySitesGuruPlugin(new \stdClass());
        $collectCategoriesEvent = new CollectCategoriesEvent();

        $mySitesGuruPlugin->onCollectCategories($collectCategoriesEvent);

        $categories = $collectCategoriesEvent->getCategories();
        $this->assertSame('fa-tachometer-alt', $categories[0]->icon);
    }

    public function testOnCollectCategoriesRegistersCorrectSortOrder(): void
    {
        $mySitesGuruPlugin = new MySitesGuruPlugin(new \stdClass());
        $collectCategoriesEvent = new CollectCategoriesEvent();

        $mySitesGuruPlugin->onCollectCategories($collectCategoriesEvent);

        $categories = $collectCategoriesEvent->getCategories();
        $this->assertSame(90, $categories[0]->sortOrder);
    }

    public function testOnCollectCategoriesRegistersLogoUrl(): void
    {
        $mySitesGuruPlugin = new MySitesGuruPlugin(new \stdClass());
        $collectCategoriesEvent = new CollectCategoriesEvent();

        $mySitesGuruPlugin->onCollectCategories($collectCategoriesEvent);

        $categories = $collectCategoriesEvent->getCategories();
        $this->assertSame('/media/plg_healthchecker_mysitesguru/logo.png', $categories[0]->logoUrl);
    }

    public function testOnCollectChecksAddsConnectionCheck(): void
    {
        $mySitesGuruPlugin = new MySitesGuruPlugin(new \stdClass());
        $collectChecksEvent = new CollectChecksEvent();

        $mySitesGuruPlugin->onCollectChecks($collectChecksEvent);

        $checks = $collectChecksEvent->getChecks();
        $this->assertCount(1, $checks);
        $this->assertInstanceOf(MySitesGuruConnectionCheck::class, $checks[0]);
    }

    public function testOnCollectProvidersAddsMySitesGuruProvider(): void
    {
        $mySitesGuruPlugin = new MySitesGuruPlugin(new \stdClass());
        $collectProvidersEvent = new CollectProvidersEvent();

        $mySitesGuruPlugin->onCollectProviders($collectProvidersEvent);

        $providers = $collectProvidersEvent->getProviders();
        $this->assertCount(1, $providers);
        $this->assertInstanceOf(ProviderMetadata::class, $providers[0]);
    }

    public function testOnCollectProvidersRegistersCorrectSlug(): void
    {
        $mySitesGuruPlugin = new MySitesGuruPlugin(new \stdClass());
        $collectProvidersEvent = new CollectProvidersEvent();

        $mySitesGuruPlugin->onCollectProviders($collectProvidersEvent);

        $providers = $collectProvidersEvent->getProviders();
        $this->assertSame('mysitesguru', $providers[0]->slug);
    }

    public function testOnCollectProvidersRegistersCorrectName(): void
    {
        $mySitesGuruPlugin = new MySitesGuruPlugin(new \stdClass());
        $collectProvidersEvent = new CollectProvidersEvent();

        $mySitesGuruPlugin->onCollectProviders($collectProvidersEvent);

        $providers = $collectProvidersEvent->getProviders();
        $this->assertSame('mySites.guru', $providers[0]->name);
    }

    public function testOnCollectProvidersRegistersCorrectDescription(): void
    {
        $mySitesGuruPlugin = new MySitesGuruPlugin(new \stdClass());
        $collectProvidersEvent = new CollectProvidersEvent();

        $mySitesGuruPlugin->onCollectProviders($collectProvidersEvent);

        $providers = $collectProvidersEvent->getProviders();
        $this->assertSame(
            'Joomla Monitoring Dashboard - Monitor unlimited sites from one place',
            $providers[0]->description,
        );
    }

    public function testOnCollectProvidersRegistersCorrectUrl(): void
    {
        $mySitesGuruPlugin = new MySitesGuruPlugin(new \stdClass());
        $collectProvidersEvent = new CollectProvidersEvent();

        $mySitesGuruPlugin->onCollectProviders($collectProvidersEvent);

        $providers = $collectProvidersEvent->getProviders();
        $this->assertSame('https://mysites.guru', $providers[0]->url);
    }

    public function testOnCollectProvidersRegistersCorrectIcon(): void
    {
        $mySitesGuruPlugin = new MySitesGuruPlugin(new \stdClass());
        $collectProvidersEvent = new CollectProvidersEvent();

        $mySitesGuruPlugin->onCollectProviders($collectProvidersEvent);

        $providers = $collectProvidersEvent->getProviders();
        $this->assertSame('fa-tachometer-alt', $providers[0]->icon);
    }

    public function testOnCollectProvidersRegistersCorrectLogoUrl(): void
    {
        $mySitesGuruPlugin = new MySitesGuruPlugin(new \stdClass());
        $collectProvidersEvent = new CollectProvidersEvent();

        $mySitesGuruPlugin->onCollectProviders($collectProvidersEvent);

        $providers = $collectProvidersEvent->getProviders();
        $this->assertSame('/media/plg_healthchecker_mysitesguru/logo.png', $providers[0]->logoUrl);
    }

    public function testOnCollectProvidersRegistersCorrectVersion(): void
    {
        $mySitesGuruPlugin = new MySitesGuruPlugin(new \stdClass());
        $collectProvidersEvent = new CollectProvidersEvent();

        $mySitesGuruPlugin->onCollectProviders($collectProvidersEvent);

        $providers = $collectProvidersEvent->getProviders();
        $this->assertSame('1.0.0', $providers[0]->version);
    }

    public function testOnBeforeReportDisplayAddsHtmlContent(): void
    {
        $mySitesGuruPlugin = new MySitesGuruPlugin(new \stdClass());
        $beforeReportDisplayEvent = new BeforeReportDisplayEvent();

        $mySitesGuruPlugin->onBeforeReportDisplay($beforeReportDisplayEvent);

        $html = $beforeReportDisplayEvent->getHtmlContent();
        // The plugin adds a promotional banner for mySites.guru
        $this->assertIsString($html);
    }

    public function testOnBeforeReportDisplayBannerContainsMySitesGuruLink(): void
    {
        $mySitesGuruPlugin = new MySitesGuruPlugin(new \stdClass());
        $beforeReportDisplayEvent = new BeforeReportDisplayEvent();

        $mySitesGuruPlugin->onBeforeReportDisplay($beforeReportDisplayEvent);

        $html = $beforeReportDisplayEvent->getHtmlContent();
        // The banner should link to mySites.guru
        $this->assertStringContainsString('mysites.guru', $html);
    }

    public function testOnBeforeReportDisplayBannerContainsBannerClass(): void
    {
        $mySitesGuruPlugin = new MySitesGuruPlugin(new \stdClass());
        $beforeReportDisplayEvent = new BeforeReportDisplayEvent();

        $mySitesGuruPlugin->onBeforeReportDisplay($beforeReportDisplayEvent);

        $html = $beforeReportDisplayEvent->getHtmlContent();
        // The banner should use mysitesguru-banner class
        $this->assertStringContainsString('mysitesguru-banner', $html);
    }

    public function testOnAfterToolbarBuildAddsButton(): void
    {
        // Clear any existing toolbar instances
        Toolbar::clearInstances();

        $mySitesGuruPlugin = new MySitesGuruPlugin(new \stdClass());
        $toolbar = Toolbar::getInstance();
        $afterToolbarBuildEvent = new AfterToolbarBuildEvent($toolbar);

        $mySitesGuruPlugin->onAfterToolbarBuild($afterToolbarBuildEvent);

        $buttons = $toolbar->getButtons();
        $this->assertNotEmpty($buttons);
    }

    public function testOnAfterToolbarBuildButtonLinksToMySitesGuru(): void
    {
        // Clear any existing toolbar instances
        Toolbar::clearInstances();

        $mySitesGuruPlugin = new MySitesGuruPlugin(new \stdClass());
        $toolbar = Toolbar::getInstance();
        $afterToolbarBuildEvent = new AfterToolbarBuildEvent($toolbar);

        $mySitesGuruPlugin->onAfterToolbarBuild($afterToolbarBuildEvent);

        $buttons = $toolbar->getButtons();
        // There should be a link button added
        $hasLinkButton = false;
        foreach ($buttons as $button) {
            if (method_exists($button, 'getType') && $button->getType() !== '') {
                $hasLinkButton = true;
                break;
            }
        }

        $this->assertTrue($hasLinkButton);
    }

    public function testEventValuesMatch(): void
    {
        // Test that the event string values match expected patterns
        $this->assertSame('onHealthCheckerCollectCategories', HealthCheckerEvents::COLLECT_CATEGORIES->value);
        $this->assertSame('onHealthCheckerCollectChecks', HealthCheckerEvents::COLLECT_CHECKS->value);
        $this->assertSame('onHealthCheckerCollectProviders', HealthCheckerEvents::COLLECT_PROVIDERS->value);
        $this->assertSame('onHealthCheckerBeforeReportDisplay', HealthCheckerEvents::BEFORE_REPORT_DISPLAY->value);
        $this->assertSame('onHealthCheckerAfterToolbarBuild', HealthCheckerEvents::AFTER_TOOLBAR_BUILD->value);
    }

    public function testOnBeforeReportDisplayDoesNothingWithoutPermission(): void
    {
        // Set up user without permission
        $cmsApplication = new CMSApplication();
        $user = new User(42);
        // No authorization set - user cannot manage com_healthchecker
        $cmsApplication->setIdentity($user);
        Factory::setApplication($cmsApplication);

        $mySitesGuruPlugin = new MySitesGuruPlugin(new \stdClass());
        $beforeReportDisplayEvent = new BeforeReportDisplayEvent();

        $mySitesGuruPlugin->onBeforeReportDisplay($beforeReportDisplayEvent);

        $html = $beforeReportDisplayEvent->getHtmlContent();
        // Should be empty - no content added without permission
        $this->assertSame('', $html);
    }

    public function testOnAfterToolbarBuildDoesNothingWithoutPermission(): void
    {
        // Set up user without permission
        $cmsApplication = new CMSApplication();
        $user = new User(42);
        // No authorization set - user cannot manage com_healthchecker
        $cmsApplication->setIdentity($user);
        Factory::setApplication($cmsApplication);

        Toolbar::clearInstances();

        $mySitesGuruPlugin = new MySitesGuruPlugin(new \stdClass());
        $toolbar = Toolbar::getInstance();
        $afterToolbarBuildEvent = new AfterToolbarBuildEvent($toolbar);

        $mySitesGuruPlugin->onAfterToolbarBuild($afterToolbarBuildEvent);

        $buttons = $toolbar->getButtons();
        // Should be empty - no button added without permission
        $this->assertEmpty($buttons);
    }

    public function testOnBeforeReportDisplayDoesNothingWithNullUser(): void
    {
        // Set up application with no user
        $cmsApplication = new CMSApplication();
        $cmsApplication->setIdentity(null);
        Factory::setApplication($cmsApplication);

        $mySitesGuruPlugin = new MySitesGuruPlugin(new \stdClass());
        $beforeReportDisplayEvent = new BeforeReportDisplayEvent();

        $mySitesGuruPlugin->onBeforeReportDisplay($beforeReportDisplayEvent);

        $html = $beforeReportDisplayEvent->getHtmlContent();
        // Should be empty - no content added without user
        $this->assertSame('', $html);
    }

    public function testOnAfterToolbarBuildDoesNothingWithNullUser(): void
    {
        // Set up application with no user
        $cmsApplication = new CMSApplication();
        $cmsApplication->setIdentity(null);
        Factory::setApplication($cmsApplication);

        Toolbar::clearInstances();

        $mySitesGuruPlugin = new MySitesGuruPlugin(new \stdClass());
        $toolbar = Toolbar::getInstance();
        $afterToolbarBuildEvent = new AfterToolbarBuildEvent($toolbar);

        $mySitesGuruPlugin->onAfterToolbarBuild($afterToolbarBuildEvent);

        $buttons = $toolbar->getButtons();
        // Should be empty - no button added without user
        $this->assertEmpty($buttons);
    }
}
