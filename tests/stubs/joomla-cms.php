<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace Joomla\CMS\Language;

class Text
{
    public static function _(string $string, mixed ...$args): string
    {
        // Return the language key for testing purposes
        return $string;
    }

    public static function sprintf(string $string, mixed ...$args): string
    {
        // Return the first arg if any, otherwise the key
        if ($args !== []) {
            return (string) $args[0];
        }

        return $string;
    }

    public static function script(string $string, bool $jsSafe = false, bool $interpretBackSlashes = true): void {}
}

namespace Joomla\CMS\Event\Result;

trait ResultAware
{
    // Note: The using class must define $arguments property
    // E.g., public $arguments; or protected array $arguments = [];

    abstract public function typeCheckResult(mixed $data): void;

    public function addResult(mixed $data): void
    {
        $this->typeCheckResult($data);

        if (! isset($this->arguments['result'])) {
            $this->arguments['result'] = [];
        }

        $this->arguments['result'][] = $data;
    }

    public function getArgument(string $name, mixed $default = null): mixed
    {
        return $this->arguments[$name] ?? $default;
    }
}

interface ResultAwareInterface
{
    public function addResult(mixed $data): void;

    public function typeCheckResult(mixed $data): void;

    public function getArgument(string $name, mixed $default = null): mixed;
}

namespace Joomla\CMS;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;

class Factory
{
    private static ?CMSApplication $application = null;

    public static function setApplication(?CMSApplication $app): void
    {
        self::$application = $app;
    }

    public static function getApplication(): CMSApplication
    {
        if (self::$application !== null) {
            return self::$application;
        }

        return new CMSApplication();
    }

    public static function getSession(): Session
    {
        return new Session();
    }

    public static function getContainer(): mixed
    {
        return null;
    }

    public static function getConfig(): mixed
    {
        return null;
    }

    public static function getUser(mixed $id = null): mixed
    {
        return null;
    }

    public static function getLanguage(): mixed
    {
        return null;
    }

    public static function getDocument(): mixed
    {
        return null;
    }

    public static function getDbo(): DatabaseInterface
    {
        return new class implements DatabaseInterface {
            public function getQuery(bool $new = false): \Joomla\Database\QueryInterface
            {
                return new class implements \Joomla\Database\QueryInterface {
                    public function select(array|string $columns): self
                    {
                        return $this;
                    }

                    public function from(string $table, ?string $alias = null): self
                    {
                        return $this;
                    }

                    public function where(array|string $conditions): self
                    {
                        return $this;
                    }

                    public function join(string $type, string $table, string $condition = ''): self
                    {
                        return $this;
                    }

                    public function leftJoin(string $table, string $condition = ''): self
                    {
                        return $this;
                    }

                    public function innerJoin(string $table, string $condition = ''): self
                    {
                        return $this;
                    }

                    public function order(array|string $columns): self
                    {
                        return $this;
                    }

                    public function group(array|string $columns): self
                    {
                        return $this;
                    }

                    public function having(array|string $conditions): self
                    {
                        return $this;
                    }

                    public function __toString(): string
                    {
                        return '';
                    }
                };
            }

            public function setQuery(\Joomla\Database\QueryInterface|string $query): self
            {
                return $this;
            }

            public function loadResult(): mixed
            {
                return null;
            }

            public function loadColumn(): array
            {
                return [];
            }

            public function loadAssoc(): ?array
            {
                return null;
            }

            public function loadAssocList(): array
            {
                return [];
            }

            public function loadObject(): ?object
            {
                return null;
            }

            public function loadObjectList(): array
            {
                return [];
            }

            public function execute(): bool
            {
                return false;
            }

            public function quoteName(array|string $name, ?string $as = null): array|string
            {
                return '';
            }

            public function quote(array|string $text, bool $escape = true): array|string
            {
                return '';
            }

            public function getPrefix(): string
            {
                return '';
            }

            public function getNullDate(): string
            {
                return '0000-00-00 00:00:00';
            }

            public function getTableList(): array
            {
                return [];
            }

            public function getVersion(): string
            {
                return '8.0.30';
            }
        };
    }
}

