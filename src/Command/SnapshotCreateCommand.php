<?php

namespace Radvance\Command;

use Connector\Connector;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

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
            $directoryPath = 'var/snapshots/';
            $process = new Process('mkdir -p  0777 '.$directoryPath);
            $process->run();

            //1. Before running mysqldump, check if that executable exists
            $cmd = 'command -v /usr/bin/mysqldump >/dev/null || { echo "false";}';
            $process = new Process($cmd);
            try {
                $process->mustRun();
                if (trim($process->getOutput()) == 'false') {
                    $output->writeLn('<error>`mysqldump` not exists in system </error>');
                    exit(1);
                }
            } catch (ProcessFailedException $e) {
                echo $e->getMessage();
            }

            //2. mysqldup in file and check its not empty and exists
            $dumpSqlFile = $directoryPath.$dbname.'-'.date('Y-m-d-H-i-s').'.sql';
            $cmd = 'mysqldump --user='.$username.' --password='.$password.' '.$dbname.'  > '.$dumpSqlFile;
            $process = new Process($cmd);
            try {
                $process->mustRun();

                if (!file_exists($dumpSqlFile) || !filesize($dumpSqlFile)) {
                    $output->writeLn('<error>'.$dumpSqlFile.'  Generate empty file </error>');
                    exit(1);
                }
                chmod($dumpSqlFile, 0777);
            } catch (ProcessFailedException $e) {
                echo $e->getMessage();
            }

            //3. Comprase file
            $cmd = 'gzip '.$dumpSqlFile;
            $process = new Process($cmd);
            try {
                $process->mustRun();
                $zipFileName = $dumpSqlFile.'.gz';
                chmod($zipFileName, 0777);
                if (filesize($zipFileName)) {
                    $output->writeLn('<info>Snapshot created:</info> <comment>'.$zipFileName.' ('.filesize($zipFileName).')</comment>');
                } else {
                    $output->writeLn('<error>Failed to compressed file</error>');
                }
            } catch (ProcessFailedException $e) {
                echo $e->getMessage();
            }
        } catch (Exception $e) {
            $output->writeLn('<error>Failed to create snapshot</error>');
        }
    }
}
