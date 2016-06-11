<?php

namespace Radvance\Translation;

use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Exception\InvalidResourceException;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use Symfony\Component\Yaml\Parser as YamlParser;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Config\Resource\FileResource;

use RuntimeException;

class RecursiveYamlFileMessageLoader extends ArrayLoader
{
    private $yamlParser;
    
    
    public function load($resource, $locale, $domain = 'messages')
    {
        if (!stream_is_local($resource)) {
            throw new InvalidResourceException(sprintf('This is not a local file "%s".', $resource));
        }

        if (!file_exists($resource)) {
            throw new NotFoundResourceException(sprintf('File "%s" not found.', $resource));
        }

        $messages = $this->loadResource($resource);

        // empty resource
        if (null === $messages) {
            $messages = array();
        }

        // not an array
        if (!is_array($messages)) {
            throw new InvalidResourceException(sprintf('Unable to load file "%s".', $resource));
        }

        $catalogue = parent::load($messages, $locale, $domain);

        if (class_exists('Symfony\Component\Config\Resource\FileResource')) {
            $catalogue->addResource(new FileResource($resource));
        }

        return $catalogue;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadResource($resource)
    {
        $basePath = dirname($resource);
        $this->yamlParser = new YamlParser();

        try {
            $messages = $this->yamlParser->parse(file_get_contents($resource));
        } catch (ParseException $e) {
            throw new InvalidResourceException(sprintf('Error parsing YAML, invalid file "%s"', $resource), 0, $e);
        }
        
        if (isset($messages['include'])) {
            foreach ($messages['include'] as $filename) {
                $filename = $basePath . '/' . $filename;
                if (!file_exists($filename)) {
                    throw new RuntimeException("Include filename does not exist: " . $filename);
                }
                $includeData = $this->loadResource($filename);
                $messages = array_merge_recursive($messages, $includeData);
            }
        }

        return $messages;
    }
}
