<?php

namespace Radvance\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class SpotClarifyMiddleware implements HttpKernelInterface
{
    use HtmlInjectorTrait;

    private $app;
    private $key;

    public function __construct(
        HttpKernelInterface $app,
        $key
    ) {
        $this->app = $app;
        $this->key = $key;
    }

    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        $response = $this->app->handle($request, $type, $catch);
        if (substr($response->headers->get('content-type', null), 0, 9) == 'text/html') {
            $response = $this->inject($response, $this->getCode(), 'body');
        }
        return $response;
    }
    
    /**
     * Returns the SpotClarify code.
     *
     * @return string
     */
    private function getCode()
    {
        return <<<SPOTCLARIFY
<script>
    (function(w,d,n,s,u,a,b){w[n]=function(x){(w[n].q=w[n].q||[]).push(x)};a=d.createElement(s);a.src=u;a.async=1;b=d.getElementsByTagName(s)[0];b.parentNode.insertBefore(a,b)})
    (window,document,'spotClarify','script','https://app.spotclarify.com/spotclarify.js');
    spotClarify('{$this->key}');
</script>
SPOTCLARIFY;
    }
}
