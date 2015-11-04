<?php

namespace Radvance\AppConfigLoader;

use Symfony\Component\Yaml\Parser as YamlParser;
use Radvance\AppConfig;

class YamlAppConfigLoader
{
    public function loadFile($filename)
    {
        $rootPath = dirname($filename);
        $config = new AppConfig($rootPath);
        
        $parser = new YamlParser();
        $data = $parser->parse(file_get_contents($filename));
        $config->setName($data['name']);
        $config->setNamespace($data['namespace']);
        return $config;
    }
}
