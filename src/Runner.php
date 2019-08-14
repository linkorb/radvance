<?php

namespace Radvance;

use Nyholm\Psr7\Factory\Psr17Factory;
use OpenAPIValidation\Schema\Validator;
use Silex\Application;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Component\HttpFoundation\Request;
use FlexMiddleware\FlexMiddlewareFactory;

class Runner
{
    public const MIDDLEWARE_SERVICES_LIST_ID = 'middleware_services';

    /**
     * @param HttpKernelInterface|Application $app
     * @param Request|null $request
     */
    static function run(HttpKernelInterface $application, Request $request = null)
    {
        $stack = $application->getStack();
        $app = $stack->resolve($application);

        $request = $request ?: Request::createFromGlobals();
        $middlewarePipe = new \Zend\Stratigility\MiddlewarePipe();

        $psr17Factory = new Psr17Factory();
        $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
        $psrRequest = $psrHttpFactory->createRequest($request);

        if ($application instanceof Application) {
            $flexMiddlewaresYaml = isset($application['flex_middlewares.config']) ?
                $application['flex_middlewares.config'] :
                realpath('../') . DIRECTORY_SEPARATOR . 'middlewares.yaml';

            if (is_file($flexMiddlewaresYaml)) {
                $middlewarePipe->pipe(FlexMiddlewareFactory::fromConfig($flexMiddlewaresYaml));
            }

            if (isset($application[self::MIDDLEWARE_SERVICES_LIST_ID])) {
                foreach ($application[self::MIDDLEWARE_SERVICES_LIST_ID] as $middlewareServiceID) {
                    if (!isset($application[$middlewareServiceID])) {
                        throw new \InvalidArgumentException("No $middlewareServiceID middleware service");
                    }

                    $middlewarePipe->pipe($application[$middlewareServiceID]);
                }
            }
        }

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
