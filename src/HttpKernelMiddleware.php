<?php

namespace Radvance;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use \Psr\Http\Message\ResponseInterface;

/**
 * Class HttpKernelMiddleware
 * @author Aleksandr Arofikin <sashaaro@gmail.com>
 */
class HttpKernelMiddleware implements MiddlewareInterface, RequestHandlerInterface
{
    /** @var HttpKernelInterface */
    protected $httpKernel;
    /** @var HttpFoundationFactory */
    protected $httpFoundationFactory;
    /** @var PsrHttpFactory */
    protected $psrHttpFactory;

    public function __construct(HttpKernelInterface $httpKernel)
    {
        $this->httpKernel = $httpKernel;
        $this->httpFoundationFactory = new HttpFoundationFactory();

        // https://symfony.com/doc/current/components/psr7.html#usage
        $psr17Factory = new Psr17Factory();
        $this->psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws \Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $symfonyRequest = $this->httpFoundationFactory->createRequest($request);
        $symfonyResponse = $this->httpKernel->handle($symfonyRequest);
        $psrRequest = $this->psrHttpFactory->createResponse($symfonyResponse);

        return $psrRequest;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->process($request, $this);
    }

    public function getHttpKernel(): HttpKernelInterface
    {
        return $this->httpKernel;
    }
}