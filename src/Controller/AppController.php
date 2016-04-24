<?php

namespace Radvance\Controller;

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

    public function templateAction(Application $app, Request $request, $template)
    {
        return new Response($app['twig']->render(
            $template
        ));
    }

    public function accountsAction(Application $app, Request $request)
    {
        $data = array();
        $data['accounts'] = $app['current_user']->getAccounts();

        return new Response($app['twig']->render(
            '@BaseTemplates/app/accounts.html.twig',
            $data
        ));
    }
}
