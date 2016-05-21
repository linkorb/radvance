<?php

namespace Radvance\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PDOException;

class BaseController
{
    use CrudControllerTrait;
    
    /**
     * @param string $model_name Model name
     */
    public function __construct($model_name = null)
    {
        $this->model_name = $model_name;
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
        return $this->delete($app, $request, $id);
    }
}
