<?php

namespace Radvance\Component\Config;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Radvance\Component\Config\ConfigLoader\YamlConfigLoader;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use RuntimeException;

class ConfigProcessor
{
    public function postProcessConfig(&$config, $parameters)
    {
        foreach ($config as $key => $value) {
            if (is_string($value)) {
                $config[$key] = $this->postProcessConfigString($value, $parameters);
            }
            if (is_array($value)) {
                $config[$key] = $this->postProcessConfig($value, $parameters);
            }
        }

        return $config;
    }

    private function postProcessConfigString($string, $parameters)
    {
        // Inject parameters for strings between % characters
        if ((substr($string, 0, 1) == '%') && (substr($string, -1, 1) == '%')) {
            $string = trim($string, '%');
            if (!isset($parameters[$string])) {
                throw new RuntimeException("Required parameter '$string' not defined");
            }
            $string = $parameters[$string];
        }

        return $string;
    }
}
