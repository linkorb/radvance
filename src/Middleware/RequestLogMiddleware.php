<?php

namespace Radvance\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class RequestLogMiddleware implements HttpKernelInterface
{
    private $app;
    private $urls;

    public function __construct(
        HttpKernelInterface $app,
        $urls
    ) {
        $this->app = $app;
        $this->urls = $urls;
    }
    
    protected function log($data)
    {
        foreach ($this->urls as $url) {
            $url = trim($url);
            $url = parse_url($url);
            
            switch ($url['scheme']) {
                case 'json-path':
                    $path = __DIR__ . '/../../' . $url['host'] . $url['path'];
                    
                    $path = str_replace('{date}', date('Ymd'), $path);

                    $json = json_encode($data, JSON_PARTIAL_OUTPUT_ON_ERROR|JSON_UNESCAPED_SLASHES);
                    //$path = $this->getRootPath() . '/app/logs/requests/' . date('Ymd') . '/';
                    if (!file_exists($path)) {
                        mkdir($path, 0777, true);
                    }
                    $filename = $path . '/' . date('Ymd-His') . '-' . $data['request-id'] . '.json';
                    file_put_contents($filename, $json . "\n");
                    break;

                case 'gelf-udp':
                    $transport = new \Gelf\Transport\UdpTransport(
                        $url['host'],
                        $url['port'],
                        \Gelf\Transport\UdpTransport::CHUNK_SIZE_WAN
                    );
                    $gelfData = $data;
                    $publisher = new \Gelf\Publisher();
                    $publisher->addTransport($transport);
                    $message = new \Gelf\Message();
                    $message->setShortMessage("Request")
                        ->setLevel(\Psr\Log\LogLevel::DEBUG)
                        ->setFullMessage("")
                        ->setFacility("")
                        ->setHost($data['host'])
                    ;
                    $gelfData['host'] = null;
                    $gelfData['datetime'] = null;
                    array_filter($gelfData);
                    foreach ($gelfData as $key => $value) {
                        $message->setAdditional($key, $value);
                    }
                    $res = $publisher->publish($message);
                    break;
                default:
                    throw new RuntimeException('Unsupported request_log url scheme: ' . $url['scheme']);
            }
        }
    }

    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        $data = [
            'request-id' => $request->headers->get('X-Request-Id'),
            'datetime' => date('Y-m-d H:i:s'),
            'method' => $request->getMethod(),
            'scheme' => $request->getScheme(),
            'host' => $request->getHttpHost(),
            'uri' => $request->getRequestUri(),
            'route' => $request->get('_route'),
        ];
        /*
        if (isset($this['current_user'])) {
            $data['username'] = $this['current_user']->getName();
        }
        */
        $data['address'] = $request->getClientIp();
        if ($request->getSession()) {
            $data['session-id'] = $request->getSession()->getId();
        }
        if ($request->headers->has('User-Agent')) {
            $data['agent'] = $request->headers->get('User-Agent');
        }
        if ($request->headers->has('referer')) {
            $data['referer'] = $request->headers->get('referer');
        }
        $this->log($data);
        
        
        $response = $this->app->handle($request, $type, $catch);

        /*
        // response details
        $data['status'] = $response->getStatusCode();
        if ($response->headers->has('Content-Type')) {
            $data['content-type'] = $response->headers->get('content-type');
        }
        */
        
        return $response;
    }
}
