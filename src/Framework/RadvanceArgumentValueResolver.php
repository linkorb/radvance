<?php

namespace Radvance\Framework;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class RadvanceArgumentValueResolver implements ArgumentValueResolverInterface
{
    private $app;

    public function __construct(\Silex\Application $app)
    {
        $this->app = $app;
    }

    public function supports(Request $request, ArgumentMetadata $argument)
    {
        return isset($this->app['$' . $argument->getName()]);
    }

    public function resolve(Request $request, ArgumentMetadata $argument)
    {
        yield $this->app['$' . $argument->getName()];
    }
}
