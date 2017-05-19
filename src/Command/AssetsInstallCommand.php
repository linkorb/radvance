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

class AssetsInstallCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('assets:install')
            ->setDescription('Install module assets')
            ->addOption(
                'copy',
                null,
                InputOption::VALUE_NONE,
                'Make copy instead of symlink'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $copy = $input->getOption('copy');

        $application = require('app/bootstrap.php'); // bit tricky way to get the app
        $modules = $application['module-manager']->getModules();
        foreach ($modules as $module) {
            $this->installModuleAssets($module, $output);
        }
    }

    private function installModuleAssets($module, $output)
    {
        $this->installAssetsInDir($module->getPath().'/../res', 'js', $module->getName(), $output);
        $this->installAssetsInDir($module->getPath().'/../res', 'css', $module->getName(), $output);
    }

    private function installAssetsInDir($dir, $type, $moduleName, $output)
    {
        $dir .= '/'.$type;
        $dir = realpath($dir);
        if (is_dir($dir)) {
            $targetDir = 'web/modules';
            $targetDir = realpath($targetDir);
            if (!is_dir($targetDir)) {
                mkdir($targetDir);
            }
            $targetDir .= '/'.strtolower($moduleName);
            if (!is_dir($targetDir)) {
                mkdir($targetDir);
            }
            $targetDir .= '/'.$type;
            if (!is_dir($targetDir)) {
                mkdir($targetDir);
            }

            $files = scandir($dir);
            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    if (file_exists($targetDir.'/'.$file)) {
                        unlink($targetDir.'/'.$file);
                    }
                    symlink($dir.'/'.$file, $targetDir.'/'.$file);
                    $output->writeln('<info>Assets installed: '.$targetDir.'/'.$file.'</>');
                }
            }
        } else {
            // $output->writeln('<comment>No assets found in '.$dir.'</>');
        }
    }
}
