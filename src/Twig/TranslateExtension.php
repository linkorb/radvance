<?php

namespace Radvance\Twig;

use Silex\Application as SilexApplication;
use Symfony\Component\HttpFoundation\Request;
use Twig_Extension;

class TranslateExtension extends Twig_Extension
{
    private $app;
    private $request;

    public function __construct(Request $request, SilexApplication $app)
    {
        $this->app = $app;
        $this->request = $request;
    }

    public function getName()
    {
        return "translate";
    }

    public function getFilters()
    {
        return array(
            "trans" => new \Twig_SimpleFilter(
                "trans",
                array($this, 't'),
                array('needs_context' => true, 'needs_environment' => true)
            )
        );
    }

    public function t(\Twig_Environment $env, $context, $string)
    {
        if (!$string) {
            return '';
        }
        if ($string[0]=='.') {
            $route = $this->request->attributes->get('_route');
            $string = 'route.' . $route . $string;
        }
        $this->app['translator']->setLocale($this->app['locale']);
        return $this->app['translator']->trans($string);
    }
}
