<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Component\Controller;

use HealthChecker\Tests\Utilities\MockHealthCheckerComponent;
use HealthChecker\Tests\Utilities\MockHealthCheckRunner;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\User\User;
use Joomla\Input\Input;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Component\Administrator\Controller\AjaxController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AjaxController::class)]
class AjaxControllerTest extends TestCase
{
    private CMSApplication $cmsApplication;

    private AjaxController $ajaxController;

    protected function setUp(): void
    {
        // Reset static state
        Session::setTokenValid(true);
        Factory::setApplication(null);

        // Create mock application
        $this->cmsApplication = new CMSApplication();
        Factory::setApplication($this->cmsApplication);

        $this->ajaxController = new AjaxController();
    }

    protected function tearDown(): void
    {
        // Reset static state after each test
        Session::setTokenValid(true);
        Factory::setApplication(null);
    }

    /**
     * Helper to set up an authorized user for testing
     */
    private function setUpAuthorizedUser(): User
    {
        $user = new User(1);
        $user->setAuthorisation('core.manage', 'com_healthchecker', true);

        $this->cmsApplication->setIdentity($user);

        return $user;
    }

    /**
     * Helper to create a mock component with a mock runner
     *
     * @param MockHealthCheckRunner $mockHealthCheckRunner The configured mock runner
     */
    private function setUpMockComponent(MockHealthCheckRunner $mockHealthCheckRunner): void
    {
        $mockHealthCheckerComponent = new MockHealthCheckerComponent();
        $mockHealthCheckerComponent->setHealthCheckRunner($mockHealthCheckRunner);

        $this->cmsApplication->setComponent('com_healthchecker', $mockHealthCheckerComponent);
    }

    public function testAjaxControllerExtendsBaseController(): void
    {
        $this->assertInstanceOf(\Joomla\CMS\MVC\Controller\BaseController::class, $this->ajaxController);
    }

    // =========================================================================
    // Token validation tests
    // =========================================================================

    public function testMetadataRejectsInvalidToken(): void
    {
        Session::setTokenValid(false);

        ob_start();
        $this->ajaxController->metadata();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
        $this->assertSame('application/json', $this->cmsApplication->getHeaders()['Content-Type'] ?? '');
    }

    public function testCategoryRejectsInvalidToken(): void
    {
        Session::setTokenValid(false);

        ob_start();
        $this->ajaxController->category();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
    }

    public function testCheckRejectsInvalidToken(): void
    {
        Session::setTokenValid(false);

        ob_start();
        $this->ajaxController->check();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
    }

    public function testStatsRejectsInvalidToken(): void
    {
        Session::setTokenValid(false);

        ob_start();
        $this->ajaxController->stats();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
    }

    public function testClearCacheRejectsInvalidToken(): void
    {
        Session::setTokenValid(false);

        ob_start();
        $this->ajaxController->clearCache();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
    }

    public function testRunRejectsInvalidToken(): void
    {
        Session::setTokenValid(false);

        ob_start();
        $this->ajaxController->run();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
    }

    // =========================================================================
    // Authorization tests - null user
    // =========================================================================

    public function testMetadataRejectsNullUser(): void
    {
        // No user set (null identity)
        ob_start();
        $this->ajaxController->metadata();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
    }

    public function testCategoryRejectsNullUser(): void
    {
        ob_start();
        $this->ajaxController->category();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
    }

    public function testCheckRejectsNullUser(): void
    {
        ob_start();
        $this->ajaxController->check();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
    }

    public function testStatsRejectsNullUser(): void
    {
        ob_start();
        $this->ajaxController->stats();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
    }

    public function testClearCacheRejectsNullUser(): void
    {
        ob_start();
        $this->ajaxController->clearCache();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
    }

    public function testRunRejectsNullUser(): void
    {
        ob_start();
        $this->ajaxController->run();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
    }

    // =========================================================================
    // Authorization tests - unauthorized user
    // =========================================================================

