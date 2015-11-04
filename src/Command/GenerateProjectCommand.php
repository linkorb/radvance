<?php

namespace Radvance\Command;

use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Radvance\AppConfigLoader\YamlAppConfigLoader;
use Radvance\Generator\Generator;
use RuntimeException;

class GenerateProjectCommand extends AbstractGeneratorCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->ignoreValidationErrors();

        $this
            ->setName('project:init')
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
        $this->generator->projectInit();
    }
    

}
