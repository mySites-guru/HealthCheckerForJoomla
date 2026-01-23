<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Component\Controller;

use MySitesGuru\HealthChecker\Component\Administrator\Controller\DisplayController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DisplayController::class)]
class DisplayControllerTest extends TestCase
{
    public function testDisplayControllerExtendsBaseController(): void
    {
        $controller = new DisplayController();

        $this->assertInstanceOf(\Joomla\CMS\MVC\Controller\BaseController::class, $controller);
    }

    public function testDisplayReturnsControllerInstance(): void
    {
        $controller = new DisplayController();

        $result = $controller->display();

        $this->assertInstanceOf(DisplayController::class, $result);
    }

    public function testDisplayWithCachableParameter(): void
    {
        $controller = new DisplayController();

        $result = $controller->display(true);

        $this->assertInstanceOf(DisplayController::class, $result);
    }

    public function testDisplayWithUrlParams(): void
    {
        $controller = new DisplayController();

        $result = $controller->display(false, [
            'view' => 'CMD',
        ]);

        $this->assertInstanceOf(DisplayController::class, $result);
    }

    public function testDisplayWithBothParameters(): void
    {
        $controller = new DisplayController();

        $result = $controller->display(true, [
            'view' => 'CMD',
            'layout' => 'CMD',
        ]);

        $this->assertInstanceOf(DisplayController::class, $result);
    }

    public function testDefaultViewIsReport(): void
    {
        $controller = new DisplayController();

        // Use reflection to check the protected property
        $reflection = new \ReflectionClass($controller);
        $property = $reflection->getProperty('default_view');

        $this->assertSame('report', $property->getValue($controller));
    }

    public function testDisplaySupportsMethodChaining(): void
    {
        $controller = new DisplayController();

        // Should be able to chain display calls
        $result = $controller->display()
            ->display();

        $this->assertInstanceOf(DisplayController::class, $result);
    }
}
