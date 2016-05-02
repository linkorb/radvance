<?php

namespace Radvance\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PDOException;
use Doctrine\Common\Inflector\Inflector;

class BaseController
{
    private $model_name;

    /**
     * @param string $model_name Model name
     */
    public function __construct($model_name = null)
    {
        if (is_null($model_name)) {
            $model_name = get_class($this);
            $model_name = explode('\\', $model_name);
            $model_name = end($model_name);
            $model_name = substr($model_name, 0, -strlen('Controller'));

            $model_name = Inflector::camelize($model_name);
        }
        $this->model_name = $model_name;
    }

    /**
     * @return string Model name
     */
    public function getModelName()
    {
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

    /**
     * @param Application $app
     * @param Request     $request
     * @param array       $parameters
     *
     * @return Response
     */
    public function indexAction(Application $app, Request $request)
    {
        return $this->renderIndex($app, array(
            'entities' => $app->getRepository($this->getModelName())->findAll(),
        ));
    }

    /**
     * @param Application $app
     * @param Request     $request
     * @param array       $parameters
     *
     * @return Response
     */
    public function viewAction(Application $app, Request $request, $id)
    {
        return $this->renderView($app, array(
            'entity' => $app->getRepository($this->getModelName())->find($id),
        ));
    }

    /**
     * @param Application $app
     * @param Request     $request
     * @param array       $parameters
     *
     * @return Response
     */
    public function addAction(Application $app, Request $request)
    {
        return $this->getEditForm($app, $request);
    }

    /**
     * @param Application $app
     * @param Request     $request
     * @param array       $parameters
     *
     * @return Response
     */
    public function editAction(Application $app, Request $request, $id)
    {
        return $this->getEditForm($app, $request, $id);
    }

    /**
     * @param Application $app
     * @param Request     $request
     * @param array       $parameters
     *
     * @return Response
     */
    public function deleteAction(Application $app, Request $request, $id)
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