    public function testMetadataRejectsUnauthorizedUser(): void
    {
        $user = new User(1);
        // User has no authorisation for core.manage
        $this->cmsApplication->setIdentity($user);

        ob_start();
        $this->ajaxController->metadata();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
    }

    public function testCategoryRejectsUnauthorizedUser(): void
    {
        $user = new User(1);
        $this->cmsApplication->setIdentity($user);

        ob_start();
        $this->ajaxController->category();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
    }

    public function testCheckRejectsUnauthorizedUser(): void
    {
        $user = new User(1);
        $this->cmsApplication->setIdentity($user);

        ob_start();
        $this->ajaxController->check();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
    }

    public function testStatsRejectsUnauthorizedUser(): void
    {
        $user = new User(1);
        $this->cmsApplication->setIdentity($user);

        ob_start();
        $this->ajaxController->stats();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
    }

    public function testClearCacheRejectsUnauthorizedUser(): void
    {
        $user = new User(1);
        $this->cmsApplication->setIdentity($user);

        ob_start();
        $this->ajaxController->clearCache();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
    }

    public function testRunRejectsUnauthorizedUser(): void
    {
        $user = new User(1);
        $this->cmsApplication->setIdentity($user);

        ob_start();
        $this->ajaxController->run();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
    }

    // =========================================================================
    // Missing parameter tests
    // =========================================================================

    public function testCategoryRejectsMissingCategoryParameter(): void
    {
        $user = new User(1);
        $user->setAuthorisation('core.manage', 'com_healthchecker', true);

        $this->cmsApplication->setIdentity($user);

        // Empty input - no category parameter
        $this->cmsApplication->setInput(new Input([]));

        ob_start();
        $this->ajaxController->category();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
    }

    public function testCheckRejectsMissingSlugParameter(): void
    {
        $user = new User(1);
        $user->setAuthorisation('core.manage', 'com_healthchecker', true);

        $this->cmsApplication->setIdentity($user);

        // Empty input - no slug parameter
        $this->cmsApplication->setInput(new Input([]));

        ob_start();
        $this->ajaxController->check();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
    }

    // =========================================================================
    // JSON response header tests
    // =========================================================================

    public function testMetadataSetsJsonContentType(): void
    {
        Session::setTokenValid(false);

        ob_start();
        $this->ajaxController->metadata();
        ob_end_clean();

        $this->assertSame('application/json', $this->cmsApplication->getHeaders()['Content-Type']);
    }

    public function testCategorySetsJsonContentType(): void
    {
        Session::setTokenValid(false);

        ob_start();
        $this->ajaxController->category();
        ob_end_clean();

        $this->assertSame('application/json', $this->cmsApplication->getHeaders()['Content-Type']);
    }

    public function testCheckSetsJsonContentType(): void
    {
        Session::setTokenValid(false);

        ob_start();
        $this->ajaxController->check();
        ob_end_clean();

        $this->assertSame('application/json', $this->cmsApplication->getHeaders()['Content-Type']);
    }

    public function testStatsSetsJsonContentType(): void
    {
        Session::setTokenValid(false);

        ob_start();
        $this->ajaxController->stats();
        ob_end_clean();

        $this->assertSame('application/json', $this->cmsApplication->getHeaders()['Content-Type']);
    }

    public function testClearCacheSetsJsonContentType(): void
    {
        Session::setTokenValid(false);

        ob_start();
        $this->ajaxController->clearCache();
        ob_end_clean();

        $this->assertSame('application/json', $this->cmsApplication->getHeaders()['Content-Type']);
    }

    public function testRunSetsJsonContentType(): void
    {
        Session::setTokenValid(false);

        ob_start();
        $this->ajaxController->run();
        ob_end_clean();

        $this->assertSame('application/json', $this->cmsApplication->getHeaders()['Content-Type']);
    }

    // =========================================================================
    // Application close tests
    // =========================================================================

    public function testMetadataClosesApplication(): void
    {
        ob_start();
        $this->ajaxController->metadata();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
    }

    public function testCategoryClosesApplication(): void
    {
        ob_start();
        $this->ajaxController->category();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
    }

