<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Component\Model;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Model\ReportModel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ReportModel::class)]
class ReportModelTest extends TestCase
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

        // Set up a mock application
        $cmsApplication = new CMSApplication();
        Factory::setApplication($cmsApplication);
    }

    protected function tearDown(): void
    {
        // Restore original application
        Factory::setApplication($this->cmsApplication);

        parent::tearDown();
    }

    public function testModelCanBeInstantiated(): void
    {
        $reportModel = new ReportModel();

        $this->assertInstanceOf(ReportModel::class, $reportModel);
    }

    public function testModelExtendsBaseDatabaseModel(): void
    {
        $reportModel = new ReportModel();

        $this->assertInstanceOf(\Joomla\CMS\MVC\Model\BaseDatabaseModel::class, $reportModel);
    }

    public function testRunChecksMethodExists(): void
    {
        $this->assertTrue(method_exists(ReportModel::class, 'runChecks'));
    }

    public function testRunChecksMethodReturnsVoid(): void
    {
        $reflectionMethod = new \ReflectionMethod(ReportModel::class, 'runChecks');
        $returnType = $reflectionMethod->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('void', $returnType->getName());
    }

    public function testGetRunnerMethodExists(): void
    {
        $this->assertTrue(method_exists(ReportModel::class, 'getRunner'));
    }

    public function testGetRunnerMethodIsPublic(): void
    {
        $reflectionMethod = new \ReflectionMethod(ReportModel::class, 'getRunner');

        $this->assertTrue($reflectionMethod->isPublic());
    }

    public function testGetResultsMethodExists(): void
    {
        $this->assertTrue(method_exists(ReportModel::class, 'getResults'));
    }

    public function testGetResultsReturnType(): void
    {
        $reflectionMethod = new \ReflectionMethod(ReportModel::class, 'getResults');
        $returnType = $reflectionMethod->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('array', $returnType->getName());
    }

    public function testGetResultsByCategoryMethodExists(): void
    {
        $this->assertTrue(method_exists(ReportModel::class, 'getResultsByCategory'));
    }

    public function testGetResultsByCategoryReturnType(): void
    {
        $reflectionMethod = new \ReflectionMethod(ReportModel::class, 'getResultsByCategory');
        $returnType = $reflectionMethod->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('array', $returnType->getName());
    }

    public function testGetFilteredResultsMethodExists(): void
    {
        $this->assertTrue(method_exists(ReportModel::class, 'getFilteredResults'));
    }

    public function testGetFilteredResultsAcceptsNullParameters(): void
    {
        $reflectionMethod = new \ReflectionMethod(ReportModel::class, 'getFilteredResults');
        $parameters = $reflectionMethod->getParameters();

        $this->assertCount(2, $parameters);

        // Both parameters should allow null
        $this->assertTrue($parameters[0]->allowsNull());
        $this->assertTrue($parameters[1]->allowsNull());
    }

    public function testGetFilteredResultsParameterNames(): void
    {
        $reflectionMethod = new \ReflectionMethod(ReportModel::class, 'getFilteredResults');
        $parameters = $reflectionMethod->getParameters();

        $this->assertSame('statusFilter', $parameters[0]->getName());
        $this->assertSame('categoryFilter', $parameters[1]->getName());
    }

    public function testGetCriticalCountMethodExists(): void
    {
        $this->assertTrue(method_exists(ReportModel::class, 'getCriticalCount'));
    }

    public function testGetCriticalCountReturnType(): void
    {
        $reflectionMethod = new \ReflectionMethod(ReportModel::class, 'getCriticalCount');
        $returnType = $reflectionMethod->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('int', $returnType->getName());
    }

    public function testGetWarningCountMethodExists(): void
    {
        $this->assertTrue(method_exists(ReportModel::class, 'getWarningCount'));
    }

    public function testGetWarningCountReturnType(): void
    {
        $reflectionMethod = new \ReflectionMethod(ReportModel::class, 'getWarningCount');
        $returnType = $reflectionMethod->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('int', $returnType->getName());
    }

    public function testGetGoodCountMethodExists(): void
    {
        $this->assertTrue(method_exists(ReportModel::class, 'getGoodCount'));
    }

    public function testGetGoodCountReturnType(): void
    {
        $reflectionMethod = new \ReflectionMethod(ReportModel::class, 'getGoodCount');
        $returnType = $reflectionMethod->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('int', $returnType->getName());
    }

    public function testGetTotalCountMethodExists(): void
    {
        $this->assertTrue(method_exists(ReportModel::class, 'getTotalCount'));
    }

    public function testGetTotalCountReturnType(): void
    {
        $reflectionMethod = new \ReflectionMethod(ReportModel::class, 'getTotalCount');
        $returnType = $reflectionMethod->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('int', $returnType->getName());
    }

    public function testGetLastRunMethodExists(): void
    {
        $this->assertTrue(method_exists(ReportModel::class, 'getLastRun'));
    }

    public function testGetLastRunReturnTypeAllowsNull(): void
    {
        $reflectionMethod = new \ReflectionMethod(ReportModel::class, 'getLastRun');
        $returnType = $reflectionMethod->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertTrue($returnType->allowsNull());
    }

    public function testToJsonMethodExists(): void
    {
        $this->assertTrue(method_exists(ReportModel::class, 'toJson'));
    }

    public function testToJsonReturnType(): void
    {
        $reflectionMethod = new \ReflectionMethod(ReportModel::class, 'toJson');
        $returnType = $reflectionMethod->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('string', $returnType->getName());
    }

    public function testModelHasCorrectNamespace(): void
    {
        $reflectionClass = new \ReflectionClass(ReportModel::class);

        $this->assertSame(
            'MySitesGuru\HealthChecker\Component\Administrator\Model',
            $reflectionClass->getNamespaceName(),
        );
    }

    public function testModelIsNotAbstract(): void
    {
        $reflectionClass = new \ReflectionClass(ReportModel::class);

        $this->assertFalse($reflectionClass->isAbstract());
    }

    public function testModelIsNotFinal(): void
    {
        $reflectionClass = new \ReflectionClass(ReportModel::class);

        $this->assertFalse($reflectionClass->isFinal());
    }

    public function testHealthCheckRunnerPropertyExists(): void
    {
        $reflectionClass = new \ReflectionClass(ReportModel::class);

        $this->assertTrue($reflectionClass->hasProperty('healthCheckRunner'));
    }

    public function testHealthCheckRunnerPropertyIsPrivate(): void
    {
        $reflectionProperty = new \ReflectionProperty(ReportModel::class, 'healthCheckRunner');

        $this->assertTrue($reflectionProperty->isPrivate());
    }

    public function testHealthCheckRunnerPropertyAllowsNull(): void
    {
        $reflectionProperty = new \ReflectionProperty(ReportModel::class, 'healthCheckRunner');
        $type = $reflectionProperty->getType();

        $this->assertNotNull($type);
        $this->assertTrue($type->allowsNull());
    }
}
