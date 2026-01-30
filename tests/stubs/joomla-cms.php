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
use Joomla\Event\DispatcherInterface;
use Joomla\Input\Input;

class CMSApplication
{
    private array $config = [];

    private ?User $identity = null;

    private ?Input $input = null;

    private array $headers = [];

    private bool $closed = false;

    private ?DispatcherInterface $dispatcher = null;

    /**
     * @var array<string, mixed> Map of component names to component instances for testing
     */
    private array $components = [];

    public function input(): mixed
    {
        return $this->input;
    }

    /**
     * Set a component instance for testing bootComponent() calls
     */
    public function setComponent(string $name, mixed $component): void
    {
        $this->components[$name] = $component;
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
        return $this->components[$component] ?? null;
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

    public function getDispatcher(): DispatcherInterface
    {
        if ($this->dispatcher === null) {
            $this->dispatcher = new class implements DispatcherInterface {
                public function dispatch(
                    string $name,
                    ?\Joomla\Event\EventInterface $event = null,
                ): \Joomla\Event\EventInterface {
                    return $event ?? new \Joomla\Event\Event($name);
                }
            };
        }

        return $this->dispatcher;
    }

    public function setDispatcher(DispatcherInterface $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
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

use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class HtmlView
{
    protected mixed $document;

    private ?BaseDatabaseModel $model = null;

    public function display(?string $tpl = null): void {}

    public function getModel(?string $name = null): ?BaseDatabaseModel
    {
        return $this->model;
    }

    public function setModel(BaseDatabaseModel $model): void
    {
        $this->model = $model;
    }
}

class JsonView
{
    private ?BaseDatabaseModel $model = null;

    public function display(?string $tpl = null): void {}

    public function getModel(?string $name = null): ?BaseDatabaseModel
    {
        return $this->model;
    }

    public function setModel(BaseDatabaseModel $model): void
    {
        $this->model = $model;
    }
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

use Joomla\Database\DatabaseInterface;

class CMSPlugin
{
    protected ?DatabaseInterface $db = null;

    protected mixed $app;

    /**
     * Plugin parameters - public property used by plugins
     * Note: No type hint to allow child classes to override without type
     *
     * @var mixed
     */
    public $params;

    /**
     * Constructor supports both legacy and modern Joomla plugin patterns
     *
     * Legacy: new CMSPlugin($dispatcher, $config)
     * Modern: new CMSPlugin(dispatcher: $dispatcher, pluginParams: $params)
     */
    public function __construct(
        mixed $subject = null,
        array $config = [],
        mixed $dispatcher = null,
        mixed $pluginParams = null,
    ) {
        // Handle modern named parameter style
        if ($dispatcher !== null) {
            $subject = $dispatcher;
        }

        if ($pluginParams !== null) {
            $this->params = $pluginParams;
        }
    }

    public function setApplication(mixed $app): void
    {
        $this->app = $app;
    }
}

class PluginHelper
{
    /**
     * @var array<string, bool>
     */
    private static array $enabledPlugins = [];

    public static function isEnabled(string $type, ?string $plugin = null): bool
    {
        $key = $type . ':' . ($plugin ?? '');

        return self::$enabledPlugins[$key] ?? false;
    }

    /**
     * Set plugin enabled status for testing
     */
    public static function setEnabled(string $type, ?string $plugin, bool $enabled): void
    {
        $key = $type . ':' . ($plugin ?? '');
        self::$enabledPlugins[$key] = $enabled;
    }

    /**
     * Reset all plugin enabled statuses for test isolation
     */
    public static function resetEnabled(): void
    {
        self::$enabledPlugins = [];
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
    /**
     * @var array<string, Registry>
     */
    private static array $params = [];

    public static function getParams(string $name): Registry
    {
        return self::$params[$name] ?? new Registry();
    }

    public static function setParams(string $name, Registry $params): void
    {
        self::$params[$name] = $params;
    }

    public static function resetParams(): void
    {
        self::$params = [];
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
    private static array $instances = [];

    private array $buttons = [];

    public static function getInstance(string $name = 'toolbar'): static
    {
        if (! isset(self::$instances[$name])) {
            self::$instances[$name] = new static();
        }

        return self::$instances[$name];
    }

    public static function clearInstances(): void
    {
        self::$instances = [];
    }

    public function standardButton(string $type, string $text = ''): ToolbarButton
    {
        $button = new ToolbarButton($type, $text);
        $this->buttons[] = $button;

        return $button;
    }

    public function dropdownButton(string $name): ToolbarDropdownButton
    {
        $button = new ToolbarDropdownButton($name);
        $this->buttons[] = $button;

        return $button;
    }

    public function linkButton(string $name): ToolbarButton
    {
        $button = new ToolbarButton($name);
        $this->buttons[] = $button;

        return $button;
    }

    public function getButtons(): array
    {
        return $this->buttons;
    }
}

class ToolbarButton
{
    private string $type;

    private string $text;

    private string $icon = '';

    private string $onclick = '';

    private string $buttonClass = '';

    private string $url = '';

    private array $attributes = [];

    public function __construct(string $type, string $text = '')
    {
        $this->type = $type;
        $this->text = $text;
    }

    public function text(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function icon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function onclick(string $onclick): self
    {
        $this->onclick = $onclick;

        return $this;
    }

    public function buttonClass(string $class): self
    {
        $this->buttonClass = $class;

        return $this;
    }

    public function url(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function attributes(array $attributes): self
    {
        $this->attributes = $attributes;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getText(): string
    {
        return $this->text;
    }
}

class ToolbarDropdownButton
{
    private string $name;

    private string $text = '';

    private bool $toggleSplit = true;

    private string $icon = '';

    private string $buttonClass = '';

    private ?Toolbar $childToolbar = null;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->childToolbar = new Toolbar();
    }

    public function text(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function toggleSplit(bool $split): self
    {
        $this->toggleSplit = $split;

        return $this;
    }

    public function icon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function buttonClass(string $class): self
    {
        $this->buttonClass = $class;

        return $this;
    }

    public function getChildToolbar(): Toolbar
    {
        return $this->childToolbar;
    }

    public function getName(): string
    {
        return $this->name;
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
    private static bool $mockSsl = false;

    public static function setMockSsl(bool $ssl): void
    {
        self::$mockSsl = $ssl;
    }

    public static function resetMockSsl(): void
    {
        self::$mockSsl = false;
    }

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
        return self::$mockSsl;
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

use Joomla\Http\Response;

/**
 * HTTP client class that mimics Joomla\CMS\Http\Http
 */
class Http
{
    public function get(string $url, array $headers = [], int|float $timeout = 10): Response
    {
        return new Response(0, '', []);
    }

    public function head(string $url, array $headers = [], int|float $timeout = 10): Response
    {
        return new Response(0, '', []);
    }

    public function post(string $url, mixed $data = '', array $headers = [], int|float $timeout = 10): Response
    {
        return new Response(0, '', []);
    }

    public function put(string $url, mixed $data = '', array $headers = [], int|float $timeout = 10): Response
    {
        return new Response(0, '', []);
    }

    public function delete(string $url, array $headers = [], int|float $timeout = 10): Response
    {
        return new Response(0, '', []);
    }

    public function patch(string $url, mixed $data = '', array $headers = [], int|float $timeout = 10): Response
    {
        return new Response(0, '', []);
    }
}

class HttpFactory
{
    private static ?Http $mockHttp = null;

    public static function setMockHttp(?Http $http): void
    {
        self::$mockHttp = $http;
    }

    public static function getHttp(): Http
    {
        if (self::$mockHttp !== null) {
            return self::$mockHttp;
        }

        return new Http();
    }
}

namespace Joomla\Http;

interface HttpInterface
{
    public function get(string $url, array $headers = [], int|float $timeout = 10): Response;

    public function head(string $url, array $headers = [], int|float $timeout = 10): Response;

    public function post(string $url, $data = '', array $headers = [], int|float $timeout = 10): Response;

    public function put(string $url, $data = '', array $headers = [], int|float $timeout = 10): Response;

    public function delete(string $url, array $headers = [], int|float $timeout = 10): Response;

    public function patch(string $url, $data = '', array $headers = [], int|float $timeout = 10): Response;
}

class Response
{
    public int $code;

    public string $body;

    /**
     * @var array<string, string|array<string>>
     */
    public array $headers;

    /**
     * @param array<string, string|array<string>> $headers
     */
    public function __construct(int $code = 200, string $body = '', array $headers = [])
    {
        $this->code = $code;
        $this->body = $body;
        $this->headers = $headers;
    }
}

namespace Joomla\CMS\Extension;

interface BootableExtensionInterface
{
    public function boot(\Psr\Container\ContainerInterface $container): void;
}

namespace Joomla\CMS\Log;

/**
 * Joomla Log class stub for PHPStan
 */
class Log
{
    public const ALL = 30719;

    public const EMERGENCY = 1;

    public const ALERT = 2;

    public const CRITICAL = 4;

    public const ERROR = 8;

    public const WARNING = 16;

    public const NOTICE = 32;

    public const INFO = 64;

    public const DEBUG = 128;

    public static function add(
        string $message,
        int $priority = self::INFO,
        string $category = '',
        ?string $date = null,
    ): void {
        // Stub - does nothing in tests
    }

    public static function addLogger(
        array $options,
        int $priorities = self::ALL,
        array|string $categories = [],
        bool $exclude = false,
    ): void {
        // Stub - does nothing in tests
    }
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
    private array $data = [];

    public function __construct(mixed $data = null)
    {
        if (is_string($data) && $data !== '') {
            $decoded = json_decode($data, true);
            if (is_array($decoded)) {
                $this->data = $decoded;
            }
        } elseif (is_array($data)) {
            $this->data = $data;
        }
    }

    public function get(string $path, mixed $default = null): mixed
    {
        return $this->data[$path] ?? $default;
    }

    public function set(string $path, mixed $value): mixed
    {
        $previous = $this->data[$path] ?? null;
        $this->data[$path] = $value;
        return $previous;
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

namespace Joomla\CMS\Dispatcher;

use Joomla\CMS\Application\CMSApplication;
use Joomla\Registry\Registry;

abstract class AbstractModuleDispatcher
{
    protected ?CMSApplication $app = null;

    protected array $module = [];

    protected ?Registry $params = null;

    public function __construct(\stdClass $module, CMSApplication $app)
    {
        $this->app = $app;
        $this->module = (array) $module;
        $this->params = new Registry($module->params ?? '{}');
    }

    protected function getApplication(): CMSApplication
    {
        return $this->app;
    }

    protected function getLayoutData(): array
    {
        return [
            'params' => $this->params,
            'module' => (object) $this->module,
            'app' => $this->app,
        ];
    }

    public function dispatch(): void
    {
        // Base dispatch - renders template
    }
}

namespace Joomla\Filter;

/**
 * InputFilter stub for testing HTML sanitization.
 *
 * This implements a basic version of Joomla's InputFilter that strips
 * HTML tags not in the allowed list.
 */
class InputFilter
{
    public const ONLY_ALLOW_DEFINED_TAGS = 0;

    public const ONLY_BLOCK_DEFINED_TAGS = 1;

    public const ONLY_ALLOW_DEFINED_ATTRIBUTES = 0;

    public const ONLY_BLOCK_DEFINED_ATTRIBUTES = 1;

    /**
     * @var string[]
     */
    private array $tagsArray;

    /**
     * @var string[]
     */
    private array $attrArray;

    private int $tagsMethod;

    private int $attrMethod;

    private int $xssAuto;

    /**
     * @param string[] $tagsArray
     * @param string[] $attrArray
     */
    public function __construct(
        array $tagsArray = [],
        array $attrArray = [],
        int $tagsMethod = self::ONLY_ALLOW_DEFINED_TAGS,
        int $attrMethod = self::ONLY_ALLOW_DEFINED_ATTRIBUTES,
        int $xssAuto = 1,
    ) {
        $this->tagsArray = array_map('strtolower', $tagsArray);
        $this->attrArray = array_map('strtolower', $attrArray);
        $this->tagsMethod = $tagsMethod;
        $this->attrMethod = $attrMethod;
        $this->xssAuto = $xssAuto;
    }

    /**
     * Clean input based on type.
     */
    public function clean(mixed $source, string $type = 'string'): mixed
    {
        if (! is_string($source)) {
            return $source;
        }

        if ($type === 'html') {
            return $this->cleanHtml($source);
        }

        return $source;
    }

    /**
     * Clean HTML, allowing only whitelisted tags and no attributes.
     *
     * This stub mimics Joomla's InputFilter behavior where dangerous tags
     * like <script>, <style>, and <iframe> have their content completely removed,
     * not just the tags themselves.
     */
    private function cleanHtml(string $source): string
    {
        // First strip any script, style, and iframe content entirely (including content)
        // These are greedy patterns that consume everything between opening and closing tags
        $source = (string) preg_replace('/<script\b[^>]*>[\s\S]*?<\/script>/i', '', $source);
        $source = (string) preg_replace('/<style\b[^>]*>[\s\S]*?<\/style>/i', '', $source);
        $source = (string) preg_replace('/<iframe\b[^>]*>[\s\S]*?<\/iframe>/i', '', $source);

        // Remove event handlers (onclick, onerror, onload, etc.)
        $source = (string) preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '', $source);
        $source = (string) preg_replace('/\s+on\w+\s*=\s*[^\s>]*/i', '', $source);

        // Remove javascript: and data: URLs
        $source = (string) preg_replace('/javascript\s*:/i', '', $source);
        $source = (string) preg_replace('/data\s*:/i', '', $source);

        // Remove all attributes from tags (we don't allow any)
        if ($this->attrMethod === self::ONLY_ALLOW_DEFINED_ATTRIBUTES && $this->attrArray === []) {
            $source = (string) preg_replace_callback(
                '/<(\w+)([^>]*)>/i',
                function (array $matches): string {
                    // Keep only the tag name, strip all attributes
                    return '<' . $matches[1] . '>';
                },
                $source,
            );
        }

        // Strip tags not in whitelist
        if ($this->tagsMethod === self::ONLY_ALLOW_DEFINED_TAGS) {
            // Build allowed tags string for strip_tags
            $allowedTagsStr = '';
            foreach ($this->tagsArray as $tag) {
                $allowedTagsStr .= '<' . $tag . '>';
            }

            $source = strip_tags($source, $allowedTagsStr);
        }

        return $source;
    }
}

namespace Joomla\CMS\Helper;

interface HelperFactoryAwareInterface
{
    public function setHelperFactory(HelperFactoryInterface $helperFactory): void;

    public function getHelperFactory(): HelperFactoryInterface;
}

interface HelperFactoryInterface
{
    public function getHelper(string $name, array $config = []): mixed;
}

trait HelperFactoryAwareTrait
{
    protected ?HelperFactoryInterface $helperFactory = null;

    public function setHelperFactory(HelperFactoryInterface $helperFactory): void
    {
        $this->helperFactory = $helperFactory;
    }

    public function getHelperFactory(): HelperFactoryInterface
    {
        if ($this->helperFactory === null) {
            throw new \UnexpectedValueException('Helper factory not set');
        }

        return $this->helperFactory;
    }
}
