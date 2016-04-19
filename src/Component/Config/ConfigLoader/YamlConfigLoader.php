<?php

namespace Radvance\Component\Config\ConfigLoader;

use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Yaml\Yaml;
use RuntimeException;

class YamlConfigLoader extends FileLoader
{
    public function load($resource, $type = null)
    {
        $files = $this->locator->locate($resource, null, false);
        if (!$files) {
            throw new RuntimeException("Config file not found: $resource");
        }

        foreach ($files as $file) {
            $config = Yaml::parse(file_get_contents($file));
            if (isset($config['imports'])) {
                foreach ($config['imports'] as $import) {
                    $configImported = $this->import($import['resource']);
                    $config = array_replace_recursive($config, $configImported);
                }
            }
        }

        return $config;
    }

    public function supports($resource, $type = null)
    {
        return is_string($resource) && 'yml' === pathinfo(
            $resource,
            PATHINFO_EXTENSION
        );
    }
}
