<?php
declare(strict_types=1);

namespace Falgun\Template;

use Falgun\Http\Response;
use Falgun\Pagination\PaginationInterface;

abstract class AbstractTemplate extends Response implements TemplateInterface
{

    protected string $viewFile;
    protected string $viewDir;
    protected array $attributes;
    protected PaginationInterface $pagination;

    public function view(string $viewFileName): self
    {
        $this->viewFile = $viewFileName . '.php';

        return $this;
    }

    abstract public function preRender(): void;

    abstract public function postRender(): void;

    public final function render(): void
    {
        $this->preRender();

        $viewPath = $this->getViewAbsolutePath();

        if (\file_exists($viewPath)) {
            require $viewPath;
        } else {
            throw new ViewFileNotFoundException($viewPath);
        }

        $this->PostRender();
    }

    public function with(array $attributes = []): self
    {
        $this->attributes = $attributes;

        return $this;
    }

    public function setViewDir(string $viewDir): self
    {
        $this->viewDir = rtrim($viewDir, '/');

        return $this;
    }

    public function setViewDirFromControllerPath(string $controller, string $viewParentDir): void
    {
        $viewFolder = $this->getViewFolderNameFromController($controller);
        $viewAbsoluteDir = rtrim($viewParentDir, '/') . '/' . $viewFolder;

        $this->viewDir = $viewAbsoluteDir;

        return;
    }

    private function getViewFolderNameFromController(string $controller): string
    {
        $lastBackslashPosition = \strrpos($controller, '\\');
        $controllerBaseName = \trim(\substr($controller, $lastBackslashPosition), '\\');

        return \str_replace('Controller', null, $controllerBaseName);
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

    public function __set($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    public function __get($name)
    {
        if (\array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }

        throw new \Exception($name . ' Attribute not found !');
    }

    public function __isset($name)
    {
        return \array_key_exists($name, $this->attributes);
    }

    public function __call($name, $arguments)
    {
        // we will implement extension system later
        throw new \Exception('invalid method call : ' . $name);
    }

    public function e($value, $flags = \ENT_QUOTES | \ENT_HTML5, $encoding = 'UTF-8'): string
    {
        return $this->escape($value, $flags, $encoding);
    }

    public function escape($value, $flags = \ENT_QUOTES | \ENT_HTML5, $encoding = 'UTF-8'): string
    {
        return \htmlspecialchars((string) $value, $flags, $encoding);
    }

    public function escapeJson($value)
    {
        return \json_encode($value, \JSON_HEX_QUOT | \JSON_HEX_TAG | \JSON_HEX_AMP | \JSON_HEX_APOS);
    }

    public function csrfToken(): string
    {
        return '<input type="hidden" value="' . ($_SESSION['csrf_token'] ?? '') . '" name="csrf_token" />';
    }

    public function pagination(PaginationInterface $pagination): self
    {
        $this->pagination = $pagination;

        return $this;
    }

    public function value($name, $default = false)
    {
        if (\array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }

        return $default;
    }

    public function objProperty($objName, $property, $default = false)
    {
        if (isset($this->attributes[$objName]->$property)) {
            return $this->attributes[$objName]->$property;
        }

        return $default;
    }
}
