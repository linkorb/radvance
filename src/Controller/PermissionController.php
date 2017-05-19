<?php

namespace Radvance\Controller;

use Radvance\Framework\BaseWebApplication as Application;
use Radvance\Model\Permission;
use Radvance\Domain\Permission as PermissionDomain;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionController
{
    public function indexAction(Application $app, Request $request, $accountName, $spaceName)
    {
        // check user login //
        if (empty($app['current_user'])) {
            return $app->redirect($app['url_generator']->generate('login'));
        }
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

    public function addAction(Application $app, Request $request, EventDispatcherInterface $dispatcher, $accountName, $spaceName)
    {
        // check user login //
        if (empty($app['current_user'])) {
            return $app->redirect($app['url_generator']->generate('login'));
        }

        $username = trim($request->request->get('P_username'));
        $roles = trim($request->request->get('P_roles'));
        $expiredate = trim($request->request->get('P_expiredate'));

        $space = $app->getSpaceRepository()->findByNameAndAccountName($spaceName, $accountName);

        $error = null;

        try {
            $user = $app['security.provider']->loadUserByUsername($username);
            $account = $user->getUserAccount();

            if ($account->getStatus() != 'ACTIVE') {
                $error = 'Invalid User';
            }
        } catch (\Exception $e) {
            $error = 'User not exists';
        }

        if (!$error) {
            if ($space) {
                if ($expiredate) {
                    $expiredate = date('Y-m-d', strtotime(str_replace('/', '-', $expiredate)));
                }

                $repo = $app->getPermissionRepository();
                $error = $repo->add($username, $space->getId(), $roles, $expiredate);
                $admin = $app['current_user']->getName();
                $event = new PermissionDomain\PermissionGrantedEvent(
                    $admin,
                    $username,
                    $roles
                );
                $dispatcher->dispatch(PermissionDomain\PermissionGrantedEvent::class, $event);
            } else {
                $error = 'Invalid space';
            }
        }

        return $app->redirect(
            $app['url_generator']->generate(
                'permission_index',
                array('accountName' => $accountName, 'spaceName' => $spaceName, 'error' => $error)
            )
        );
    }

    public function deleteAction(Application $app, Request $request, EventDispatcherInterface $dispatcher, $accountName, $spaceName, $permissionId)
    {
        // check user login //
        if (empty($app['current_user'])) {
            return $app->redirect($app['url_generator']->generate('login'));
        }

        $permissionRepo = $app->getPermissionRepository();
        $space = $app->getSpaceRepository()->findByNameAndAccountName($spaceName, $accountName);

        if (!$space) {
            throw new RuntimeException('Space not found');
        }
        $permission = $permissionRepo->find($permissionId);

        if ($permission->getSpaceId() != $space->getId()) {
            throw new RuntimeException('Permission not in this space');
        }

        $admin = $app['current_user']->getName();
        $event = new PermissionDomain\PermissionRevokedEvent(
            $admin,
            $permission->getUsername(),
            ''
        );
        $dispatcher->dispatch(PermissionDomain\PermissionRevokedEvent::class, $event);

        $permissionRepo->remove($permission);

        return $app->redirect(
            $app['url_generator']->generate(
                'permission_index',
                array('accountName' => $accountName, 'spaceName' => $spaceName)
            )
        );
    }
}
