<?php

namespace Radvance\Middleware;

use Symfony\Component\HttpFoundation\Response;

trait HtmlInjectorTrait
{
    private function inject(Response $response, $code, $tag = 'body')
    {
        $html = (string)$response->getContent();
        $pos = strripos($html, "</{$tag}>");

        if ($pos === false) {
            return $response;
        }

        $html = substr($html, 0, $pos) . $code . substr($html, $pos);
        $response->setContent($html);
        return $response;
    }
}
