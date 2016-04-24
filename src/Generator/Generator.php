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

    public function generateProject()
    {
        $this->ensureDirectory($this->appConfig->getAppPath().'');
        $this->ensureDirectory($this->appConfig->getAppPath().'/config');
        $this->ensureDirectory($this->appConfig->getAppPath().'/config/routes');
        $this->ensureDirectory($this->appConfig->getCodePath().'');
        $this->ensureDirectory($this->appConfig->getCodePath().'/Model');
        $this->ensureDirectory($this->appConfig->getCodePath().'/Repository');
        $this->ensureDirectory($this->appConfig->getCodePath().'/Controller');
        $this->ensureDirectory($this->appConfig->getWebPath().'');
        $this->ensureDirectory($this->appConfig->getTemplatesPath().'');
        $this->ensureDirectory($this->appConfig->getThemesPath().'');
        $this->ensureDirectory($this->appConfig->getThemesPath().'/default');

        // Project root
        $this->ensureFile('README.md');
        $this->ensureFile('.gitignore');
        $this->ensureFile('.editorconfig');

        $this->ensureFile('.bowerrc');
        $this->ensureFile('bower.json');
        $this->ensureFile('deploy');

        // Web root
        $this->ensureFile($this->appConfig->getWebPath().'/index.php');
        $this->ensureFile($this->appConfig->getWebPath().'/.htaccess');

        // App directory
        $this->ensureFile($this->appConfig->getAppPath().'/bootstrap.php');
        $this->ensureFile($this->appConfig->getAppPath().'/schema.xml');
        $this->ensureFile($this->appConfig->getAppPath().'/config/config.yml');
        $this->ensureFile($this->appConfig->getAppPath().'/config/parameters.yml.dist');
        $this->ensureFile($this->appConfig->getAppPath().'/config/routes.yml');

        $this->ensureFile($this->appConfig->getThemesPath().'/default/layout.html.twig');
        $this->ensureFile($this->appConfig->getThemesPath().'/default/style.less');

        $this->ensureFile($this->appConfig->getTemplatesPath().'/frontpage.html.twig');

        // src directory
        $this->ensureFile($this->appConfig->getCodePath().'/Application.php');
    }

    public function generateController($prefix)
    {
        if (substr($prefix, -10) == 'Controller') {
            throw new RuntimeException('Please only pass a classname prefix, excluding the `Controller` postfix');
        }
        $data = array();
        $data['CLASS_PREFIX'] = $prefix;

        $this->ensureDirectory($this->appConfig->getCodePath().'/Controller');
        $this->ensureFile(
            $this->appConfig->getCodePath().'/Controller/'.$prefix.'Controller.php',
            $this->appConfig->getCodePath().'/Controller/ExampleController.php',
            $data
        );
    }

    public function generateModel($prefix)
    {
        $data = array();
        $data['CLASS_PREFIX'] = $prefix;

        $this->ensureDirectory($this->appConfig->getCodePath().'/Model');
        $this->ensureFile(
            $this->appConfig->getCodePath().'/Model/'.$prefix.'.php',
            $this->appConfig->getCodePath().'/Model/Example.php',
            $data
        );
    }

    public function generateRepository($prefix)
    {
        $data = array();
        $data['CLASS_PREFIX'] = $prefix;

        $this->ensureDirectory($this->appConfig->getCodePath().'/Repository');
        $this->ensureFile(
            $this->appConfig->getCodePath().'/Repository/Pdo'.$prefix.'Repository.php',
            $this->appConfig->getCodePath().'/Repository/PdoExampleRepository.php',
            $data
        );
    }

    public function generateTemplates($prefix)
    {
        $data = array();
        $data['CLASS_PREFIX'] = $prefix;

        $this->ensureDirectory($this->appConfig->getTemplatePath().'');
        $this->ensureDirectory($this->appConfig->getTemplatePath().'/'.$prefix);

        $this->ensureFile(
            $this->appConfig->getTemplatePath().'/'.$prefix.'/index.html.twig',
            $this->appConfig->getTemplatePath().'/Example/index.html.twig',
            $data
        );

        $this->ensureFile(
            $this->appConfig->getTemplatePath().'/'.$prefix.'/view.html.twig',
            $this->appConfig->getTemplatePath().'/Example/view.html.twig',
            $data
        );

        $this->ensureFile(
            $this->appConfig->getTemplatePath().'/'.$prefix.'/edit.html.twig',
            $this->appConfig->getTemplatePath().'/Example/edit.html.twig',
            $data
        );
    }

    private function ensureDirectory($path)
    {
        $fullPath = $this->appConfig->getRootPath().'/'.$path;
        $this->output->writeln('- <fg=green>Ensure directory: '.$path.'</fg=green>');
        if (!file_exists($fullPath)) {
            mkdir($fullPath);
        }
    }

    private function ensureFile($outputPath, $templatePath = null, $data = array())
    {
        if (!$templatePath) {
            $templatePath = $outputPath;
        }
        $fullOutputPath = $this->appConfig->getRootPath().'/'.$outputPath;

        if (!file_exists($this->templatePath.'/'.$templatePath)) {
            throw new RuntimeException('Missing template for: '.$templatePath.' ('.$this->templatePath.'::'.$templatePath.')');
        }

        if (!file_exists($fullOutputPath)) {
            $this->output->writeln('- <fg=white>Ensure file: '.$outputPath.' (create)</fg=white>');
            $content = file_get_contents($this->templatePath.'/'.$templatePath);

            $data['NAMESPACE'] = $this->appConfig->getNamespace();
            foreach ($data as $key => $value) {
                echo "    * $key=\"$value\"\n";
                $content = str_replace('$$'.$key.'$$', $value, $content);
            }
            file_put_contents($fullOutputPath, $content);
        } else {
            $this->output->writeln('- <fg=green>Ensure file: '.$outputPath.' (skip)</fg=green>');
        }
    }
}
