<?php

namespace Radvance\Framework;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class TypeArgumentValueResolver implements ArgumentValueResolverInterface
{
    private $app;

    public function __construct(\Silex\Application $app)
    {
        $this->app = $app;
    }

    public function supports(Request $request, ArgumentMetadata $argument)
    {
        return isset($this->app[$argument->getType()]);
    }

    public function resolve(Request $request, ArgumentMetadata $argument)
    {
        yield $this->app[$argument->getType()];
    }
}
