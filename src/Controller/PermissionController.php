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
        $space = $app->getRepository(
            $app->getSpaceConfig()->getTableName()
        )->findByNameAndAccountName($spaceName, $accountName);

        return new Response($app['twig']->render(
            '@BaseTemplates/permission/index.html.twig',
            array(
                'accountName' => $accountName,
                'spaceName' => $spaceName,
                'permissions' => $app->getRepository('permission')->findBySpaceId($space->getId()),
                'error' => $request->query->get('error'),
            )
        ));
    }

    public function addAction(Application $app, Request $request, $accountName, $spaceName)
    {
        $username = trim($request->request->get('P_username'));

        $space = $app->getRepository(
            $app->getSpaceConfig()->getTableName()
        )->findByNameAndAccountName($spaceName, $accountName);
        
        $error = null;
        if ($space) {
            $repo = $app->getRepository('permission');
            $permission = new Permission();
            $permission->setUsername($username)->setSpaceId($space->getId());
            if (!$repo->persist($permission)) {
                $error = 'user exists';
            }
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
        $space = $app->getRepository(
            $app->getSpaceConfig()->getTableName()
        )->findByNameAndAccountName($spaceName, $accountName);
        
        if ($space) {
            $app->getRepository('permission')->remove(
                $app->getRepository('permission')->find($permissionId)
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
