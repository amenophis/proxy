<?php

namespace Amenophis\Proxy\Pki;

use Amenophis\Proxy\Exception\FileNotFound;
use Amenophis\Proxy\Exception\PlatformCannotBeInitialized;
use Amenophis\Proxy\Exception\UnableToInstallRootCertificate;
use Amenophis\Proxy\Exception\UnableToUninstallRootCertificate;
use phpseclib\Crypt\RSA;
use phpseclib\File\X509;
use Symfony\Component\Filesystem\Filesystem;

abstract class Pki
{
    private const ROOT_KEY_NAME = 'rootCA-key.pem';
    private const ROOT_CERTIFICATE_NAME = 'rootCA.pem';

    protected $certPath;
    protected $rootPrivateKeyPath;
    protected $rootCertificatePath;
    protected $filesystem;

    /**
     * @throws PlatformCannotBeInitialized
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->certPath = rtrim($this->getHome(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'amenophis'.DIRECTORY_SEPARATOR.'proxy'.DIRECTORY_SEPARATOR;
        $this->rootPrivateKeyPath = $this->certPath.self::ROOT_KEY_NAME;
        $this->rootCertificatePath = $this->certPath.self::ROOT_CERTIFICATE_NAME;
        $this->filesystem = $filesystem;

        $this->init();
    }

    /**
     * @throws PlatformCannotBeInitialized
     */
    public function init(): void
    {

    }

    abstract public function getHome(): string;

    /**
     * @throws FileNotFound
     * @throws UnableToInstallRootCertificate
     */
    abstract public function installCARootCertificate(): void;

    /**
     * @throws FileNotFound
     * @throws UnableToUninstallRootCertificate
     */
    abstract public function uninstallCARootCertificate(): void;

    public function isExistingCARoot(): bool
    {
        return $this->filesystem->exists([
            $this->rootPrivateKeyPath,
            $this->rootCertificatePath,
        ]);
    }

    /**
     * @throws FileNotFound
     */
    public function getCACertificate(): X509
    {
        $this->ensureFileExists($this->rootCertificatePath);

        $certificate = new X509();
        $certificate->loadX509(file_get_contents($this->rootCertificatePath));

        return $certificate;
    }

    public function generateCARootCertificate(string $organization, string $organizationUnit, string $commonName, int $validity): void
    {
        $keys = (new RSA())->createKey(3072);
        $privateKey = new RSA();
        $privateKey->loadKey($keys['privatekey']);

        $publicKey = new RSA();
        $publicKey->loadKey($keys['publickey']);
        $publicKey->setPublicKey();

        $subject = new X509();
        $subject->setPublicKey($publicKey);
        $subject->setDNProp('o', $organization);
        $subject->setDNProp('ou', $organizationUnit);
        $subject->setDNProp('cn', $commonName);

        $issuer = new X509();
        $issuer->setPrivateKey($privateKey);
        $issuer->setDN($subject->getDN());

        $x509 = new X509();
        $x509->setStartDate(new \DateTime());
        $x509->setEndDate(sprintf('+%d year', $validity));
        $x509->makeCA();

        $certificateInfo = $x509->sign($issuer, $subject, 'sha256WithRSAEncryption');
        $certificatePem = $x509->saveX509($certificateInfo);

        $this->filesystem->dumpFile($this->rootPrivateKeyPath, $privateKey->getPrivateKey());
        $this->filesystem->dumpFile($this->rootCertificatePath, $certificatePem);
        $this->filesystem->chmod($this->rootPrivateKeyPath, 0400);
        $this->filesystem->chmod($this->rootCertificatePath, 0644);
    }

    /**
     * @throws FileNotFound
     */
    public function removeCACertificate()
    {
        $this->ensureFileExists($this->rootCertificatePath);
        $this->ensureFileExists($this->rootPrivateKeyPath);

        $this->filesystem->remove([$this->rootCertificatePath, $this->rootPrivateKeyPath]);
    }

    public function generateCertificateForDomain(string $domain, int $validity): void
    {
        $rootCertificate = $this->getCACertificate();

        $privateKey = new RSA();
        $privateKey->loadKey(file_get_contents($this->rootPrivateKeyPath));

        $publicKey = new RSA();
        $publicKey->loadKey($privateKey->getPublicKey());

        $caCertificate = new X509();
        $caCertificate->loadX509(file_get_contents($this->rootCertificatePath));

        $certificateKeys = (new RSA())->createKey(3072);
        $certificatePrivateKey = new RSA();
        $certificatePrivateKey->loadKey($certificateKeys['privatekey']);

        $certificatePublicKey = new RSA();
        $certificatePublicKey->loadKey($certificateKeys['publickey']);
        $certificatePublicKey->setPublicKey();

        $subject = new X509();
        $subject->setPublicKey($certificatePublicKey);
        $subject->setDNProp('o', $rootCertificate->getDNProp('o')[0]);
        $subject->setDNProp('ou', $rootCertificate->getDNProp('ou')[0]);

        $issuer = new X509();
        $issuer->setPrivateKey($privateKey);
        $issuer->setDN($caCertificate->getSubjectDN());

        $x509 = new X509();
        $x509->setStartDate(new \DateTime());
        $x509->setEndDate(sprintf('+%d year', $validity));

        $result = $x509->sign($issuer, $subject, 'sha256WithRSAEncryption');
        $x509->loadX509($result);

        $issuer->setKeyIdentifier($issuer->computeKeyIdentifier($issuer));

        $x509->setExtension('id-ce-keyUsage', ['digitalSignature', 'keyEncipherment'], true, true);
        $x509->setExtension('id-ce-extKeyUsage', ['id-kp-serverAuth'], false, true);
        $x509->setExtension('id-ce-basicConstraints', ['cA' => false], true, true);
        $x509->setExtension('id-ce-subjectAltName', [['dNSName' => $domain]], false, true);

        $certificateInfo = $x509->sign($issuer, $x509, 'sha256WithRSAEncryption');
        $certificatePem = $x509->saveX509($certificateInfo);

        $path = $this->certPath.DIRECTORY_SEPARATOR.$domain;

        $this->filesystem->dumpFile($path.'-key.pem', $certificatePrivateKey->getPrivateKey());
        $this->filesystem->dumpFile($path.'.pem', $certificatePem);

        $this->filesystem->chmod($path.'-key.pem', 0400);
        $this->filesystem->chmod($path.'.pem', 0644);
    }

    /**
     * @throws FileNotFound
     */
    protected function ensureFileExists(string $filePath): void
    {
        if (!$this->filesystem->exists($filePath)) {
            throw new FileNotFound($filePath);
        }
    }
}