    public function testCheckClosesApplication(): void
    {
        ob_start();
        $this->ajaxController->check();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
    }

    public function testStatsClosesApplication(): void
    {
        ob_start();
        $this->ajaxController->stats();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
    }

    public function testClearCacheClosesApplication(): void
    {
        ob_start();
        $this->ajaxController->clearCache();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
    }

    public function testRunClosesApplication(): void
    {
        ob_start();
        $this->ajaxController->run();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
    }

    // =========================================================================
    // Empty category/slug validation tests
    // =========================================================================

    public function testCategoryRejectsEmptyString(): void
    {
        $user = new User(1);
        $user->setAuthorisation('core.manage', 'com_healthchecker', true);

        $this->cmsApplication->setIdentity($user);

        $this->cmsApplication->setInput(new Input([
            'category' => '',
        ]));

        ob_start();
        $this->ajaxController->category();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
    }

    public function testCheckRejectsEmptySlug(): void
    {
        $user = new User(1);
        $user->setAuthorisation('core.manage', 'com_healthchecker', true);

        $this->cmsApplication->setIdentity($user);

        $this->cmsApplication->setInput(new Input([
            'slug' => '',
        ]));

        ob_start();
        $this->ajaxController->check();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
    }

    // =========================================================================
    // SUCCESS PATH TESTS - metadata()
    // =========================================================================

    public function testMetadataReturnsSuccessfulResponseForAuthorizedUser(): void
    {
        $this->setUpAuthorizedUser();

        $mockHealthCheckRunner = new MockHealthCheckRunner();
        $mockHealthCheckRunner->setMetadata([
            'categories' => [[
                'slug' => 'system',
                'label' => 'System',
            ]],
            'providers' => [[
                'slug' => 'core',
                'name' => 'Core',
            ]],
            'checks' => [[
                'slug' => 'core.php_version',
                'category' => 'system',
                'title' => 'PHP Version',
            ]],
        ]);
        $this->setUpMockComponent($mockHealthCheckRunner);

        ob_start();
        $this->ajaxController->metadata();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
        $this->assertSame('application/json', $this->cmsApplication->getHeaders()['Content-Type']);
    }

    public function testMetadataHandlesExceptionGracefully(): void
    {
        $this->setUpAuthorizedUser();

        $mockHealthCheckRunner = new MockHealthCheckRunner();
        $mockHealthCheckRunner->throwExceptionOn('getMetadata', new \RuntimeException('Test error'));
        $this->setUpMockComponent($mockHealthCheckRunner);

        ob_start();
        $this->ajaxController->metadata();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
        $this->assertSame('application/json', $this->cmsApplication->getHeaders()['Content-Type']);
    }

    // =========================================================================
    // SUCCESS PATH TESTS - category()
    // =========================================================================

    public function testCategoryReturnsSuccessfulResponseForAuthorizedUser(): void
    {
        $this->setUpAuthorizedUser();
        $this->cmsApplication->setInput(new Input([
            'category' => 'system',
        ]));

        $mockHealthCheckRunner = new MockHealthCheckRunner();
        $mockHealthCheckRunner->setCategoryResults([
            'core.php_version' => [
                'status' => 'good',
                'title' => 'PHP Version',
                'description' => 'PHP version is good',
                'slug' => 'core.php_version',
                'category' => 'system',
                'provider' => 'core',
            ],
        ]);
        $this->setUpMockComponent($mockHealthCheckRunner);

        ob_start();
        $this->ajaxController->category();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
        $this->assertSame('application/json', $this->cmsApplication->getHeaders()['Content-Type']);
    }

    public function testCategoryHandlesExceptionGracefully(): void
    {
        $this->setUpAuthorizedUser();
        $this->cmsApplication->setInput(new Input([
            'category' => 'system',
        ]));

        $mockHealthCheckRunner = new MockHealthCheckRunner();
        $mockHealthCheckRunner->throwExceptionOn('runCategory', new \RuntimeException('Test error'));
        $this->setUpMockComponent($mockHealthCheckRunner);

        ob_start();
        $this->ajaxController->category();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
        $this->assertSame('application/json', $this->cmsApplication->getHeaders()['Content-Type']);
    }

