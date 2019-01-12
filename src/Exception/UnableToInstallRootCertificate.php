<?php

namespace Amenophis\Proxy\Exception;

class UnableToInstallRootCertificate extends \Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}