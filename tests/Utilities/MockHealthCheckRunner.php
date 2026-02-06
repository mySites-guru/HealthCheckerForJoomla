<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Utilities;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Provider\ProviderRegistry;
use MySitesGuru\HealthChecker\Component\Administrator\Service\CategoryRegistry;

/**
 * Mock HealthCheckRunner for testing AjaxController
 *
 * This is a manual test double because HealthCheckRunner is final and cannot
 * be mocked by PHPUnit. This class provides configurable behaviors for testing.
 */
class MockHealthCheckRunner
{
    private ?array $metadata = null;

    private ?array $categoryResults = null;

    private ?HealthCheckResult $healthCheckResult = null;

    private int $criticalCount = 0;

    private int $warningCount = 0;

    private int $goodCount = 0;

    private int $totalCount = 0;

    private ?\DateTimeImmutable $lastRun = null;

    private ?array $statsWithCache = null;

    private ?array $toArrayResult = null;

    private ?\Exception $exception = null;

    private string $methodToThrowOn = '';

    /**
     * Configure an exception to be thrown on a specific method call
     */
    public function throwExceptionOn(string $method, \Exception $exception): self
    {
        $this->methodToThrowOn = $method;
        $this->exception = $exception;

        return $this;
    }

    private function maybeThrow(string $method): void
    {
        if ($this->methodToThrowOn === $method && $this->exception instanceof \Exception) {
            throw $this->exception;
        }
    }

    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        $this->maybeThrow('getMetadata');

        return $this->metadata ?? [
            'categories' => [],
            'providers' => [],
            'checks' => [],
        ];
    }

    public function setCategoryResults(array $results): self
    {
        $this->categoryResults = $results;

        return $this;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function runCategory(string $category): array
    {
        $this->maybeThrow('runCategory');

        return $this->categoryResults ?? [];
    }

    public function setSingleCheckResult(?HealthCheckResult $healthCheckResult): self
    {
        $this->healthCheckResult = $healthCheckResult;

        return $this;
    }

    public function runSingleCheck(string $slug): ?HealthCheckResult
    {
        $this->maybeThrow('runSingleCheck');

        return $this->healthCheckResult;
    }

    public function setCounts(int $critical, int $warning, int $good): self
    {
        $this->criticalCount = $critical;
        $this->warningCount = $warning;
        $this->goodCount = $good;
        $this->totalCount = $critical + $warning + $good;

        return $this;
    }

    public function setLastRun(?\DateTimeImmutable $lastRun): self
    {
        $this->lastRun = $lastRun;

        return $this;
    }

    public function run(): void
    {
        $this->maybeThrow('run');
    }

    public function getCriticalCount(): int
    {
        return $this->criticalCount;
    }

    public function getWarningCount(): int
    {
        return $this->warningCount;
    }

    public function getGoodCount(): int
    {
        return $this->goodCount;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    public function getLastRun(): ?\DateTimeImmutable
    {
        return $this->lastRun;
    }

    public function setStatsWithCache(array $stats): self
    {
        $this->statsWithCache = $stats;

        return $this;
    }

    /**
     * @return array{critical: int, warning: int, good: int, total: int, lastRun: ?string}
     */
    public function getStatsWithCache(?int $cacheTtl = null): array
    {
        $this->maybeThrow('getStatsWithCache');

        return $this->statsWithCache ?? [
            'critical' => $this->criticalCount,
            'warning' => $this->warningCount,
            'good' => $this->goodCount,
            'total' => $this->totalCount,
            'lastRun' => $this->lastRun?->format('c'),
        ];
    }

    public function clearCache(): void
    {
        $this->maybeThrow('clearCache');
    }

    public function setToArrayResult(array $result): self
    {
        $this->toArrayResult = $result;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $this->maybeThrow('toArray');

        return $this->toArrayResult ?? [
            'lastRun' => $this->lastRun?->format('c'),
            'summary' => [
                'critical' => $this->criticalCount,
                'warning' => $this->warningCount,
                'good' => $this->goodCount,
                'total' => $this->totalCount,
            ],
            'categories' => [],
            'providers' => [],
            'results' => [],
        ];
    }

    // These methods exist on the real runner but aren't used by AjaxController directly
    public function getCategoryRegistry(): CategoryRegistry
    {
        return new CategoryRegistry();
    }

    public function getProviderRegistry(): ProviderRegistry
    {
        return new ProviderRegistry();
    }
}
