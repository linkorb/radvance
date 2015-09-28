<?php

namespace LinkORB\Framework\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticationController
{
    public function loginAction(Application $app, Request $request)
    {
        return $app['twig']->render('@BaseTemplates/login.html.twig', array(
            'error' => $app['security.last_error']($request)
        ));
    }
}
