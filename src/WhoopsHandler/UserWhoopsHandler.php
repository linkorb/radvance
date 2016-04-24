<?php

namespace Radvance\WhoopsHandler;

use Silex\Application;
use Whoops\Handler\Handler;

class UserWhoopsHandler extends Handler
{
    private $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * @inherit
     */
    public function handle()
    {
        $app = $this->application;
        $data = array();
        $html = $app['twig']->render(
            '@BaseTemplates/app/exception.html.twig',
            $data
        );
        http_response_code(500);
        echo $html;

        return Handler::QUIT;
    }
}
