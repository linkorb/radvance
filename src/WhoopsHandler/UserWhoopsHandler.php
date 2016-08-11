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
        
        $filename = 'exception.html.twig';
        if (!$app['twig.loader']->exists($filename)) {
            $filename = '@BaseTemplates/app/exception.html.twig';
        }
        $html = $app['twig']->render(
            $filename,
            $data
        );
        http_response_code(500);
        echo $html;

        return Handler::QUIT;
    }
}
