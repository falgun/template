<?php
declare(strict_types=1);

namespace Falgun\Template\Tests\Stubs;

use Falgun\Http\Session;
use Falgun\Template\AbstractTemplate;
use Falgun\Pagination\PaginationInterface;

final class TemplateStub extends AbstractTemplate
{

    public bool $loadedPreRender;
    public bool $loadedPostRender;

    public function __construct(Session $session, PaginationInterface $pagination)
    {
        parent::__construct();

        $this->session = $session;
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
