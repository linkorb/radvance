<?php

namespace Radvance\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Radvance\Generator\Generator;

class GenerateProjectCommand extends AbstractGeneratorCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->ignoreValidationErrors();

        $this
            ->setName('generate:project')
            ->setDescription('(re-)initialize a project directory')
            ->addOption(
                'projectPath',
                null,
                InputOption::VALUE_REQUIRED,
                'Path to the project root'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    final protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        $this->generator->generateProject();
    }
}
