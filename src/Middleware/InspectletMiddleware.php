<?php

namespace Radvance\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class InspectletMiddleware implements HttpKernelInterface
{
    use HtmlInjectorTrait;

    private $app;
    private $siteId;

    public function __construct(
        HttpKernelInterface $app,
        $siteId
    ) {
        $this->app = $app;
        $this->siteId = $siteId;
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
     * Returns the Inspectlet code.
     *
     * @return string
     */
    private function getCode()
    {
        return <<<INSPECTLET
<script type="text/javascript" id="inspectletjs">
window.__insp = window.__insp || [];
__insp.push(['wid', {$this->siteId}]);
(function() {
function ldinsp(){if(typeof window.__inspld != "undefined") return; window.__inspld = 1; var insp = document.createElement('script'); insp.type = 'text/javascript'; insp.async = true; insp.id = "inspsync"; insp.src = ('https:' == document.location.protocol ? 'https' : 'http') + '://cdn.inspectlet.com/inspectlet.js'; var x = document.getElementsByTagName('script')[0]; x.parentNode.insertBefore(insp, x); };
setTimeout(ldinsp, 500); document.readyState != "complete" ? (window.attachEvent ? window.attachEvent('onload', ldinsp) : window.addEventListener('load', ldinsp, false)) : ldinsp();
})();
</script>
INSPECTLET;
    }
}
