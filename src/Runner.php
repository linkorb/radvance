<?php

namespace Radvance;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Component\HttpFoundation\Request;

class Runner
{
    static function run(HttpKernelInterface $app, Request $request = null)
    {
        $stack = $app->getStack();
        $app = $stack->resolve($app);
            
        $request = $request ?: Request::createFromGlobals();

        $response = $app->handle($request);
        $response->send();
        if ($app instanceof TerminableInterface) {
            $app->terminate($request, $response);
        }
    }
}
