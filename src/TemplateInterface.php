<?php

namespace Falgun\Template;

interface TemplateInterface
{

    public function view(string $viewFileName): self;

    public function with(array $args = []): self;

    public function render(): void;
}
