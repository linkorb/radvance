<?php

namespace Radvance\Command;

use Connector\Connector;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class SnapshotLoadCommand extends AbstractGeneratorCommand
{
    protected function configure()
    {
        $this
            ->setName('snapshot:load')
            ->addArgument(
                'snapshotFilename',
                InputArgument::REQUIRED,
                'file full path for import'
            )
            ->setDescription('Snapshot load file into datbase.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $snapshotFilename = $input->getArgument('snapshotFilename');

        if (!file_exists($snapshotFilename)) {
            throw new \RuntimeException('file not found.');
        }

        $filename = 'app/config/parameters.yml';
        if (!file_exists($filename)) {
            throw new \RuntimeException('No such file: '.$filename);
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

            if ($connector->exists($config)) {
                $connector->drop($config);
                $output->writeLn('<info>Drop Database: '.$dbname.'</info>');
            }
            $connector->create($config);
            $output->writeLn('<info>Create Database: '.$dbname.'</info>');

            // Database import //
            $process = new Process('gunzip <  '.$snapshotFilename.'  | mysql --user='.$username.' --password='.$password.'  '.$dbname);
            $process->run();

            // executes after the command finishes //
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
            echo $process->getOutput();
            $output->writeLn('<info>Snapshot load  successfully: '.$snapshotFilename.'</info>');
        } catch (Exception $e) {
            $output->writeLn('<error>Fail to load Snapshot: '.$snapshotFilename.' </error>');
        }
    }
}
