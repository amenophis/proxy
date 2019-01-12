<?php

namespace Amenophis\Proxy\Command;

use Amenophis\Proxy\Exception\FileNotFound;
use Amenophis\Proxy\Exception\PlatformNotSupported;
use Amenophis\Proxy\Exception\UnableToInstallRootCertificate;
use Amenophis\Proxy\Pki\PkiFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CertificateGenerateCommand extends Command
{
    protected static $defaultName = 'certificate:generate';

    protected function configure()
    {
        $this
            ->setDescription('Generate a certificate using the existing CA')
            ->addArgument('domain', InputArgument::REQUIRED, 'Domain')
            ->addOption('validity', null, InputOption::VALUE_OPTIONAL, 'Validity in years for the CA Root certificate')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        try {
            $domain = $input->getArgument('domain');
            $validity = $input->getOption('validity') ?? 10;

            $pki = PkiFactory::create(PHP_OS_FAMILY);

            if (!$pki->isExistingCARoot()){
                $io->writeln('No CA generated. Use certificate:ca:install command before');
                return;
            }

            $io->writeln(sprintf('Generating certificate for domain "%s" ... ', $domain));
            $pki->generateCertificateForDomain($domain, $validity);
        } catch (PlatformNotSupported $e) {
            $io->error($e->getMessage());
        }
    }
}
