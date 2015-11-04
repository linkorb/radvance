<?php

namespace $$NAMESPACE$$\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Radvance\Controller\BaseController;
use $$NAMESPACE$$\Model\$$CLASS_PREFIX$$;

class $$CLASS_PREFIX$$Controller extends BaseController
{
    protected function getEditForm(Application $app, Request $request, $id = null)
    {
        $repository = $app->getRepository($this->getModelName());
        $entity = $repository->findOrCreate($id);

        $form = $app['form.factory']->createBuilder('form', $entity->toArray())
            ->add('name', 'text')
            ->add('description', 'textarea', array('required' => false))
            ->getForm()
        ;

        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();

            $entity->loadFromArray($data);

            if ($repository->persist($entity)) {
                return $app->redirect(
                    $app['url_generator']->generate(sprintf('%s_index', $this->getModelName()))
                );
            }
        }

        return $this->renderEdit($app, array(
            'form' => $form->createView(),
            'entity' => $entity
        ));
    }
}
