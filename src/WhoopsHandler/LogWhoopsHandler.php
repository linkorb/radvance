<?php

namespace Radvance\WhoopsHandler;

use Silex\Application;
use Whoops\Handler\Handler;


class LogWhoopsHandler extends Handler
{
    private $application;
    
    /**
     * @var bool
     */

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * @inherit
     */
    public function handle()
    {
        $formatter = new Formatter();
        $data = $formatter->getData($this->getInspector());
        
        $json = json_encode($data, JSON_PARTIAL_OUTPUT_ON_ERROR|JSON_UNESCAPED_SLASHES);
        $path = $this->application->getRootPath() . '/app/logs/exceptions';
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $filename = $path . '/' . date('Ymd-His') . '-' . sha1(rand()) . '.json';
        file_put_contents($filename, $json);
        return Handler::DONE;
    }
}
