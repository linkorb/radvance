<?php

namespace Radvance\Command;

use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use RuntimeException;

class ProjectInitCommand extends Command
{
    protected $output;
    protected $projectPath;
    protected $templatePath;
    protected $projectNameSpace = 'Test\\Stuff\\';
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
        $this->output = $output;
        $path = $input->getArgument('projectPath');
        $this->projectPath = realpath($path);
        $this->output->writeLn("(Re)Initializing Project Path: " . $this->projectPath);
        if (!$this->projectPath || !file_exists($this->projectPath)) {
            throw new RuntimeException("Path does not exist: " . $path);
        }
        
        $this->templatePath = __DIR__ . '/templates';
        
        $this->ensureDirectory('app/', $output);
        $this->ensureDirectory('app/config', $output);
        $this->ensureDirectory('app/config/routes', $output);
        $this->ensureDirectory('src/', $output);
        $this->ensureDirectory('web/', $output);
        $this->ensureFile('README.md', $output);
        $this->ensureFile('.gitignore', $output);
        $this->ensureFile('web/index.php', $output);
        $this->ensureFile('web/.htaccess', $output);
        $this->ensureFile('app/bootstrap.php', $output);
        $this->ensureFile('app/schema.xml', $output);
        $this->ensureFile('app/config/parameters.yml.dist', $output);
        $this->ensureFile('app/config/routes.yml', $output);
        //$this->ensureFile('composer.json', $output);
        
        //$this->format = $input->getOption('format');
    }
    
    private function ensureDirectory($path)
    {
        $fullPath = $this->projectPath . '/' . $path;
        $this->output->writeln('- <fg=green>Ensure directory: ' . $path . '</fg=green>');
        if (!file_exists($fullPath)) {
            mkdir($fullPath);
        }
    }
    
    private function ensureFile($path)
    {
        $fullPath = $this->projectPath . '/' . $path;
        
        if (!file_exists($this->templatePath . '/' . $path)) {
            throw new RuntimeException("Missing template for: " . $path);
        }

        
        if (!file_exists($fullPath)) {
            $this->output->writeln('- <fg=white>Ensure file: ' . $path . ' (create)</fg=white>');
            $data = file_get_contents($this->templatePath . '/' . $path);
            $data = str_replace('$$NAMESPACE$$', $this->projectNameSpace, $data);
            file_put_contents($fullPath, $data);
        } else {
            $this->output->writeln('- <fg=green>Ensure file: ' . $path . ' (skip)</fg=green>');
        }
    }
    
}
