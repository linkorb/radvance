<?php

namespace Radvance\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class AuthenticationController
{
    public function loginAction(Application $app, Request $request)
    {
        $templateName = '@Templates/auth/login.html.twig';
        if (!$app['twig']->getLoader()->exists($templateName)) {
            $templateName = '@BaseTemplates/auth/login.html.twig';
        }
        return $app['twig']->render($templateName, array(
            'error' => $app['security.last_error']($request),
        ));
    }

    public function logoutAction(Application $app, Request $request)
    {
        $app['session']->start();
        $app['session']->invalidate();

        return $app->redirect('/');
    }
}
