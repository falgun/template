<?php

namespace Falgun\Template;

use Exception;

class ViewFileNotFoundException extends Exception
{

    public function __construct(string $file)
    {
        parent::__construct($file . ' Not found', 500);
    }
}