namespace Joomla\CMS\Application;

use Joomla\CMS\User\User;
use Joomla\Input\Input;

class CMSApplication
{
    private array $config = [];

    private ?User $identity = null;

    private ?Input $input = null;

    private array $headers = [];

    private bool $closed = false;

    public function input(): mixed
    {
        return $this->input;
    }

    public function getInput(): Input
    {
        if ($this->input === null) {
            $this->input = new Input();
        }

        return $this->input;
    }

    public function setInput(Input $input): void
    {
        $this->input = $input;
    }

    public function getIdentity(): ?User
    {
        return $this->identity;
    }

    public function setIdentity(?User $identity): void
    {
        $this->identity = $identity;
    }

    public function setHeader(string $name, string $value, bool $replace = false): void
    {
        $this->headers[$name] = $value;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function close(int $code = 0): void
    {
        $this->closed = true;
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }

    public function bootComponent(string $component): mixed
    {
        return null;
    }

    public function enqueueMessage(string $msg, string $type = 'info'): void {}

    public function redirect(string $url, int $status = 303): void {}

    public function get(string $name, mixed $default = null): mixed
    {
        return $this->config[$name] ?? $default;
    }

    public function set(string $name, mixed $value): mixed
    {
        $old = $this->config[$name] ?? null;
        $this->config[$name] = $value;
        return $old;
    }
}

namespace Joomla\CMS\User;

class User
{
    public int $id = 0;

    public string $name = '';

    public string $username = '';

    public string $email = '';

    private array $authorisations = [];

    public function __construct(int $id = 0)
    {
        $this->id = $id;
    }

    public function authorise(string $action, ?string $assetname = null): bool
    {
        $key = $action . ':' . ($assetname ?? '');

        return $this->authorisations[$key] ?? false;
    }

    public function setAuthorisation(string $action, ?string $assetname, bool $allowed): void
    {
        $key = $action . ':' . ($assetname ?? '');
        $this->authorisations[$key] = $allowed;
    }
}

namespace Joomla\Input;

class Input
{
    private array $data = [];

    public function __construct(array $source = [])
    {
        $this->data = $source;
    }

    public function get(string $name, mixed $default = null, string $filter = 'cmd'): mixed
    {
        return $this->data[$name] ?? $default;
    }

    public function getString(string $name, string $default = ''): string
    {
        return (string) ($this->data[$name] ?? $default);
    }

    public function getInt(string $name, int $default = 0): int
    {
        return (int) ($this->data[$name] ?? $default);
    }

    public function set(string $name, mixed $value): void
    {
        $this->data[$name] = $value;
    }
}

namespace Joomla\CMS\Session;

class Session
{
    private static bool $tokenValid = true;

    public static function setTokenValid(bool $valid): void
    {
        self::$tokenValid = $valid;
    }

    public function get(string $name, mixed $default = null, string $namespace = 'default'): mixed
    {
        return null;
    }

    public function set(string $name, mixed $value, string $namespace = 'default'): mixed
    {
        return null;
    }

    public function has(string $name, string $namespace = 'default'): bool
    {
        return false;
    }

    public function clear(string $name, string $namespace = 'default'): mixed
    {
        return null;
    }

    public static function checkToken(string $method = 'post'): bool
    {
        return self::$tokenValid;
    }

    public static function getFormToken(bool $forceNew = false): string
    {
        return '';
    }
}

namespace Joomla\CMS\Response;

class JsonResponse implements \Stringable
{
    public function __construct(mixed $data = null, ?string $message = null, bool $error = false) {}

    public function __toString(): string
    {
        return '';
    }
}

namespace Joomla\CMS\MVC\Controller;

class BaseController
{
    protected mixed $input;

    protected mixed $app;

    public function execute(string $task): mixed
    {
        return null;
    }

    public function redirect(): bool
    {
        return false;
    }

