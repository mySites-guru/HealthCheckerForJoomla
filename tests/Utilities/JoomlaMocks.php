<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace Joomla\Database;

/**
 * Mock DatabaseInterface for testing
 */
interface DatabaseInterface
{
    // Minimal interface for testing
}

namespace Joomla\Event;

/**
 * Mock Event class for testing
 */
class Event
{
    protected string $name;

    public $arguments;

    public function __construct(string $name, array $arguments = [])
    {
        $this->name = $name;
        $this->arguments = $arguments;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }
}

namespace Joomla\CMS\Event\Result;

/**
 * Mock ResultAware trait for testing
 */
trait ResultAware
{
    abstract public function typeCheckResult(mixed $data): void;

    public function addResult(mixed $data): void
    {
        $this->typeCheckResult($data);

        if (! isset($this->arguments['result'])) {
            $this->arguments['result'] = [];
        }

        $this->arguments['result'][] = $data;
    }
}

/**
 * Mock ResultAwareInterface for testing
 */
interface ResultAwareInterface
{
    public function addResult(mixed $data): void;

    public function typeCheckResult(mixed $data): void;
}
