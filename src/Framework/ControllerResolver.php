<?php

namespace Radvance\Framework;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolver as BaseControllerResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Silex\Application;
use RuntimeException;
use Twig_Environment;

class ControllerResolver extends BaseControllerResolver
{

    protected $app;

    public function __construct(Application $app)
    {
        $logger = null;
        $this->app = $app;
        parent::__construct($logger);
    }
    /**
     * {@inheritdoc}
     */
    public function getController(Request $request)
    {
        $controller = $request->attributes->get('_controller', null);
        $part = explode(':', $controller);
        if (count($part)!=3) {
            throw new RuntimeException("Controller does not have 3 parts:" . $controller . "(" . count($part));
        }
        // assume middle part is empty
        if ($part[1]!='') {
            throw new RuntimeException("Invalid controller format: " . $controller);
        }
        $className = $part[0];
        $methodName = $part[2];

        $reflectionClass = new \ReflectionClass($className);
        $constructor = $reflectionClass->getConstructor();

        $args = [];
        if ($constructor) {
            $parameters = $constructor->getParameters();
            $args = $this->injectArguments($parameters);
        }

        $class = $reflectionClass->newInstanceArgs($args);

        // Workaround for BaseController methods
        if (!method_exists($class, $methodName)) {
            if (method_exists($class, 'default' . ucfirst($methodName))) {
                $methodName = 'default' . ucfirst($methodName);
            }
        }
        return array($class, $methodName);
    }

    protected function injectArguments(array $parameters)
    {
        $args = [];
        $repositoryManager = $this->app['repository-manager'];
        foreach ($parameters as $parameter) {
            $class = $parameter->getClass();
            if ($class) {
                $className = (string)$class->getName();
                if ($class->isInstance($this->app)) {
                    $args[$parameter->getName()] = $this->app;
                }
                if ($className == Twig_Environment::class) {
                    $args[$parameter->getName()] = $this->app['twig'];
                }
                if ($className == UrlGenerator::class) {
                    $args[$parameter->getName()] = $this->app['url_generator'];
                }
                if ($className == \Symfony\Component\Form\FormFactory::class) {
                    $args[$parameter->getName()] = $this->app['form.factory'];
                }
                if ($className == \Radvance\Model\SpaceInterface::class) {
                    $args[$parameter->getName()] = $this->app['space'];
                }
                if (substr($className, -10) == 'Repository') {
                    foreach ($repositoryManager->getRepositories() as $repository) {
                        if (get_class($repository) == $className) {
                            $args[$parameter->getName()] = $repository;
                        }
                    }
                }
            }
        }
        return $args;
    }

    protected function doGetArguments(Request $request, $controller, array $parameters)
    {
        $args = $this->injectArguments($parameters);
        foreach ($args as $key => $value) {
            $request->attributes->set($key, $value);
        }
        return parent::doGetArguments($request, $controller, $parameters);
    }
}
