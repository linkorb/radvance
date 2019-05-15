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
        $exception = $this->getException();
     
        $filename = 'exception.html.twig';
        if (!$app['twig.loader']->exists($filename)) {
            $filename = '@BaseTemplates/app/exception.html.twig';
        }
        $code = 500;
        $message = 'Internal server error';

        if (is_a($exception, \Radvance\Exception\ExpiredException::class)) {
            $code = 419;
            $message = 'Expired';
        } 

        $data = [
            'code' => $code,
            'message' => $message,
        ];


        $html = $app['twig']->render(
            $filename,
            $data
        );

        // echo get_class($exception);exit();

        http_response_code($code);
        echo $html;

        return Handler::QUIT;
    }
}
