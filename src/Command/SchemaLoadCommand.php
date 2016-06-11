<?php

namespace Radvance\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Process\Exception\ProcessFailedException;
use RuntimeException;

class SchemaLoadCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('schema:load')
            ->setDescription('Uses dbtk-schema-loader to update db schema')
            ->addOption(
                'apply',
                null,
                InputOption::VALUE_NONE,
                'Apply allow you to synchronise schema'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $apply = $input->getOption('apply');
        $filename = 'app/config/parameters.yml';
        if (!file_exists($filename)) {
            throw new RuntimeException("No such file: " . $filename);
        }
        $data = file_get_contents($filename);
        $config = Yaml::parse($data);
        
        if (isset($config['pdo'])) {
            $pdo = $config['pdo'];
        } else {
            if (!isset($config['parameters']['pdo'])) {
                throw new RuntimeException("Can't find pdo configuration");
            }
            $pdo = $config['parameters']['pdo'];
        }
        
        $cmd = 'vendor/bin/dbtk-schema-loader schema:load app/schema.xml ' . $pdo;
        if ($apply) {
            $cmd .= ' --apply';
        }
        
        $process = new Process($cmd);
        $output->writeLn($process->getCommandLine());
        $process->run();
        
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        $output->write($process->getOutput());
    }
}
