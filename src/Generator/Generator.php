<?php

namespace Radvance\Generator;

use Radvance\AppConfig;
use RuntimeException;

class Generator
{
    private $appConfig;
    private $output;
    private $templatePath;
    
    public function __construct(AppConfig $appConfig, $output, $templatePath)
    {
        $this->appConfig = $appConfig;
        $this->output = $output;
        $this->templatePath = realpath($templatePath);
        
    }
    
    public function projectInit()
    {
        $this->ensureDirectory($this->appConfig->getAppPath() . '/');
        $this->ensureDirectory($this->appConfig->getAppPath() . '/config');
        $this->ensureDirectory($this->appConfig->getAppPath() . '/config/routes');
        $this->ensureDirectory($this->appConfig->getCodePath() . '/');
        $this->ensureDirectory($this->appConfig->getWebPath() . '/');
        $this->ensureFile('README.md');
        $this->ensureFile('.gitignore');
        $this->ensureFile($this->appConfig->getWebPath() . '/index.php');
        $this->ensureFile($this->appConfig->getWebPath() . '/.htaccess');
        $this->ensureFile($this->appConfig->getAppPath() . '/bootstrap.php');
        $this->ensureFile($this->appConfig->getAppPath() . '/schema.xml');
        $this->ensureFile($this->appConfig->getAppPath() . '/config/parameters.yml.dist');
        $this->ensureFile($this->appConfig->getAppPath() . '/config/routes.yml');
    }
    
    private function ensureDirectory($path)
    {
        $fullPath = $this->appConfig->getRootPath() . '/' . $path;
        $this->output->writeln('- <fg=green>Ensure directory: ' . $path . '</fg=green>');
        if (!file_exists($fullPath)) {
            mkdir($fullPath);
        }
    }
    
    private function ensureFile($path)
    {
        $fullPath = $this->appConfig->getRootPath() . '/' . $path;
        
        if (!file_exists($this->templatePath . '/' . $path)) {
            throw new RuntimeException("Missing template for: " . $path . ' (' . $this->templatePath .'::' . $path . ')');
        }

        if (!file_exists($fullPath)) {
            $this->output->writeln('- <fg=white>Ensure file: ' . $path . ' (create)</fg=white>');
            $data = file_get_contents($this->templatePath . '/' . $path);
            $data = str_replace('$$NAMESPACE$$', $this->appConfig->getNamespace(), $data);
            file_put_contents($fullPath, $data);
        } else {
            $this->output->writeln('- <fg=green>Ensure file: ' . $path . ' (skip)</fg=green>');
        }
    }
}
