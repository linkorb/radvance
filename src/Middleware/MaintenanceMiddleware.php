<?php

namespace Radvance\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class MaintenanceMiddleware implements HttpKernelInterface
{
    private $app;
    private $generator;
    private $header;
    private $responseHeader;
    private $whitelist;

    public function __construct(
        HttpKernelInterface $app,
        $enabled = false,
        $whitelist = []
    ) {
        $this->app = $app;
        $this->enabled = $enabled;
        $this->whitelist = $whitelist;
    }

    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        if ($this->enabled) {
            if (!in_array($request->getClientIp(), $this->whitelist)) {
                return $this->serverMaintenanceResponse($request);
            }
        }

        $response = $this->app->handle($request, $type, $catch);

        return $response;
    }
    
    public function serverMaintenanceResponse(Request $request)
    {
        $html = "<h1>503 Service unavailable</h1>";
        $html .= "<h2>Maintenance work is being performed</h2>";
        $html .= "<p>Please check back in a few minutes...</p>";
        
        $response = new Response(
            $html,
            Response::HTTP_SERVICE_UNAVAILABLE,
            array(
                'content-type' => 'text/html',
                'retry-after' => 60
            )
        );
        return $response;
    }
}
