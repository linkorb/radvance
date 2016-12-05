<?php

namespace Radvance\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class PiwikMiddleware implements HttpKernelInterface
{
    use HtmlInjectorTrait;

    private $app;
    private $piwikUrl;
    private $siteId;
    private $options = [];

    public function __construct(
        HttpKernelInterface $app,
        $piwikUrl,
        $siteId
    ) {
        $this->app = $app;
        $this->piwikUrl = $piwikUrl;
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
     * Returns the piwik code.
     *
     * @return string
     */
    private function getCode()
    {
        $_paq = '';
        foreach ($this->options as $option) {
            $option[0] = "'".$option[0]."'";
            $_paq .= sprintf('_paq.push([%s]);', implode(',', $option));
        }
        return <<<PWK
<script>
    var _paq = _paq || [];
    {$_paq}
    (function() {
        var u="{$this->piwikUrl}";
        _paq.push(['setTrackerUrl', u+'piwik.php']);
        _paq.push(['setSiteId', {$this->siteId}]);
        var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
        g.type='text/javascript'; g.async=true; g.defer=true; g.src=u+'piwik.js'; s.parentNode.insertBefore(g,s);
    })();
</script>
<noscript><p><img src="{$this->piwikUrl}piwik.php?idsite={$this->siteId}" style="border:0;" alt="" /></p></noscript>
PWK;
    }
}
