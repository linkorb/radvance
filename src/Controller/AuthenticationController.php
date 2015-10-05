<?php

namespace Radvance\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticationController
{
    public function loginAction(Application $app, Request $request)
    {
        return $app['twig']->render('@BaseTemplates/auth/login.html.twig', array(
            'error' => $app['security.last_error']($request)
        ));
    }

    public function logoutAction(Application $app, Request $request)
    {
        $app['session']->start();
        $app['session']->invalidate();

        return $app->redirect('/');
    }
}
