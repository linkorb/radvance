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

class MySQLConnectCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('mysql:connect')
            ->setDescription('Directly connect to MySQL based on configured credentials');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
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

        $partten = '/^(\w+)\:\/\/(\w+)\:(.+)\@([a-zA-Z0-9\.]+)\/(\w+)/';
        preg_match($partten, $pdo, $matches);

        if ($matches) {
            if ('mysql' == $matches[1] || 'mysqli' == $matches[1]) {
                $cmd = 'mysql -u '.$matches[2].' --password='.$matches[3].' -h '.$matches[4].' '.$matches[5];
            } else {
                $output->writeLn('<error>PDO is not mysql type</error>');
            }
        } else {
            $output->writeLn('<error>Cannot parse DSN</error>');
        }

        $output->write($cmd);
    }
}
