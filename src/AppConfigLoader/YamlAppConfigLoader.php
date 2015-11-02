<?php

namespace Radvance\AppConfigLoader;

use Symfony\Component\Yaml\Parser as YamlParser;
use Radvance\AppConfig;

class YamlAppConfigLoader
{
    public function loadFile($filename)
    {
        $config = new AppConfig();
        
        $parser = new YamlParser();
        $data = $parser->parse(file_get_contents($filename));
        $config->setName($data['name']);
        $config->setNamespace($data['namespace']);
        $config->setCodePath($data['code_path']);
        return $config;
    }
}
