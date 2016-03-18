<?php

namespace Radvance\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DashboardController
{

    public function indexAction(Application $app, Request $request)
    {
        if (!$app['current_user']) {
            return $app->redirect(
                $app['url_generator']->generate(
                    'login'
                )
            );
        }

        return new Response($app['twig']->render(
            '@BaseTemplates/dashboard.html.twig',
            array(
                'libraries' => $app->getSpaceRepository()
                    ->findByUsername($app['current_user']->getName()),
            )
        ));
    }
}
