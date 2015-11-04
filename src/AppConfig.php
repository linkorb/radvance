<?php

namespace Radvance;

use RuntimeException;

class AppConfig
{
    public function __construct($rootPath)
    {
        $this->rootPath = realpath($rootPath);
        if (!file_exists($this->rootPath)) {
            throw new RuntimeException("Rootpath does not exist: " . $rootPath);
        }
    }
    
    private $ns;
    
    public function getNameSpace()
    {
        return $this->ns;
    }
    
    public function setNameSpace($ns)
    {
        $this->ns = $ns;
        return $this;
    }
    
    private $name;
    
    public function getName()
    {
        return $this->name;
    }
    
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
    
    private $rootPath;
    
    public function getRootPath()
    {
        return $this->rootPath;
    }
    
    public function setRootPath($rootPath)
    {
        $this->rootPath = $rootPath;
        return $this;
    }
    
    private $webPath = 'web';
    
    public function getWebPath()
    {
        return $this->webPath;
    }
    
    private $codePath = 'src';

    public function getCodePath()
    {
        return $this->codePath;
    }

    private $appPath = 'app';

    public function getAppPath()
    {
        return $this->appPath;
    }
    
    private $templatePath = 'templates';

    public function getTemplatePath()
    {
        return $this->templatePath;
    }
    
}
