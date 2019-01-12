<?php

namespace Amenophis\Proxy\Pki;

use Amenophis\Proxy\Exception\UnableToInstallRootCertificate;
use Amenophis\Proxy\Exception\UnableToUninstallRootCertificate;
use Symfony\Component\Process\Process;

class Darwin extends Pki
{
    public function getHome(): string
    {
        $home = getenv('HOME');
        if (empty($home)) {
            return '';
        }

        return rtrim($home, '/').'/Library/Application Support';
    }

    public function installCARootCertificate(): void
    {
        $this->ensureFileExists($this->rootCertificatePath);

        $p = new Process([
            'sudo',
            'security',
            'add-trusted-cert',
            '-d',
            '-p',
            'ssl',
            '-p',
            'basic',
            '-k',
            '/Library/Keychains/System.keychain',
            $this->rootCertificatePath
        ]);

        if (0 !== $p->run()) {
            throw new UnableToInstallRootCertificate($p->getErrorOutput());
        }
    }

    public function uninstallCARootCertificate(): void
    {
        $this->ensureFileExists($this->rootCertificatePath);

        $p = new Process([
            'sudo',
            'security',
            'remove-trusted-cert',
            '-d',
            $this->rootCertificatePath
        ]);

        if (0 !== $p->run()) {
            throw new UnableToUninstallRootCertificate($p->getErrorOutput());
        }
    }
}