<?php

namespace Amenophis\Proxy\Exception;

class UnableToUninstallRootCertificate extends \Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}