    public function display(bool $cacheable = false, array $urlparams = []): static
    {
        return $this;
    }
}

namespace Joomla\CMS\MVC\Model;

class BaseDatabaseModel
{
    protected mixed $db;

    public function getState(?string $property = null, mixed $default = null): mixed
    {
        return null;
    }

    public function setState(string $property, mixed $value = null): mixed
    {
        return null;
    }
}

namespace Joomla\CMS\MVC\View;

class HtmlView
{
    protected mixed $document;

    public function display(?string $tpl = null): void {}
}

class JsonView
{
    public function display(?string $tpl = null): void {}
}

namespace Joomla\CMS\Extension;

class MVCComponent
{
    public function __construct(mixed $dispatcher = null) {}

    public function getDispatcher(): mixed
    {
        return null;
    }
}

namespace Joomla\CMS\Plugin;

class CMSPlugin
{
    protected mixed $db;

    protected mixed $app;

    public function __construct(mixed $subject, array $config = []) {}

    public function setApplication(mixed $app): void
    {
        $this->app = $app;
    }
}

class PluginHelper
{
    public static function isEnabled(string $type, ?string $plugin = null): bool
    {
        return false;
    }

    public static function importPlugin(string $folder = '', ?string $plugin = null): void {}

    public static function getPlugin(string $type, ?string $plugin = null): mixed
    {
        return null;
    }
}

namespace Joomla\CMS\Extension;

interface PluginInterface {}

interface ModuleInterface {}

namespace Joomla\CMS\Component;

use Joomla\Registry\Registry;

class ComponentHelper
{
    public static function getParams(string $name): Registry
    {
        return new Registry();
    }
}

namespace Joomla\CMS\Toolbar;

class ToolbarHelper
{
    public static function title(string $title, string $icon = ''): void {}

    public static function preferences(string $component): void {}
}

class Toolbar
{
    public static function getInstance(string $name = 'toolbar'): static
    {
        return new static();
    }
}

namespace Joomla\CMS\Router;

class Route
{
    public static function _(string $url, bool $xhtml = true, ?int $ssl = null): string
    {
        return '';
    }
}

namespace Joomla\CMS\Uri;

class Uri
{
    public static function root(bool $pathonly = false, int $path = 0): string
    {
        return '';
    }

    public static function getInstance(string $uri = 'SERVER'): static
    {
        return new static();
    }

    public function isSsl(): bool
    {
        return false;
    }
}

namespace Joomla\CMS;

class Version
{
    public function getShortVersion(): string
    {
        return '5.0.0';
    }
}

namespace Joomla\CMS\Http;

class HttpFactory
{
    public static function getHttp(): mixed
    {
        return null;
    }
}

namespace Joomla\CMS\Extension;

interface BootableExtensionInterface
{
    public function boot(\Psr\Container\ContainerInterface $container): void;
}

namespace Joomla\CMS\Cache;

interface CacheControllerFactoryInterface
{
    public function createCacheController(string $type, array $options = []): mixed;
}

namespace Joomla\CMS\Extension;

interface ComponentInterface {}

namespace Joomla\CMS\Dispatcher;

interface ComponentDispatcherFactoryInterface {}

namespace Joomla\CMS\MVC\Factory;

interface MVCFactoryInterface {}

namespace Joomla\CMS\Extension\Service\Provider;

class MVCFactory
{
    public function __construct(string $namespace) {}
}

class ComponentDispatcherFactory
{
    public function __construct(string $namespace) {}
}

class ModuleDispatcherFactory
{
    public function __construct(string $namespace) {}
}

class HelperFactory
{
    public function __construct(string $namespace) {}
}

class Module
{
    public function __construct(?\Joomla\CMS\Extension\ModuleInterface $module = null) {}
}

namespace Joomla\Registry;

class Registry
{
    public function get(string $path, mixed $default = null): mixed
    {
        return null;
    }
}

namespace Joomla\CMS\HTML;

class HTMLHelper
{
    public static function _(string $type, mixed ...$args): mixed
    {
        return null;
    }
}
