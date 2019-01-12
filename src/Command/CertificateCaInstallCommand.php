<?php

namespace Amenophis\Proxy\Command;

use Amenophis\Proxy\Exception\FileNotFound;
use Amenophis\Proxy\Exception\PlatformNotSupported;
use Amenophis\Proxy\Exception\UnableToInstallRootCertificate;
use Amenophis\Proxy\Pki\PkiFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CertificateCaInstallCommand extends Command
{
    protected static $defaultName = 'certificate:ca:install';

    protected function configure()
    {
        $this
            ->setDescription('Install the CA into system keystore')
            ->addOption('organization', 'o', InputOption::VALUE_OPTIONAL, 'Organization (O) field in the CA Root certificate')
            ->addOption('organization-unit', 'ou', InputOption::VALUE_OPTIONAL, 'OrganizationUnit (OU) field in the CA Root certificate')
            ->addOption('common-name', 'cn', InputOption::VALUE_OPTIONAL, 'CommonName (CN) field in the CA Root certificate')
            ->addOption('validity', null, InputOption::VALUE_OPTIONAL, 'Validity in years for the CA Root certificate')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        try {
            $organization = $input->getOption('organization') ?? 'Amenophis';
            $organizationUnit = $input->getOption('organization-unit') ?? get_current_user().'@'.gethostname();
            $commonName = $input->getOption('common-name') ?? $organization.' '.$organizationUnit;
            $validity = $input->getOption('validity') ?? 10;

            $pki = PkiFactory::create(PHP_OS_FAMILY);

            if (!$pki->isExistingCARoot()){
                $io->writeln('Generating CA ... ');
                $pki->generateCARootCertificate($organization, $organizationUnit, $commonName, $validity);
            } else {
                $io->writeln('CA already present');
            }

            $io->writeln('Registering CA ... ');
            $pki->installCARootCertificate();
        } catch (PlatformNotSupported|FileNotFound|UnableToInstallRootCertificate $e) {
            $io->error($e->getMessage());
        }
    }
}
