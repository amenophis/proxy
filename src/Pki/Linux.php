<?php

namespace Amenophis\Proxy\Pki;

use Amenophis\Proxy\Exception\FileNotFound;
use Amenophis\Proxy\Exception\PlatformCannotBeInitialized;
use Amenophis\Proxy\Exception\PlatformNotSupported;
use Amenophis\Proxy\Exception\UnableToInstallRootCertificate;
use Amenophis\Proxy\Exception\UnableToUninstallRootCertificate;
use phpseclib\File\X509;
use Symfony\Component\Process\Process;

class Linux extends Pki
{
    /**
     * @var string
     */
    private $systemTrustFilename;

    /**
     * @var string[]
     */
    private $systemTrustCommand;

    public function getHome(): string
    {
        $home = getenv('XDG_DATA_HOME') ?: getenv('HOME') ?: null;
        if (empty($home)) {
            return '';
        }

        return rtrim($home, '/').'/.local/share';
    }

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        if ($this->filesystem->exists('/usr/local/share/ca-certificates/')) {
            // Ubuntu, Debian
            $this->systemTrustFilename = '/usr/local/share/ca-certificates/amenophis_proxy_%s.crt';
            $this->systemTrustCommand = ['sudo', 'update-ca-certificates'];
        } else if ($this->filesystem->exists('/etc/pki/ca-trust/source/anchors/')) {
            // RHEL
            $this->systemTrustFilename = '/etc/pki/ca-trust/source/anchors/amenophis_proxy_%s.pem';
            $this->systemTrustCommand = ['sudo', 'update-ca-trust', 'extract'];
        } else if ($this->filesystem->exists('/etc/ca-certificates/trust-source/anchors/')) {
            // Arch
            $this->systemTrustFilename = '/etc/ca-certificates/trust-source/anchors/amenophis_proxy_%s.crt';
            $this->systemTrustCommand = ['sudo', 'trust', 'extract-compat'];
        }

        if ($this->systemTrustCommand) {
            if (!$path = strtok(exec('which '.escapeshellarg($this->systemTrustCommand[1])), PHP_EOL)) {
               throw new PlatformCannotBeInitialized('Unable to guess distribution');
            }
        }
    }

    private function getSystemTrustFilename(): ?string
    {
        try {
            $certificate = $this->getCACertificate();

            return sprintf($this->systemTrustFilename, $certificate->getDN(X509::DN_HASH));
        } catch (FileNotFound $e) {
            return null;
        }
    }

    public function installCARootCertificate(): void
    {
        $this->ensureFileExists($this->rootCertificatePath);

        $p1 = new Process(['sudo', 'tee', $this->getSystemTrustFilename()]);
        $p1->setInput(file_get_contents($this->rootCertificatePath));
        $p1->run();
        if (0 !== $p1->run()) {
            throw new UnableToInstallRootCertificate($p1->getErrorOutput());
        }

        $p2 = new Process($this->systemTrustCommand);
        $p2->run();
        if (0 !== $p2->run()) {
            throw new UnableToInstallRootCertificate($p2->getErrorOutput());
        }
    }

    public function uninstallCARootCertificate(): void
    {
        $this->ensureFileExists($this->rootCertificatePath);

        $p1 = new Process(['sudo', 'rm', '-f', $this->getSystemTrustFilename()]);
        $p1->setInput(file_get_contents($this->rootCertificatePath));
        $p1->run();
        if (0 !== $p1->run()) {
            throw new UnableToUninstallRootCertificate($p1->getErrorOutput());
        }

        $p2 = new Process($this->systemTrustCommand);
        $p2->run();
        if (0 !== $p2->run()) {
            throw new UnableToUninstallRootCertificate($p2->getErrorOutput());
        }
    }
}