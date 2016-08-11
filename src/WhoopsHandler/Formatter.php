<?php

namespace Radvance\WhoopsHandler;

use Whoops\Exception\Formatter as WhoopsFormatter;

class Formatter
{
    private $returnFrames = true;
    /**
     * @param  bool|null  $returnFrames
     * @return bool|$this
     */
    public function addTraceToOutput($returnFrames = null)
    {
        if (func_num_args() == 0) {
            return $this->returnFrames;
        }
        $this->returnFrames = (bool) $returnFrames;
        return $this;
    }
    
    public function getData($inspector)
    {
        //print_r($this->getInspector());exit();
        $data = [
            'trace' => WhoopsFormatter::formatExceptionAsDataArray(
                $inspector,
                $this->addTraceToOutput()
            ),
            "GET Data"              => $_GET,
            "POST Data"             => $_POST,
            "Files"                 => $_FILES,
            "Cookies"               => $_COOKIE,
            "Session"               => isset($_SESSION) ? $_SESSION :  [],
            "Server/Request Data"   => $_SERVER,
            "Environment Variables" => $_ENV,
        ];
        return $data;
    }
}
