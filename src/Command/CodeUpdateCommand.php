<?php

namespace Radvance\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Process\Process;

class CodeUpdateCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('code:update')
            ->setDescription('Update to the latest code and install dependencies');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // ensure parameters.yml file
        $filename = 'app/config/parameters.yml';
        if (!file_exists($filename)) {
            $dist = $filename.'.dist';
            if (file_exists($dist)) {
                if (copy($dist, $filename)) {
                    $output->writeLn('<info>parameters.yml generated based on the dist file.</info>');
                } else {
                    $output->writeLn('<error>parameters.yml is not generated!</error>');
                }
            } else {
                $output->writeLn('<error>parameters.yml.dist file doesn not exists!</error>');
            }
        } else {
            $output->writeLn('<info>parameters.yml exists already.</info>');
        }

        // ensure app/storage
        $this->ensureDirectories('app/storage');

        // ensure app/logs
        $this->ensureDirectories('app/logs');
        $this->ensureDirectories('app/logs/exceptions');

        // git update
        $this->runCommandProcess('git pull origin master', $output);

        // composer install
        $this->runCommandProcess('composer install --prefer-dist --ignore-platform-reqs', $output);

        // bower install
        $this->runCommandProcess('bower install --allow-root', $output);

        // update schema
        $command = $this->getApplication()->find('schema:load');
        $schemaLoadInput = new ArrayInput([
            'command' => 'schema:load',
            '--apply' => true,
        ]);
        $command->run($schemaLoadInput, $output);

        // install assets
        $command = $this->getApplication()->find('assets:install');
        $schemaLoadInput = new ArrayInput([
            'command' => 'assets:install',
            '--copy' => false,
        ]);
        $command->run($schemaLoadInput, $output);

        $output->writeLn('<info>Done!</info>');
    }

    private function runCommandProcess($cmd, OutputInterface $output)
    {
        $process = new Process($cmd);
        $output->writeLn('<comment>'.$process->getCommandLine().'</comment>');
        $process->run();
        $output->writeLn($process->getOutput());
    }

    private function ensureDirectories($path)
    {
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
            chmod($path, 0777);
        }
    }
}
