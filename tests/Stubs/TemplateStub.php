<?php
declare(strict_types=1);

namespace Falgun\Template\Tests\Stubs;

use Falgun\Template\AbstractTemplate;
use Falgun\Pagination\PaginationInterface;

final class TemplateStub extends AbstractTemplate
{

    public bool $loadedPreRender;
    public bool $loadedPostRender;

    public function __construct(PaginationInterface $pagination)
    {
        parent::__construct();

        $this->pagination = $pagination;
    }

    public function postRender(): void
    {
        $this->loadedPostRender = true;
    }

    public function preRender(): void
    {
        $this->loadedPreRender = true;
    }
}
