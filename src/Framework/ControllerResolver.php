<?php

namespace Radvance\Framework;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolver as BaseControllerResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Silex\Application;
use RuntimeException;

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
        
        return array($class, $methodName);
    }
    
    protected function injectArguments(array $parameters)
    {
        $args = [];
        foreach ($parameters as $parameter) {
            if ($parameter->getName()=='app') {
                $args['app'] = $this->app;
            }
            if (substr($parameter->getName(), -10) == 'Repository') {
                $args[$parameter->getName()] = $this->app->getRepository(substr($parameter->getName(), 0, -10));
            }
        }
        return $args;
    }
    
    protected function doGetArguments(Request $request, $controller, array $parameters)
    {
        //print_r($parameters);
        foreach ($parameters as $param) {
            if ($param->getClass() && $param->getClass()->isInstance($this->app)) {
                $request->attributes->set($param->getName(), $this->app);
                break;
            }
            //todo: inject repositories here too?
        }
        $args = $this->injectArguments($parameters);
        foreach ($args as $key => $value) {
            $request->attributes->set($key, $value);
        }
        return parent::doGetArguments($request, $controller, $parameters);
    }
}
