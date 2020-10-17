<?php

namespace Falgun\Template;

use Exception;

class AttributeNotFoundException extends Exception
{

    public function __construct(string $attribute)
    {
        parent::__construct('"' . $attribute . '" Not found in template! Have you passed the data from controller?', 500);
    }
}