    public function testCategoryRejectsZeroAsCategory(): void
    {
        $this->setUpAuthorizedUser();
        $this->cmsApplication->setInput(new Input([
            'category' => '0',
        ]));

        ob_start();
        $this->ajaxController->category();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
    }

    // =========================================================================
    // SUCCESS PATH TESTS - check()
    // =========================================================================

    public function testCheckReturnsSuccessfulResponseForAuthorizedUser(): void
    {
        $this->setUpAuthorizedUser();
        $this->cmsApplication->setInput(new Input([
            'slug' => 'core.php_version',
        ]));

        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Good,
            title: 'PHP Version',
            description: 'PHP version is good',
            slug: 'core.php_version',
            category: 'system',
            provider: 'core',
        );

        $mockHealthCheckRunner = new MockHealthCheckRunner();
        $mockHealthCheckRunner->setSingleCheckResult($healthCheckResult);
        $this->setUpMockComponent($mockHealthCheckRunner);

        ob_start();
        $this->ajaxController->check();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
        $this->assertSame('application/json', $this->cmsApplication->getHeaders()['Content-Type']);
    }

    public function testCheckReturnsErrorForNonExistentSlug(): void
    {
        $this->setUpAuthorizedUser();
        $this->cmsApplication->setInput(new Input([
            'slug' => 'nonexistent.check',
        ]));

        $mockHealthCheckRunner = new MockHealthCheckRunner();
        $mockHealthCheckRunner->setSingleCheckResult(null);
        $this->setUpMockComponent($mockHealthCheckRunner);

        ob_start();
        $this->ajaxController->check();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
        $this->assertSame('application/json', $this->cmsApplication->getHeaders()['Content-Type']);
    }

    public function testCheckHandlesExceptionGracefully(): void
    {
        $this->setUpAuthorizedUser();
        $this->cmsApplication->setInput(new Input([
            'slug' => 'core.php_version',
        ]));

        $mockHealthCheckRunner = new MockHealthCheckRunner();
        $mockHealthCheckRunner->throwExceptionOn('runSingleCheck', new \RuntimeException('Test error'));
        $this->setUpMockComponent($mockHealthCheckRunner);

        ob_start();
        $this->ajaxController->check();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
        $this->assertSame('application/json', $this->cmsApplication->getHeaders()['Content-Type']);
    }

    public function testCheckRejectsZeroAsSlug(): void
    {
        $this->setUpAuthorizedUser();
        $this->cmsApplication->setInput(new Input([
            'slug' => '0',
        ]));

        ob_start();
        $this->ajaxController->check();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
    }

    // =========================================================================
    // SUCCESS PATH TESTS - stats()
    // =========================================================================

    public function testStatsReturnsSuccessfulResponseWithoutCache(): void
    {
        $this->setUpAuthorizedUser();
        $this->cmsApplication->setInput(new Input([
            'cache' => 0,
        ]));

        $mockHealthCheckRunner = new MockHealthCheckRunner();
        $mockHealthCheckRunner->setCounts(1, 2, 10);
        $mockHealthCheckRunner->setLastRun(new \DateTimeImmutable('2026-01-23T10:00:00+00:00'));
        $this->setUpMockComponent($mockHealthCheckRunner);

        ob_start();
        $this->ajaxController->stats();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
        $this->assertSame('application/json', $this->cmsApplication->getHeaders()['Content-Type']);
    }

    public function testStatsReturnsSuccessfulResponseWithCache(): void
    {
        $this->setUpAuthorizedUser();
        $this->cmsApplication->setInput(new Input([
            'cache' => 1,
            'cache_ttl' => 900,
        ]));

        $mockHealthCheckRunner = new MockHealthCheckRunner();
        $mockHealthCheckRunner->setStatsWithCache([
            'critical' => 0,
            'warning' => 3,
            'good' => 15,
            'total' => 18,
            'lastRun' => '2026-01-23T10:00:00+00:00',
        ]);
        $this->setUpMockComponent($mockHealthCheckRunner);

        ob_start();
        $this->ajaxController->stats();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
        $this->assertSame('application/json', $this->cmsApplication->getHeaders()['Content-Type']);
    }

    public function testStatsHandlesZeroCacheTtl(): void
    {
        $this->setUpAuthorizedUser();
        $this->cmsApplication->setInput(new Input([
            'cache' => 1,
            'cache_ttl' => 0,
        ]));

        $mockHealthCheckRunner = new MockHealthCheckRunner();
        $mockHealthCheckRunner->setCounts(0, 0, 5);
        $mockHealthCheckRunner->setLastRun(null);
        $this->setUpMockComponent($mockHealthCheckRunner);

        ob_start();
        $this->ajaxController->stats();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
        $this->assertSame('application/json', $this->cmsApplication->getHeaders()['Content-Type']);
    }

    public function testStatsHandlesExceptionGracefully(): void
    {
        $this->setUpAuthorizedUser();
        $this->cmsApplication->setInput(new Input([]));

        $mockHealthCheckRunner = new MockHealthCheckRunner();
        $mockHealthCheckRunner->throwExceptionOn('run', new \RuntimeException('Test error'));
        $this->setUpMockComponent($mockHealthCheckRunner);

        ob_start();
        $this->ajaxController->stats();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
        $this->assertSame('application/json', $this->cmsApplication->getHeaders()['Content-Type']);
    }

    // =========================================================================
    // SUCCESS PATH TESTS - clearCache()
    // =========================================================================

    public function testClearCacheReturnsSuccessfulResponse(): void
    {
        $this->setUpAuthorizedUser();

        $mockHealthCheckRunner = new MockHealthCheckRunner();
        $this->setUpMockComponent($mockHealthCheckRunner);

        ob_start();
        $this->ajaxController->clearCache();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
        $this->assertSame('application/json', $this->cmsApplication->getHeaders()['Content-Type']);
    }

    public function testClearCacheHandlesExceptionGracefully(): void
    {
        $this->setUpAuthorizedUser();

        $mockHealthCheckRunner = new MockHealthCheckRunner();
        $mockHealthCheckRunner->throwExceptionOn('clearCache', new \RuntimeException('Test error'));
        $this->setUpMockComponent($mockHealthCheckRunner);

        ob_start();
        $this->ajaxController->clearCache();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
        $this->assertSame('application/json', $this->cmsApplication->getHeaders()['Content-Type']);
    }

    // =========================================================================
    // SUCCESS PATH TESTS - run()
    // =========================================================================

    public function testRunReturnsSuccessfulResponse(): void
    {
        $this->setUpAuthorizedUser();

        $mockHealthCheckRunner = new MockHealthCheckRunner();
        $mockHealthCheckRunner->setToArrayResult([
            'lastRun' => '2026-01-23T10:00:00+00:00',
            'summary' => [
                'critical' => 0,
                'warning' => 2,
                'good' => 10,
                'total' => 12,
            ],
            'categories' => [],
            'providers' => [],
            'results' => [],
        ]);
        $this->setUpMockComponent($mockHealthCheckRunner);

        ob_start();
        $this->ajaxController->run();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
        $this->assertSame('application/json', $this->cmsApplication->getHeaders()['Content-Type']);
    }

    public function testRunHandlesExceptionGracefully(): void
    {
        $this->setUpAuthorizedUser();

        $mockHealthCheckRunner = new MockHealthCheckRunner();
        $mockHealthCheckRunner->throwExceptionOn('run', new \RuntimeException('Test error'));
        $this->setUpMockComponent($mockHealthCheckRunner);

        ob_start();
        $this->ajaxController->run();
        ob_end_clean();

        $this->assertTrue($this->cmsApplication->isClosed());
        $this->assertSame('application/json', $this->cmsApplication->getHeaders()['Content-Type']);
    }
}
