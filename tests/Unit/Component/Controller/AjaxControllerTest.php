<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Component\Controller;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\User\User;
use Joomla\Input\Input;
use MySitesGuru\HealthChecker\Component\Administrator\Controller\AjaxController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AjaxController::class)]
class AjaxControllerTest extends TestCase
{
    private CMSApplication $app;

    private AjaxController $controller;

    protected function setUp(): void
    {
        // Reset static state
        Session::setTokenValid(true);
        Factory::setApplication(null);

        // Create mock application
        $this->app = new CMSApplication();
        Factory::setApplication($this->app);

        $this->controller = new AjaxController();
    }

    protected function tearDown(): void
    {
        // Reset static state after each test
        Session::setTokenValid(true);
        Factory::setApplication(null);
    }

    public function testAjaxControllerExtendsBaseController(): void
    {
        $this->assertInstanceOf(\Joomla\CMS\MVC\Controller\BaseController::class, $this->controller);
    }

    // =========================================================================
    // Token validation tests
    // =========================================================================

    public function testMetadataRejectsInvalidToken(): void
    {
        Session::setTokenValid(false);

        ob_start();
        $this->controller->metadata();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
        $this->assertSame('application/json', $this->app->getHeaders()['Content-Type'] ?? '');
    }

    public function testCategoryRejectsInvalidToken(): void
    {
        Session::setTokenValid(false);

        ob_start();
        $this->controller->category();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testCheckRejectsInvalidToken(): void
    {
        Session::setTokenValid(false);

        ob_start();
        $this->controller->check();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testStatsRejectsInvalidToken(): void
    {
        Session::setTokenValid(false);

        ob_start();
        $this->controller->stats();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testClearCacheRejectsInvalidToken(): void
    {
        Session::setTokenValid(false);

        ob_start();
        $this->controller->clearCache();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testRunRejectsInvalidToken(): void
    {
        Session::setTokenValid(false);

        ob_start();
        $this->controller->run();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    // =========================================================================
    // Authorization tests - null user
    // =========================================================================

    public function testMetadataRejectsNullUser(): void
    {
        // No user set (null identity)
        ob_start();
        $this->controller->metadata();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testCategoryRejectsNullUser(): void
    {
        ob_start();
        $this->controller->category();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testCheckRejectsNullUser(): void
    {
        ob_start();
        $this->controller->check();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testStatsRejectsNullUser(): void
    {
        ob_start();
        $this->controller->stats();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testClearCacheRejectsNullUser(): void
    {
        ob_start();
        $this->controller->clearCache();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testRunRejectsNullUser(): void
    {
        ob_start();
        $this->controller->run();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    // =========================================================================
    // Authorization tests - unauthorized user
    // =========================================================================

    public function testMetadataRejectsUnauthorizedUser(): void
    {
        $user = new User(1);
        // User has no authorisation for core.manage
        $this->app->setIdentity($user);

        ob_start();
        $this->controller->metadata();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testCategoryRejectsUnauthorizedUser(): void
    {
        $user = new User(1);
        $this->app->setIdentity($user);

        ob_start();
        $this->controller->category();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testCheckRejectsUnauthorizedUser(): void
    {
        $user = new User(1);
        $this->app->setIdentity($user);

        ob_start();
        $this->controller->check();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testStatsRejectsUnauthorizedUser(): void
    {
        $user = new User(1);
        $this->app->setIdentity($user);

        ob_start();
        $this->controller->stats();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testClearCacheRejectsUnauthorizedUser(): void
    {
        $user = new User(1);
        $this->app->setIdentity($user);

        ob_start();
        $this->controller->clearCache();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testRunRejectsUnauthorizedUser(): void
    {
        $user = new User(1);
        $this->app->setIdentity($user);

        ob_start();
        $this->controller->run();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    // =========================================================================
    // Missing parameter tests
    // =========================================================================

    public function testCategoryRejectsMissingCategoryParameter(): void
    {
        $user = new User(1);
        $user->setAuthorisation('core.manage', 'com_healthchecker', true);
        $this->app->setIdentity($user);

        // Empty input - no category parameter
        $this->app->setInput(new Input([]));

        ob_start();
        $this->controller->category();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testCheckRejectsMissingSlugParameter(): void
    {
        $user = new User(1);
        $user->setAuthorisation('core.manage', 'com_healthchecker', true);
        $this->app->setIdentity($user);

        // Empty input - no slug parameter
        $this->app->setInput(new Input([]));

        ob_start();
        $this->controller->check();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    // =========================================================================
    // JSON response header tests
    // =========================================================================

    public function testMetadataSetsJsonContentType(): void
    {
        Session::setTokenValid(false);

        ob_start();
        $this->controller->metadata();
        ob_end_clean();

        $this->assertSame('application/json', $this->app->getHeaders()['Content-Type']);
    }

    public function testCategorySetsJsonContentType(): void
    {
        Session::setTokenValid(false);

        ob_start();
        $this->controller->category();
        ob_end_clean();

        $this->assertSame('application/json', $this->app->getHeaders()['Content-Type']);
    }

    public function testCheckSetsJsonContentType(): void
    {
        Session::setTokenValid(false);

        ob_start();
        $this->controller->check();
        ob_end_clean();

        $this->assertSame('application/json', $this->app->getHeaders()['Content-Type']);
    }

    public function testStatsSetsJsonContentType(): void
    {
        Session::setTokenValid(false);

        ob_start();
        $this->controller->stats();
        ob_end_clean();

        $this->assertSame('application/json', $this->app->getHeaders()['Content-Type']);
    }

    public function testClearCacheSetsJsonContentType(): void
    {
        Session::setTokenValid(false);

        ob_start();
        $this->controller->clearCache();
        ob_end_clean();

        $this->assertSame('application/json', $this->app->getHeaders()['Content-Type']);
    }

    public function testRunSetsJsonContentType(): void
    {
        Session::setTokenValid(false);

        ob_start();
        $this->controller->run();
        ob_end_clean();

        $this->assertSame('application/json', $this->app->getHeaders()['Content-Type']);
    }

    // =========================================================================
    // Application close tests
    // =========================================================================

    public function testMetadataClosesApplication(): void
    {
        ob_start();
        $this->controller->metadata();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testCategoryClosesApplication(): void
    {
        ob_start();
        $this->controller->category();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testCheckClosesApplication(): void
    {
        ob_start();
        $this->controller->check();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testStatsClosesApplication(): void
    {
        ob_start();
        $this->controller->stats();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testClearCacheClosesApplication(): void
    {
        ob_start();
        $this->controller->clearCache();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testRunClosesApplication(): void
    {
        ob_start();
        $this->controller->run();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    // =========================================================================
    // Empty category/slug validation tests
    // =========================================================================

    public function testCategoryRejectsEmptyString(): void
    {
        $user = new User(1);
        $user->setAuthorisation('core.manage', 'com_healthchecker', true);
        $this->app->setIdentity($user);

        $this->app->setInput(new Input([
            'category' => '',
        ]));

        ob_start();
        $this->controller->category();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }

    public function testCheckRejectsEmptySlug(): void
    {
        $user = new User(1);
        $user->setAuthorisation('core.manage', 'com_healthchecker', true);
        $this->app->setIdentity($user);

        $this->app->setInput(new Input([
            'slug' => '',
        ]));

        ob_start();
        $this->controller->check();
        ob_end_clean();

        $this->assertTrue($this->app->isClosed());
    }
}
