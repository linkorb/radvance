<?php

namespace Radvance\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\Inflector\Inflector;

trait CrudControllerTrait
{
    private $model_name;
    
    /**
     * @return string Model name
     */
    public function getModelName()
    {
        if (is_null($this->model_name)) {
            $model_name = get_class($this);
            $model_name = explode('\\', $model_name);
            $model_name = end($model_name);
            $model_name = substr($model_name, 0, -strlen('Controller'));

            $this->model_name = Inflector::tableize($model_name);
        }
        return $this->model_name;
    }
    
    protected function addDefaultParameters($app, $parameters = array())
    {
        $parameters['name'] = $this->getModelName();

        return $parameters;
    }
    
    /**
     * @param array $parameters
     *
     * @return Response
     */
    public function renderIndex(Application $app, $parameters = array())
    {
        $parameters = $this->addDefaultParameters($app, $parameters);

        return new Response($app['twig']->render(
            sprintf('%s/index.html.twig', $this->getModelName()),
            $parameters
        ));
    }

    /**
     * @param array $parameters
     *
     * @return Response
     */
    public function renderEdit(Application $app, $parameters = array())
    {
        $parameters = $this->addDefaultParameters($app, $parameters);

        return new Response($app['twig']->render(
            sprintf('%s/edit.html.twig', $this->getModelName()),
            $parameters
        ));
    }

    /**
     * @param array $parameters
     *
     * @return Response
     */
    public function renderView(Application $app, $parameters = array())
    {
        $parameters = $this->addDefaultParameters($app, $parameters);

        return new Response($app['twig']->render(
            sprintf('%s/view.html.twig', $this->getModelName()),
            $parameters
        ));
    }
    
    public function delete(Application $app, Request $request, $id)
    {
        $repo = $app->getRepository($this->getModelName());
        try {
            $repo->remove($repo->find($id));
        } catch (PDOException $e) {
            $app->addFlash('error', $e->getMessage());
        }

        // @todo Check errors

        return $app->redirect(
            $app['url_generator']->generate(sprintf('%s_index', $this->getModelName()))
        );
    }
}
