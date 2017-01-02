<?php

namespace Radvance\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Less_Parser;

class AssetsController
{
    public function styleAction(Application $app, Request $request)
    {
        $parser = new Less_Parser();

        $filename = sprintf('%s/style.less', $app->getThemePath(true));
        $baseUrl = '';
        if (isset($app['parameters']['baseurl'])) {
            $baseUrl = $app['parameters']['baseurl'];
        }
        $parser->parseFile($filename, $baseUrl);

        return new Response($parser->getCss(), 200, array(
            'Content-Type' => 'text/css',
        ));
    }

    public function serveAction(Application $app, Request $request, $postfix)
    {
        // Thanks StackOverflow!
        // http://stackoverflow.com/questions/2668854/sanitizing-strings-to-make-them-url-and-filename-safe
        // Remove anything which isn't a word, whitespace, number
        // or any of the following caracters -_~,;:[]().
        $postfix = preg_replace("([^\w\s\d\-_~,;:\[\]\(\).\/])", '', $postfix);
        // Remove any runs of periods (thanks falstro!)
        $postfix = preg_replace("([\.]{2,})", '', $postfix);

        $filename = sprintf('%s/%s', $app->getAssetsPath(), $postfix);
        if (!file_exists($filename)) {
            $app->abort(
                404,
                sprintf('The asset "%s" cannot be found.', $postfix)
            );
        }
        $options = array();
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        switch ($extension) {
            case 'png':
                $options['Content-Type'] = 'image/png';
                break;
            case 'jpg':
                $options['Content-Type'] = 'image/jpg';
                break;
            case 'css':
                $options['Content-Type'] = 'text/css';
                break;
            case 'js':
                $options['Content-Type'] = 'application/javascript';
                break;
            default:
                $options['Content-Type'] = 'application/octet-stream';
                $options['Content-Disposition'] = 'attachment;filename="'.basename($filename).'"';
                break;
        }

        $data = file_get_contents($filename);

        return new Response($data, 200, $options);
    }
}
