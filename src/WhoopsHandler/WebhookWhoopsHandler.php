<?php

namespace Radvance\WhoopsHandler;

use Silex\Application;
use Whoops\Handler\Handler;


class WebhookWhoopsHandler extends Handler
{
    private $application;
    private $url;
    
    /**
     * @var bool
     */

    public function __construct(Application $application, $url)
    {
        $this->application = $application;
        $this->url = $url;
    }

    /**
     * @inherit
     */
    public function handle()
    {
        $formatter = new Formatter();
        $data = $formatter->getData($this->getInspector());
        
        $json = json_encode($data, JSON_PARTIAL_OUTPUT_ON_ERROR|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
        
        $data = [];
        $data['event'] = 'exception';
        //$data['event-id'] = Uuid::uuid4();
        $data['datetime'] = date('Y-m-d H:i:s');
        $data['json'] = $json;

        /*
        if ($user) {
            $data['user'] = [
                'name' => $user->getName(),
                'display_name' => $user->getDisplayName(),
                'email' => $user->getEmail(),
                'mobile' => $user->getMobile()
            ];
        }
        */
        
        $verify = __DIR__ . '/../../cacert.pem';
        if (!file_exists($verify)) {
            throw new RuntimeException($verify . ' not found');
        }
        
        $headers = [
            'Content-Type' => 'application/json'
        ];

        $guzzle = new \GuzzleHttp\Client(
            [
                'headers' => $headers,
                'verify' => $verify
            ]
        );
        $response = $guzzle->request('POST', $this->url, ['json' => $data]);
        
        return Handler::DONE;
    }
}
