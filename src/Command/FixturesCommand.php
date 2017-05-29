<?php

namespace Radvance\Command;

use Connector\Connector;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Process\Process;

class FixturesCommand extends AbstractGeneratorCommand
{
    protected function configure()
    {
        $this
            ->setName('fixture:run')
            ->setDescription('Drop exists Database and re-generate with Fixtures data.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // ASK USER INPUT //
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Are you sure you want to delete all data?[y/n]', false);

        if (!$helper->ask($input, $output, $question)) {
            return;
        }

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
                throw new RuntimeException("Can't find pdo configuration");
            }
            $pdoUrl = $parameters['parameters']['pdo'];
        }

        try {
            $connector = new Connector();
            $config = $connector->getConfig($pdoUrl);
            $dbname = $config->getName();

            if ($connector->exists($config)) {
                $connector->drop($config);
                $output->writeLn('<info>Drop Database: '.$dbname.'</info>');
            }
            $connector->create($config);
            $output->writeLn('<info>Create Database: '.$dbname.'</info>');

            // load schema //
            $output->writeLn('<info>Loading Schema...</info>');
            $process = new Process('vendor/bin/dbtk-schema-loader  schema:load app/schema.xml  '.$pdoUrl.' --apply');
            $process->run();
            echo $process->getOutput();

            // load fixture data //
            $output->writeLn('<info>Loading Fixture data...</info>');
            $process = new Process('vendor/bin/haigha fixtures:load fixtures/main.yml  '.$pdoUrl.' ');
            $process->run();
            echo $process->getOutput();
        } catch (Exception $e) {
            $output->writeLn('<error>Database not Exist '.$dbname.' </error>');
        }
    }
}
