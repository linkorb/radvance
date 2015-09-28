<?php

namespace LinkORB\Framework\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AppController
{
    public function frontpageAction(Application $app, Request $request)
    {
        return new Response($app['twig']->render(
            '@BaseTemplates/frontpage.html.twig'
        ));
    }
}
