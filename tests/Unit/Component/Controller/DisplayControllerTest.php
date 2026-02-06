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
        $displayController = new DisplayController();

        $this->assertInstanceOf(\Joomla\CMS\MVC\Controller\BaseController::class, $displayController);
    }

    public function testDisplayReturnsControllerInstance(): void
    {
        $displayController = new DisplayController();

        $result = $displayController->display();

        $this->assertInstanceOf(DisplayController::class, $result);
    }

    public function testDisplayWithCachableParameter(): void
    {
        $displayController = new DisplayController();

        $result = $displayController->display(true);

        $this->assertInstanceOf(DisplayController::class, $result);
    }

    public function testDisplayWithUrlParams(): void
    {
        $displayController = new DisplayController();

        $result = $displayController->display(false, [
            'view' => 'CMD',
        ]);

        $this->assertInstanceOf(DisplayController::class, $result);
    }

    public function testDisplayWithBothParameters(): void
    {
        $displayController = new DisplayController();

        $result = $displayController->display(true, [
            'view' => 'CMD',
            'layout' => 'CMD',
        ]);

        $this->assertInstanceOf(DisplayController::class, $result);
    }

    public function testDefaultViewIsReport(): void
    {
        $displayController = new DisplayController();

        // Use reflection to check the protected property
        $reflectionClass = new \ReflectionClass($displayController);
        $reflectionProperty = $reflectionClass->getProperty('default_view');

        $this->assertSame('report', $reflectionProperty->getValue($displayController));
    }

    public function testDisplaySupportsMethodChaining(): void
    {
        $displayController = new DisplayController();

        // Should be able to chain display calls
        $result = $displayController->display()
            ->display();

        $this->assertInstanceOf(DisplayController::class, $result);
    }
}
