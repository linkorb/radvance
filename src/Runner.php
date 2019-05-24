<?php

namespace Radvance;

use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
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
        $middlewarePipe = new \Zend\Stratigility\MiddlewarePipe();

        $psr17Factory = new Psr17Factory();
        $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
        $psrRequest = $psrHttpFactory->createRequest($request);

        // TODO $middlewarePipe->pipe(\FlexMiddleware\FlexMiddlewareFactory::fromConfig('../config/middlewares.yaml'));


        $middlewarePipe->pipe(new HttpKernelMiddleware($app));
        $psrResponse = $middlewarePipe->handle($psrRequest);

        self::sendResponse($psrResponse);

        if ($app instanceof TerminableInterface) {
            $httpFoundationFactory = new HttpFoundationFactory();
            $app->terminate($request, $httpFoundationFactory->createResponse($psrResponse));
        }
    }

    static function sendResponse($response)
    {
        //$httpFoundationFactory->createResponse($psrResponse)->send();

        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), false);
            }
        }

        header(sprintf(
            'HTTP/%s %s %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        ));

        $body = $response->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }

        echo $body->getContents();
    }
}
