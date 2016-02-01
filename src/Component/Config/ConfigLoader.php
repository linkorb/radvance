<?php

namespace Radvance\Component\Config;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Radvance\Component\Config\ConfigLoader\YamlConfigLoader;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use RuntimeException;

class ConfigLoader
{
    public function load($path, $filename)
    {
        $configDirectories = array($path);
        $locator = new FileLocator($configDirectories);
        
        $loaderResolver = new LoaderResolver(array(new YamlConfigLoader($locator)));
        $delegatingLoader = new DelegatingLoader($loaderResolver);

        $config = $delegatingLoader->load($filename);
        if (isset($config['parameters'])) {
            $config = $this->postProcessConfig($config, $config['parameters']);
        }
        return $config;
    }
    
    private function postProcessConfigString($string, $parameters)
    {
        $language = new ExpressionLanguage();
        $language->register(
            'env',
            function ($str) {
                // This implementation is only needed if you want to compile
                // not needed when simply using the evaluator
                throw new RuntimeException("The 'env' method is not yet compilable.");
            },
            function ($arguments, $str, $required = false) {
                $res = getenv($str);

                if (!$res && $required) {
                    throw new RuntimeException("Required environment variable '$str' is not defined");
                }

                return $res;
            }
        );

        preg_match_all('~\{\{(.*?)\}\}~', $string, $matches);

        $variables = array();
        //$variables['hello']='world';

        foreach ($matches[1] as $match) {
            $out = $language->evaluate($match, $variables);
            $string = str_replace('{{'.$match.'}}', $out, $string);
        }
        
        // Inject parameters for strings between % characters
        if ((substr($string, 0, 1)=='%') && (substr($string, -1, 1)=='%')) {
            $string = trim($string, '%');
            if (!isset($parameters[$string])) {
                throw new RuntimeException("Required parameter '$string' not defined");
            }
            $string = $parameters[$string];
        }

        return $string;
    }

    private function postProcessConfig(&$config, $parameters)
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

}
