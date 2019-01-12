<?php

namespace Amenophis\Proxy\Command;

use Amenophis\Proxy\Exception\FileNotFound;
use Amenophis\Proxy\Exception\PlatformNotSupported;
use Amenophis\Proxy\Exception\UnableToInstallRootCertificate;
use Amenophis\Proxy\Exception\UnableToUninstallRootCertificate;
use Amenophis\Proxy\Pki\PkiFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CertificateCaUninstallCommand extends Command
{
    protected static $defaultName = 'certificate:ca:uninstall';

    protected function configure()
    {
        $this
            ->setDescription('Uninstall the CA into system keystore')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        try {
            $pki = PkiFactory::create(PHP_OS_FAMILY);

            if (!$pki->isExistingCARoot()){
                $io->writeln('CA not installed.');
                return;
            }

            $io->writeln('Uninstalling CA ... ');
            $pki->uninstallCARootCertificate();

            $pki->removeCACertificate();
        } catch (PlatformNotSupported|FileNotFound|UnableToUninstallRootCertificate $e) {
            $io->error($e->getMessage());
        }
    }
}
