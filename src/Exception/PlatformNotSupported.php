<?php

namespace Amenophis\Proxy\Exception;

class PlatformNotSupported extends \Exception
{
    public function __construct(string $platform)
    {
        parent::__construct(sprintf('Platform "%s" not supported', $platform));
    }
}