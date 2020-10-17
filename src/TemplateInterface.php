<?php

namespace Falgun\Template;

interface TemplateInterface
{

    public function view(string $viewFileName): self;

    /**
     * @param array<string, mixed> $attributes
     * @return \self
     */
    public function with(array $attributes = []): self;

    public function render(): void;

    public function setViewDirFromControllerPath(string $controller, string $viewParentDir): void;

    public function getViewAbsolutePath(): string;
}
