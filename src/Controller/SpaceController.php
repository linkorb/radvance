<?php

namespace Radvance\Controller;

use Radvance\Framework\BaseWebApplication as Application;
use Radvance\Model\Space;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SpaceController
{
    public function indexAction(Application $app, Request $request, $accountName)
    {
        $repo = $app->getSpaceRepository();

        return new Response($app['twig']->render(
            '@BaseTemplates/space/index.html.twig',
            array(
                'spaces' => $repo->findByAccountName($accountName),
                'accountName' => $accountName,
                'nameOfSpace' => $repo->getNameOfSpace(),
                'nameOfSpacePl' => $repo->getNameOfSpace(true),
            )
        ));
    }

    public function viewAction(Application $app, Request $request, $accountName, $spaceName)
    {
        // $space = $app->getSpaceRepository()->findByNameAndAccountName($spaceName, $accountName);
        $repo = $app->getSpaceRepository();
        $space = $repo->findByNameAndAccountName($spaceName, $accountName);
        if (! $space) {
            $app->abort(
                404,
                sprintf('The %s "%s" cannot be found.', $repo->getNameOfSpace(), $spaceName)
            );
        }

        return new Response($app['twig']->render(
            '@BaseTemplates/space/view.html.twig',
            array(
                'space' => $space,
                'nameOfSpace' => $repo->getNameOfSpace(),
            )
        ));
    }

    public function addAction(Application $app, Request $request, $accountName)
    {
        return $this->getSpaceEditForm($app, $request, $accountName);
    }

    public function editAction(Application $app, Request $request, $accountName, $spaceName)
    {
        return $this->getSpaceEditForm($app, $request, $accountName, $spaceName);
    }

    private function getSpaceEditForm(Application $app, Request $request, $accountName, $spaceName = null)
    {
        $error = $request->query->get('error');
        $repo = $app->getSpaceRepository();
        $add = false;
        $spaceName = trim($spaceName);

        $space = $repo->findByNameAndAccountName($spaceName, $accountName);

        if (null === $space) {
            $add = true;
            $defaults = array(
                'account_name' => $accountName,
            );
            $spaceClassName = $app['spaceModelClassName'];
            $space = new $spaceClassName();
            $space->setAccountName($accountName);
        } else {
            $defaults = array(
                'account_name' => $accountName,
                'name' => $space->getName(),
                'description' => $space->getDescription(),
            );
        }

        $form = $app['form.factory']->createBuilder('form', $defaults)
            ->add('account_name', 'text', array('read_only' => true))
            ->add('name', 'text')
            ->add('description', 'textarea', array('required' => false))
            ->getForm();

        // handle form submission
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();
            $space->setName($data['name'])
                ->setDescription($data['description']);
            if (method_exists($space, 'setCreatedAt')) {
                $space->setCreatedAt();
            }

            if (!$repo->persist($space)) {
                return $app->redirect(
                    $app['url_generator']->generate(
                        'space_add',
                        array(
                            'error' => 'Space exists',
                            'accountName' => $accountName,
                        )
                    )
                );
            } else {
                // auto-add permission
                if ($add) {
                    $app->getPermissionRepository()->add($app['current_user']->getName(), $space->getId(), 'ADMIN');
                }
            }

            return $app->redirect(
                $app['url_generator']->generate(
                    'space_index',
                    array('accountName' => $accountName)
                )
            );
        }

        return new Response($app['twig']->render(
            '@BaseTemplates/space/edit.html.twig',
            array(
                'form' => $form->createView(),
                'space' => $space,
                'error' => $error,
                'accountName' => $accountName,
                'nameOfSpace' => $repo->getNameOfSpace(),
                'nameOfSpacePl' => $repo->getNameOfSpace(true),
            )
        ));
    }

    public function deleteAction(Application $app, Request $request, $accountName, $spaceName)
    {
        $spaceRepository = $app->getSpaceRepository();
        $space = $spaceRepository->findByNameAndAccountName($spaceName, $accountName);
        if ($space) {
            $spaceRepository->remove($space);
        }

        return $app->redirect(
            $app['url_generator']->generate(
                'space_index',
                array('accountName' => $accountName)
            )
        );
    }
}
