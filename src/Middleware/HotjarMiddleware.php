<?php

namespace Radvance\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class HotjarMiddleware implements HttpKernelInterface
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
     * Returns the Hotjar code.
     *
     * @return string
     */
    private function getCode()
    {
        return <<<HOTJAR
<script>
  (function(h,o,t,j,a,r){
      h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)};
      h._hjSettings={hjid:{$this->siteId},hjsv:5};
      a=o.getElementsByTagName('head')[0];
      r=o.createElement('script');r.async=1;
      r.src=t+h._hjSettings.hjid+j+h._hjSettings.hjsv;
      a.appendChild(r);
  })(window,document,'//static.hotjar.com/c/hotjar-','.js?sv=');
</script>
HOTJAR;
    }
}
