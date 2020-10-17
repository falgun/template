<?php
declare(strict_types=1);

namespace Falgun\Template;

use Falgun\Http\Session;
use Falgun\Http\Response;
use Falgun\Pagination\PaginationInterface;

abstract class AbstractTemplate extends Response implements TemplateInterface
{

    protected string $viewFile;
    protected string $viewDir;

    /** @var array<string, mixed> */
    protected array $attributes;
    protected ?Session $session;
    protected ?PaginationInterface $pagination;

    public function __construct()
    {
        parent::__construct();

        $this->viewDir = '';
        $this->viewFile = '';

        $this->attributes = [];
        $this->session = null;
        $this->pagination = null;
    }

    public function view(string $viewFileName): self
    {
        $this->viewFile = $viewFileName . '.php';

        return $this;
    }

    abstract public function preRender(): void;

    abstract public function postRender(): void;

    /**
     * @return void
     * @throws ViewFileNotFoundException
     * @psalm-suppress UnresolvableInclude
     */
    public final function render(): void
    {
        $this->preRender();

        $viewPath = $this->getViewAbsolutePath();

        if (\is_file($viewPath) === false) {
            throw new ViewFileNotFoundException($viewPath);
        }

        require $viewPath;

        $this->PostRender();
    }

    /**
     * @param array<string, mixed> $attributes
     * @return \self
     */
    public function with(array $attributes = []): self
    {
        $this->attributes = $attributes;

        return $this;
    }

    public function setViewDir(string $viewDir): void
    {
        $this->viewDir = \rtrim($viewDir, '/');
    }

    public function setViewDirFromControllerPath(string $controller, string $viewParentDir): void
    {
        $viewFolder = $this->getViewFolderNameFromController($controller);
        $viewAbsoluteDir = \rtrim($viewParentDir, '/') . '/' . $viewFolder;

        $this->viewDir = $viewAbsoluteDir;

        return;
    }

    private function getViewFolderNameFromController(string $controller): string
    {
        $lastBackslashPosition = (int) \strrpos($controller, '\\');
        $controllerBaseName = \trim(\substr($controller, $lastBackslashPosition), '\\');

        return \str_replace('Controller', '', $controllerBaseName);
    }

    public function getViewDir(): string
    {
        return $this->viewDir;
    }

    public function getViewFile(): string
    {
        return $this->viewFile;
    }

    public function getViewAbsolutePath(): string
    {
        return $this->viewDir . '/' . $this->viewFile;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set(string $name, $value): void
    {
        $this->attributes[$name] = $value;
    }

    /**
     * @param string $name
     * @return mixed
     * @throws AttributeNotFoundException
     */
    public function __get(string $name)
    {
        if (\array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }

        throw new AttributeNotFoundException($name);
    }

    public function __isset(string $name): bool
    {
        return \array_key_exists($name, $this->attributes);
    }

    /**
     * @param string $name
     * @param array<int, mixed> $arguments
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function __call(string $name, array $arguments)
    {
        // we will implement extension system later
        throw new \InvalidArgumentException('invalid method call : ' . $name);
    }

    /**
     * escape value for printing on webpage
     * @param mixed $value
     * @param int $flags
     * @param string $encoding
     * @return string
     */
    public function e($value, int $flags = \ENT_QUOTES | \ENT_HTML5, string $encoding = 'UTF-8'): string
    {
        return $this->escape($value, $flags, $encoding);
    }

    /**
     * escape value for printing on webpage
     * @param mixed $value
     * @param int $flags
     * @param string $encoding
     * @return string
     */
    public function escape($value, int $flags = \ENT_QUOTES | \ENT_HTML5, string $encoding = 'UTF-8'): string
    {
        return \htmlspecialchars((string) $value, $flags, $encoding);
    }

    /**
     * @param mixed $value
     * @return string|false
     */
    public function escapeJson($value)
    {
        return \json_encode($value, \JSON_HEX_QUOT | \JSON_HEX_TAG | \JSON_HEX_AMP | \JSON_HEX_APOS);
    }

    /**
     * @return string
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function csrfToken(): string
    {
        if (isset($this->session) === false) {
            throw new \InvalidArgumentException('You need to assign Session object in template class constructor', 500);
        }

        if ($this->session->has('falgun_csrf_token') === false) {
            throw new \RuntimeException('CSRF TOKEN is not found in session, have you loaded the middleware?', 403);
        }

        return '<input type="hidden" value="' . ($this->session->get('falgun_csrf_token')) . '" name="csrf_token" />';
    }

    public function pagination(PaginationInterface $pagination): self
    {
        $this->pagination = $pagination;

        return $this;
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function value(string $name, $default = false)
    {
        if (\array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }

        return $default;
    }

    /**
     * get value of property of a object
     * or $default otherwise
     * @param string $objName
     * @param string $property
     * @param mixed $default
     * @return mixed
     */
    public function objProperty(string $objName, string $property, $default = false)
    {
        if (isset($this->attributes[$objName]->$property)) {
            return $this->attributes[$objName]->$property;
        }

        return $default;
    }
}
