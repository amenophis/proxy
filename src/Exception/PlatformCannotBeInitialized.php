<?php

namespace Amenophis\Proxy\Exception;

class PlatformCannotBeInitialized extends \Exception
{
    public function __construct(string $error)
    {
        parent::__construct(sprintf('Platform can\'t be initialized (%s)', $error));
    }
}