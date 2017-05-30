<?php

namespace Radvance\Command;

use Connector\Connector;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class SnapshotCreateCommand extends AbstractGeneratorCommand
{
    protected function configure()
    {
        $this
            ->setName('snapshot:create')
            ->setDescription('Snapshot create database dump file.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filename = 'app/config/parameters.yml';
        if (!file_exists($filename)) {
            throw new RuntimeException('No such file: '.$filename);
        }
        $data = file_get_contents($filename);
        $parameters = Yaml::parse($data);

        if (isset($parameters['pdo'])) {
            $pdoUrl = $parameters['pdo'];
        } else {
            if (!isset($parameters['parameters']['pdo'])) {
                throw new \RuntimeException("Can't find pdo configuration");
            }
            $pdoUrl = $parameters['parameters']['pdo'];
        }

        try {
            $connector = new Connector();
            $config = $connector->getConfig($pdoUrl);
            $dbname = $config->getName();

            $username = $config->getUsername();
            $password = $config->getPassword();

            // CRATE Directory //
            $directoryPath = '/var/snapshots/';
            $process = new Process('mkdir -p  0777 '.$directoryPath);
            $process->run();

            // Database Dump //
            $dumpFileName = $dbname.'-'.date('y-m-d-H-m-i').'.sql.gz';

            $process = new Process('/usr/bin/mysqldump --user='.$username.' --password='.$password.'  '.$dbname.' | gzip > '.$directoryPath.$dumpFileName);
            $process->run();

            // executes after the command finishes //
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
            chmod($directoryPath.$dumpFileName, 0775);
            echo $process->getOutput();
            $output->writeLn('<info>Snapshot Create: '.$directoryPath.$dumpFileName.'</info>');
        } catch (Exception $e) {
            $output->writeLn('<error>Fail to create Snapshot: '.$dbname.' </error>');
        }
    }
}
