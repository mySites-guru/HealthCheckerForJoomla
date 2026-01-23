<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace Joomla\DI;

interface ServiceProviderInterface
{
    public function register(Container $container): void;
}

class Container
{
    public function set(string $key, callable|object|string $value, bool $shared = false): static
    {
        return $this;
    }

    public function get(string $key): mixed
    {
        return null;
    }

    public function has(string $key): bool
    {
        return false;
    }
}
