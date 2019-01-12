<?php

namespace Amenophis\Proxy\Pki;

use Amenophis\Proxy\Exception\PlatformNotSupported;
use Symfony\Component\Filesystem\Filesystem;

final class PkiFactory
{
    private function __construct() {}

    /**
     * @throws PlatformNotSupported
     */
    public static function create(string $platformName): Pki
    {
        $class = null;
        switch ($platformName) {
            case 'Darwin':
                return new Darwin(new Filesystem());
                break;
            case 'Linux':
                return new Linux(new Filesystem());
                break;
            case 'Windows':
            case 'BSD':
            case 'Solaris':
            case 'Unknown':
            default:
                throw new PlatformNotSupported($platformName);
                break;
        }
    }
}