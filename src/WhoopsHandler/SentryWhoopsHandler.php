<?php

namespace Radvance\WhoopsHandler;

use Silex\Application;
use Whoops\Handler\Handler;


class SentryWhoopsHandler extends Handler
{
    private $app;
    private $sentry;

    /**
     * @var bool
     */

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->sentry = $app['sentry'];
    }

    /**
     * @inherit
     */
    public function handle()
    {

        $exception = $this->getException();
        $extra = [];
        $this->sentry->captureException($exception, $extra);

        return Handler::DONE; // this handler is done, move to next
    }
}
