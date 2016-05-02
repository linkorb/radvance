<?php

use $$NAMESPACE$$\Application;
use Symfony\Component\HttpFoundation\Request;

$app = new Application();

$app->before(function (Request $request, Application $app) {
    // Define userbaseUrl in twig templates for login + signup links
    if (isset($app['userbaseUrl'])) {
        $app['twig']->addGlobal('userbaseUrl', $app['userbaseUrl']);
    }

    // support baseurl
    if (isset($app['parameters']['baseurl'])) {
        $app['request_context']->setBaseUrl($app['parameters']['baseurl']);
    }
    $app['twig']->addGlobal('current_route', $request->get('_route'));

    $app['current_user'] = null;
    $token = $app['security.token_storage']->getToken();
    if ($token) {
        if ($request->get('_route') != 'login') {
            if ($token->getUser() == 'anon.') {
                // visitor is not authenticated
            } else {
                // visitor is authenticated
                $app['current_user'] = $token->getUser();
                $app['twig']->addGlobal('current_user', $token->getUser());
            }
        }
    }

    if ($request->attributes->has('accountName')) {
        $accountName = $request->attributes->get('accountName');
        $app['twig']->addGlobal('accountName', $accountName);
        $app['accountName'] = $accountName;
    }

    if ($request->attributes->has('libraryName')) {
        $libraryName = $request->attributes->get('libraryName');
        $app['twig']->addGlobal('libraryName', $libraryName);
        $app['libraryName'] = $libraryName;
    }
});

return $app;
