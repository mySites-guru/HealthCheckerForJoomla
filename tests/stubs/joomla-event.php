<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace Joomla\Event;

class Event implements EventInterface
{
    protected string $name = '';

    public $arguments = [];

    public function __construct(string $name = '', array $arguments = [])
    {
        $this->name = $name;
        $this->arguments = $arguments;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getArgument(string $name, mixed $default = null): mixed
    {
        return null;
    }

    public function hasArgument(string $name): bool
    {
        return false;
    }

    public function getArguments(): array
    {
        return [];
    }

    public function isStopped(): bool
    {
        return false;
    }

    public function stopPropagation(): void {}
}

interface EventInterface
{
    public function getName(): string;

    public function getArgument(string $name, mixed $default = null): mixed;

    public function hasArgument(string $name): bool;

    public function getArguments(): array;

    public function isStopped(): bool;

    public function stopPropagation(): void;
}

interface SubscriberInterface
{
    public static function getSubscribedEvents(): array;
}

interface DispatcherInterface
{
    public function dispatch(string $name, EventInterface $event = null): EventInterface;
}
