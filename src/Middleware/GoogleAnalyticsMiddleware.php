<?php

namespace Radvance\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class GoogleAnalyticsMiddleware implements HttpKernelInterface
{
    use HtmlInjectorTrait;

    private $app;
    private $siteId;
    private $options = [];

    public function __construct(
        HttpKernelInterface $app,
        $siteId
    ) {
        $this->app = $app;
        $this->siteId = (string)$siteId;
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
     * Returns the google code.
     * https://github.com/h5bp/html5-boilerplate/blob/master/src/index.html.
     *
     * @return string
     */
    private function getCode()
    {
        return <<<GA
<script>
    (function(b,o,i,l,e,r){b.GoogleAnalyticsObject=l;b[l]||(b[l]=
    function(){(b[l].q=b[l].q||[]).push(arguments)});b[l].l=+new Date;
    e=o.createElement(i);r=o.getElementsByTagName(i)[0];
    e.src='https://www.google-analytics.com/analytics.js';
    r.parentNode.insertBefore(e,r)}(window,document,'script','ga'));
    ga('create','{$this->siteId}','auto');ga('send','pageview');
</script>
GA;
    }
}
