<?php

namespace Radvance;

class AppConfig
{
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
    
    private $codePath;
    
    public function getCodePath()
    {
        return $this->codePath;
    }
    
    public function setCodePath($codePath)
    {
        $this->codePath = $codePath;
        return $this;
    }
}
