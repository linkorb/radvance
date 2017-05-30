<?php

namespace Radvance\Command;

use Connector\Connector;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SnapshotListCommand extends AbstractGeneratorCommand
{
    protected function configure()
    {
        $this
            ->setName('snapshot:list')
            ->setDescription('Snapshot file list.')
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

            // dump list //
            $directoryPath = '/var/snapshots/';
            $files = array();
            if ($handle = opendir($directoryPath)) {
                while (false !== ($file = readdir($handle))) {
                    if ($file != '.' && $file != '..' && preg_match('/^'.$dbname.'/i', $file)) {
                        //$files[filemtime($file)] = $file;
                        $files[] = $file;
                    }
                }

                foreach ($files as $file) {
                    echo $directoryPath.$file."\n\r";
                }
            }
            $output->writeLn('<info>Snapshot list :'.$dbname.' </info>');
        } catch (Exception $e) {
            $output->writeLn('<error>Fail to find list : '.$dbname.' </error>');
        }
    }
}
