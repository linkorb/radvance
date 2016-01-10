<?php

namespace Radvance\Controller;

use Radvance\Framework\BaseWebApplication as Application;
use Radvance\Model\Permission;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionController
{
    public function indexAction(Application $app, Request $request, $accountName, $libraryName)
    {
        $library = $app->getRepository('library')->findByNameAndAccountName($libraryName, $accountName);

        return new Response($app['twig']->render(
            '@BaseTemplates/permission/index.html.twig',
            array(
                // 'libraries' => $libraries,
                'accountName' => $accountName,
                'libraryName' => $libraryName,
                'permissions' => $app->getRepository('permission')->findByLibraryId($library->getId()),
                'error' => $request->query->get('error'),
            )
        ));
    }

    public function addAction(Application $app, Request $request, $accountName, $libraryName)
    {
        $username = trim($request->request->get('P_username'));

        $library = $app->getRepository('library')->findByNameAndAccountName($libraryName, $accountName);
        $error = null;
        if ($library) {
            $repo = $app->getRepository('permission');
            $permission = new Permission();
            $permission->setUsername($username)->setLibraryId($library->getId());
            if (!$repo->persist($permission)) {
                $error = 'user exists';
            }
        } else {
            $error = 'Invalid library';
        }

        return $app->redirect(
            $app['url_generator']->generate(
                'permission_index',
                array('accountName' => $accountName, 'libraryName' => $libraryName, 'error' => $error)
            )
        );
    }

    public function deleteAction(Application $app, Request $request, $accountName, $libraryName, $permissionId)
    {
        $library = $app->getRepository('library')->findByNameAndAccountName($libraryName, $accountName);
        if ($library) {
            $app->getRepository('permission')->remove(
                $app->getRepository('permission')->find($permissionId)
            );
        }

        return $app->redirect(
            $app['url_generator']->generate(
                'permission_index',
                array('accountName' => $accountName, 'libraryName' => $libraryName)
            )
        );
    }
}
