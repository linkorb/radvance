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

class ProjectInitCommand extends Command
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
            ->addArgument(
                'projectPath',
                InputArgument::REQUIRED,
                'Project path'
            )
            ->addOption(
                'format',
                null,
                InputOption::VALUE_REQUIRED,
                'The output format: array, JSON, or print'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getArgument('projectPath');
        $this->projectPath = realpath($path);
        $output->writeLn("(Re)Initializing Project Path: " . $this->projectPath);
        
        if (!$this->projectPath || !file_exists($this->projectPath)) {
            throw new RuntimeException("Path does not exist: " . $path);
        }

        $filename = $path . '/radvance.yml';
        if (!file_exists($filename)) {
            throw new RuntimeException("File not found: $filename");
        }
        
        $appConfigLoader = new YamlAppConfigLoader();
        $appConfig = $appConfigLoader->loadFile($filename);

        $templatePath = __DIR__ . '/../../generator-templates';
        $this->generator = new Generator($appConfig, $output, $templatePath);
        $this->generator->projectInit();
        
        //$this->ensureFile('composer.json', $output);
        
        //$this->format = $input->getOption('format');
    }
    

}
