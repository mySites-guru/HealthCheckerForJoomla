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
        $plugin = new MySitesGuruPlugin(new \stdClass());
        $event = new CollectCategoriesEvent();

        $plugin->onCollectCategories($event);

        $categories = $event->getCategories();
        $this->assertCount(1, $categories);
        $this->assertInstanceOf(HealthCategory::class, $categories[0]);
    }

    public function testOnCollectCategoriesRegistersCorrectSlug(): void
    {
        $plugin = new MySitesGuruPlugin(new \stdClass());
        $event = new CollectCategoriesEvent();

        $plugin->onCollectCategories($event);

        $categories = $event->getCategories();
        $this->assertSame('mysitesguru', $categories[0]->slug);
    }

    public function testOnCollectCategoriesRegistersCorrectLabel(): void
    {
        $plugin = new MySitesGuruPlugin(new \stdClass());
        $event = new CollectCategoriesEvent();

        $plugin->onCollectCategories($event);

        $categories = $event->getCategories();
        $this->assertSame('mySites.guru Integration', $categories[0]->label);
    }

    public function testOnCollectCategoriesRegistersCorrectIcon(): void
    {
        $plugin = new MySitesGuruPlugin(new \stdClass());
        $event = new CollectCategoriesEvent();

        $plugin->onCollectCategories($event);

        $categories = $event->getCategories();
        $this->assertSame('fa-tachometer-alt', $categories[0]->icon);
    }

    public function testOnCollectCategoriesRegistersCorrectSortOrder(): void
    {
        $plugin = new MySitesGuruPlugin(new \stdClass());
        $event = new CollectCategoriesEvent();

        $plugin->onCollectCategories($event);

        $categories = $event->getCategories();
        $this->assertSame(90, $categories[0]->sortOrder);
    }

    public function testOnCollectCategoriesRegistersLogoUrl(): void
    {
        $plugin = new MySitesGuruPlugin(new \stdClass());
        $event = new CollectCategoriesEvent();

        $plugin->onCollectCategories($event);

        $categories = $event->getCategories();
        $this->assertSame('/media/plg_healthchecker_mysitesguru/logo.png', $categories[0]->logoUrl);
    }

    public function testOnCollectChecksAddsConnectionCheck(): void
    {
        $plugin = new MySitesGuruPlugin(new \stdClass());
        $event = new CollectChecksEvent();

        $plugin->onCollectChecks($event);

        $checks = $event->getChecks();
        $this->assertCount(1, $checks);
        $this->assertInstanceOf(MySitesGuruConnectionCheck::class, $checks[0]);
    }

    public function testOnCollectProvidersAddsMySitesGuruProvider(): void
    {
        $plugin = new MySitesGuruPlugin(new \stdClass());
        $event = new CollectProvidersEvent();

        $plugin->onCollectProviders($event);

        $providers = $event->getProviders();
        $this->assertCount(1, $providers);
        $this->assertInstanceOf(ProviderMetadata::class, $providers[0]);
    }

    public function testOnCollectProvidersRegistersCorrectSlug(): void
    {
        $plugin = new MySitesGuruPlugin(new \stdClass());
        $event = new CollectProvidersEvent();

        $plugin->onCollectProviders($event);

        $providers = $event->getProviders();
        $this->assertSame('mysitesguru', $providers[0]->slug);
    }

    public function testOnCollectProvidersRegistersCorrectName(): void
    {
        $plugin = new MySitesGuruPlugin(new \stdClass());
        $event = new CollectProvidersEvent();

        $plugin->onCollectProviders($event);

        $providers = $event->getProviders();
        $this->assertSame('mySites.guru', $providers[0]->name);
    }

    public function testOnCollectProvidersRegistersCorrectDescription(): void
    {
        $plugin = new MySitesGuruPlugin(new \stdClass());
        $event = new CollectProvidersEvent();

        $plugin->onCollectProviders($event);

        $providers = $event->getProviders();
        $this->assertSame(
            'Joomla Monitoring Dashboard - Monitor unlimited sites from one place',
            $providers[0]->description,
        );
    }

    public function testOnCollectProvidersRegistersCorrectUrl(): void
    {
        $plugin = new MySitesGuruPlugin(new \stdClass());
        $event = new CollectProvidersEvent();

        $plugin->onCollectProviders($event);

        $providers = $event->getProviders();
        $this->assertSame('https://mysites.guru', $providers[0]->url);
    }

    public function testOnCollectProvidersRegistersCorrectIcon(): void
    {
        $plugin = new MySitesGuruPlugin(new \stdClass());
        $event = new CollectProvidersEvent();

        $plugin->onCollectProviders($event);

        $providers = $event->getProviders();
        $this->assertSame('fa-tachometer-alt', $providers[0]->icon);
    }

    public function testOnCollectProvidersRegistersCorrectLogoUrl(): void
    {
        $plugin = new MySitesGuruPlugin(new \stdClass());
        $event = new CollectProvidersEvent();

        $plugin->onCollectProviders($event);

        $providers = $event->getProviders();
        $this->assertSame('/media/plg_healthchecker_mysitesguru/logo.png', $providers[0]->logoUrl);
    }

    public function testOnCollectProvidersRegistersCorrectVersion(): void
    {
        $plugin = new MySitesGuruPlugin(new \stdClass());
        $event = new CollectProvidersEvent();

        $plugin->onCollectProviders($event);

        $providers = $event->getProviders();
        $this->assertSame('1.0.0', $providers[0]->version);
    }

    public function testOnBeforeReportDisplayAddsHtmlContent(): void
    {
        $plugin = new MySitesGuruPlugin(new \stdClass());
        $event = new BeforeReportDisplayEvent();

        $plugin->onBeforeReportDisplay($event);

        $html = $event->getHtmlContent();
        // The plugin adds a promotional banner for mySites.guru
        $this->assertIsString($html);
    }

    public function testOnBeforeReportDisplayBannerContainsMySitesGuruLink(): void
    {
        $plugin = new MySitesGuruPlugin(new \stdClass());
        $event = new BeforeReportDisplayEvent();

        $plugin->onBeforeReportDisplay($event);

        $html = $event->getHtmlContent();
        // The banner should link to mySites.guru
        $this->assertStringContainsString('mysites.guru', $html);
    }

    public function testOnBeforeReportDisplayBannerContainsBannerClass(): void
    {
        $plugin = new MySitesGuruPlugin(new \stdClass());
        $event = new BeforeReportDisplayEvent();

        $plugin->onBeforeReportDisplay($event);

        $html = $event->getHtmlContent();
        // The banner should use mysitesguru-banner class
        $this->assertStringContainsString('mysitesguru-banner', $html);
    }

    public function testOnAfterToolbarBuildAddsButton(): void
    {
        // Clear any existing toolbar instances
        Toolbar::clearInstances();

        $plugin = new MySitesGuruPlugin(new \stdClass());
        $toolbar = Toolbar::getInstance();
        $event = new AfterToolbarBuildEvent($toolbar);

        $plugin->onAfterToolbarBuild($event);

        $buttons = $toolbar->getButtons();
        $this->assertNotEmpty($buttons);
    }

    public function testOnAfterToolbarBuildButtonLinksToMySitesGuru(): void
    {
        // Clear any existing toolbar instances
        Toolbar::clearInstances();

        $plugin = new MySitesGuruPlugin(new \stdClass());
        $toolbar = Toolbar::getInstance();
        $event = new AfterToolbarBuildEvent($toolbar);

        $plugin->onAfterToolbarBuild($event);

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

        $plugin = new MySitesGuruPlugin(new \stdClass());
        $event = new BeforeReportDisplayEvent();

        $plugin->onBeforeReportDisplay($event);

        $html = $event->getHtmlContent();
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

        $plugin = new MySitesGuruPlugin(new \stdClass());
        $toolbar = Toolbar::getInstance();
        $event = new AfterToolbarBuildEvent($toolbar);

        $plugin->onAfterToolbarBuild($event);

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

        $plugin = new MySitesGuruPlugin(new \stdClass());
        $event = new BeforeReportDisplayEvent();

        $plugin->onBeforeReportDisplay($event);

        $html = $event->getHtmlContent();
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

        $plugin = new MySitesGuruPlugin(new \stdClass());
        $toolbar = Toolbar::getInstance();
        $event = new AfterToolbarBuildEvent($toolbar);

        $plugin->onAfterToolbarBuild($event);

        $buttons = $toolbar->getButtons();
        // Should be empty - no button added without user
        $this->assertEmpty($buttons);
    }
}
