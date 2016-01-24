<?php

namespace Radvance\Controller;

use Radvance\Framework\BaseWebApplication as Application;
use Radvance\Model\Library;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LibraryController
{
    public function indexAction(Application $app, Request $request, $accountName)
    {
        $libraries = $app->getRepository('library')->findByAccountName($accountName);

        return new Response($app['twig']->render(
            '@BaseTemplates/library/index.html.twig',
            array(
                'libraries' => $libraries,
                'accountName' => $accountName,
            )
        ));
    }
    
    public function viewAction(Application $app, Request $request, $accountName, $libraryName)
    {
        $repo = $app->getRepository('library');

        $library = $repo->findByNameAndAccountName($libraryName, $accountName);

        return new Response($app['twig']->render(
            '@BaseTemplates/library/view.html.twig',
            array(
                'library' => $library
            )
        ));
    }

    public function addAction(Application $app, Request $request, $accountName)
    {
        return $this->getLibraryEditForm($app, $request, $accountName);
    }

    public function editAction(Application $app, Request $request, $accountName, $libraryName)
    {
        return $this->getLibraryEditForm($app, $request, $accountName, $libraryName);
    }

    private function getLibraryEditForm(Application $app, Request $request, $accountName, $libraryName = null)
    {
        $error = $request->query->get('error');
        $repo = $app->getRepository('library');
        $add = false;
        $libraryName = trim($libraryName);

        // $library = $repo->findOneOrNullBy(array('id' => $libraryName));
        $library = $repo->findByNameAndAccountName($libraryName, $accountName);

        if (null === $library) {
            $add = true;
            $defaults = array(
                'account_name' => $accountName,
            );
            $library = new Library();
            $library->setAccountName($accountName);
        } else {
            $defaults = array(
                'account_name' => $accountName,
                'name' => $library->getName(),
                'description' => $library->getDescription(),
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
            $library->setName($data['name'])
                ->setDescription($data['description']);

            if (!$repo->persist($library)) {
                return $app->redirect(
                    $app['url_generator']->generate(
                        'library_add',
                        array(
                            'error' => 'Library exists',
                            'accountName' => $accountName,
                        )
                    )
                );
            }

            return $app->redirect(
                $app['url_generator']->generate(
                    'library_index',
                    array('accountName' => $accountName)
                )
            );
        }

        return new Response($app['twig']->render(
            '@BaseTemplates/library/edit.html.twig',
            array(
                'form' => $form->createView(),
                'library' => $library,
                'error' => $error,
                'accountName' => $accountName,
            )
        ));
    }

    public function deleteAction(Application $app, Request $request, $accountName, $libraryName)
    {
        $library = $app->getRepository('library')
            ->findByNameAndAccountName($libraryName, $accountName);
        if ($library) {
            $app->getRepository('library')->remove($library);
        }

        return $app->redirect(
            $app['url_generator']->generate(
                'library_index',
                array('accountName' => $accountName)
            )
        );
    }
}
