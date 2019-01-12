<?php

namespace Amenophis\Proxy\Exception;

class FileNotFound extends \Exception
{
    public function __construct(string $path)
    {
        parent::__construct(sprintf('File "%s" not found ', $path));
    }
}