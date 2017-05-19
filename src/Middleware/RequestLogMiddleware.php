<?php

namespace Radvance\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use RuntimeException;

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
        foreach ($this->urls as $urlString) {
            $urlString = trim($urlString);
            $url = parse_url($urlString);
            switch ($url['scheme']) {
                case 'json-path':
                    $path = '/' . $url['host'] . $url['path'];
                    $path = rtrim($path, '/');

                    $path = str_replace('{date}', date('Ymd'), $path);
                    $json = json_encode($data, JSON_UNESCAPED_SLASHES);
                    if (!file_exists($path)) {
                        mkdir($path, 0777, true);
                    }
                    $filename = $path . '/' . date('Ymd-His') . '-' . $data['request']['id'] . '.json';
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
                    throw new RuntimeException('Unsupported request_log url scheme: ' . $urlString);
            }
        }
    }

    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        $start = microtime(true); // ms as float
        $response = $this->app->handle($request, $type, $catch);
        $end = microtime(true); // ms as float

        $data = [];

        $data['request'] = [];
        $data['request']['id'] = $request->headers->get('X-Request-Id');
        $data['request']['ip'] = $request->getClientIp();
        $data['request']['query'] = $request->query->all();
        $data['request']['post'] = $request->request->all();
        $data['request']['attributes'] = $request->attributes->all();
        $data['request']['server'] = $request->server->all();
        $data['request']['cookies'] = $request->cookies->all();
        $data['request']['headers'] = $request->headers->all();

        $data['response'] = [];
        $data['response']['code'] =  $response->getStatusCode();
        $data['response']['headers'] = $response->headers->all();
        $data['timing'] = [];
        $data['timing']['start'] = $start;
        $data['timing']['end'] = $end;
        $data['timing']['duration'] = $end-$start;

        $this->log($data);

        return $response;
    }
}
