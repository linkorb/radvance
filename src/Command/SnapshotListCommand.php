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
            ->setDescription('List existing snapshots')
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

        $directoryPath = 'var/snapshots/';

        try {
            $connector = new Connector();
            $config = $connector->getConfig($pdoUrl);
            $dbname = $config->getName();

            // dump list //
            $files = array();
            $c = 0;
            if ($handle = opendir($directoryPath)) {
                while (false !== ($file = readdir($handle))) {
                    if ($file != '.' && $file != '..' && preg_match('/^'.$dbname.'/i', $file)) {
                        //$files[filemtime($file)] = $file;
                        $files[] = $file;
                    }
                }

                foreach ($files as $file) {
                    $c++;
                    $output->writeLn('* <comment>' . $file  . '</comment>');
                }
            }
            if ($c==0) {
                $output->writeLn('<error>No snapshots found</error>');
            }

        } catch (Exception $e) {
            $output->writeLn('<error>Error listing snapshots</error>');
        }
    }
}
