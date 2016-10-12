<?php

namespace Radvance\Controller;

use Radvance\Framework\BaseWebApplication as Application;
use Radvance\Model\Permission;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionController
{
    public function indexAction(Application $app, Request $request, $accountName, $spaceName)
    {
        $space = $app->getSpaceRepository()->findByNameAndAccountName($spaceName, $accountName);

        return new Response($app['twig']->render(
            '@BaseTemplates/permission/index.html.twig',
            array(
                'accountName' => $accountName,
                'spaceName' => $spaceName,
                'permissions' => $app->getPermissionRepository()->findBySpaceId($space->getId()),
                'error' => $request->query->get('error'),
            )
        ));
    }

    public function addAction(Application $app, Request $request, $accountName, $spaceName)
    {
        $username = trim($request->request->get('P_username'));
        $roles = trim($request->request->get('P_roles'));

        $space = $app->getSpaceRepository()->findByNameAndAccountName($spaceName, $accountName);

        $error = null;
        if ($space) {
            $repo = $app->getPermissionRepository();
            $error = $repo->add($username, $roles, $space->getId());
        } else {
            $error = 'Invalid space';
        }

        return $app->redirect(
            $app['url_generator']->generate(
                'permission_index',
                array('accountName' => $accountName, 'spaceName' => $spaceName, 'error' => $error)
            )
        );
    }

    public function deleteAction(Application $app, Request $request, $accountName, $spaceName, $permissionId)
    {
        $space = $app->getSpaceRepository()->findByNameAndAccountName($spaceName, $accountName);

        if ($space) {
            $app->getPermissionRepository()->remove(
                $app->getPermissionRepository()->find($permissionId)
            );
        }

        return $app->redirect(
            $app['url_generator']->generate(
                'permission_index',
                array('accountName' => $accountName, 'spaceName' => $spaceName)
            )
        );
    }
}
