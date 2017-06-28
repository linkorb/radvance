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
            $cmd = 'which -a  mysqldump';
            $process = new Process($cmd);

            try {
                $process->run();
                if (!trim($process->getOutput())) {
                    $output->writeLn('<error>`mysqldump` not exists in system </error>');
                    exit(1);
                }
            } catch (ProcessFailedException $e) {
                echo $e->getMessage();
                exit(1);
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
            } catch (ProcessFailedException $e) {
                echo $e->getMessage();
                exit(1);
            }

            //3. Comprase file
            $cmd = 'gzip '.$dumpSqlFile;
            $process = new Process($cmd);
            try {
                $process->mustRun();
                $zipFileName = $dumpSqlFile.'.gz';

                if (filesize($zipFileName)) {
                    $output->writeLn('<info>Snapshot created:</info> <comment>'.$zipFileName.' ('.$this->convertToReadableSize(filesize($zipFileName)).')</comment>');
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

    protected function convertToReadableSize($size)
    {
        $base = log($size) / log(1024);
        $suffix = array('', 'KB', 'MB', 'GB', 'TB');
        $f_base = floor($base);

        return round(pow(1024, $base - floor($base)), 1).$suffix[$f_base];
    }
}
