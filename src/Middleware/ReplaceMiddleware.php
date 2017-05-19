<?php

namespace Radvance\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ReplaceMiddleware implements HttpKernelInterface
{
    private $app;
    private $replacements;

    public function __construct(
        HttpKernelInterface $app,
        $replacements
    ) {
        $this->app = $app;
        $this->replacements = $replacements;
    }

    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        $response = $this->app->handle($request, $type, $catch);
        $content = (string)$response->getContent();
        foreach ($this->replacements as $key => $value) {
            $content = str_replace($key, $value, $content);
        }

        $response->setContent($content);
        return $response;
    }
}
