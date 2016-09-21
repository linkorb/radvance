<?php

namespace Radvance\Controller;

use Symfony\Component\HttpFoundation\Response;

class MetaController
{
    public function robotAction()
    {
        return new Response(
            "User-agent: *\nDisallow:"
        );
    }

    public function faviconAction()
    {
        return new Response(
            readfile(__DIR__.'/../favicon.png'),
            200,
            [
                'Content-Type' => 'image/png',
                'Content-Disposition' => 'inline; filename="favicon.ico',
            ]
        );
    }
}
