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

abstract class AbstractGeneratorCommand extends Command
{
    protected $generator;
    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->hasOption('projectPath')) {
            $path = $input->getOption('projectPath');
        } else {
            $path = getcwd();
        }
        
        $this->projectPath = realpath($path);
        if (!$this->projectPath || !file_exists($this->projectPath)) {
            throw new RuntimeException("Project path does not exist: " . $path);
        }

        $filename = $path . '/radvance.yml';
        if (!file_exists($filename)) {
            throw new RuntimeException("File not found: $filename");
        }

        $output->writeLn("(Re)Initializing Project Path: " . $this->projectPath);
        
        $appConfigLoader = new YamlAppConfigLoader();
        $appConfig = $appConfigLoader->loadFile($filename);

        $templatePath = __DIR__ . '/../../generator-templates';
        $this->generator = new Generator($appConfig, $output, $templatePath);
        
    }
    

}
