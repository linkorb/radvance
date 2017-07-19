<?php

namespace Radvance\Controller;

use Radvance\Framework\BaseWebApplication as Application;
use Radvance\Model\Space;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Radvance\Constraint\CodeConstraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Form\FormError;

class SpaceController
{
    protected function isAccountOwner(AdvancedUserInterface $user, $accountName)
    {
        $authorized = $user->getUsername() == $accountName;
        if (!$authorized && method_exists($user, 'getAccounts')) {
            // userbase user provider
            foreach ($user->getAccounts() as $a) {
                foreach ($a->getAccountUsers() as $au) {
                    if ($au->isOwner() && $au->getAccountName() == $accountName && $au->getUsername() == $user->getUsername()) {
                        $authorized = true;
                        break 2;
                    }
                }
            }
        }
        if (!$authorized) {
            throw new \Exception('Not authorized to manage the account');
        }
    }

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
        if (!isset($app['current_user'])) {
            return $app->redirect($app['url_generator']->generate('login'));
        }
        $this->isAccountOwner($app['current_user'], $accountName);

        // $space = $app->getSpaceRepository()->findByNameAndAccountName($spaceName, $accountName);
        $repo = $app->getSpaceRepository();
        $space = $repo->findByNameAndAccountName($spaceName, $accountName);
        if (!$space) {
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
        if (!isset($app['current_user'])) {
            return $app->redirect($app['url_generator']->generate('login'));
        }
        $this->isAccountOwner($app['current_user'], $accountName);

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
        if (property_exists($space, 'fqdn')) {
            $defaults['fqdn'] = $space->getFqdn();
        }

        $form = $app['form.factory']->createBuilder('form', $defaults)
            ->add('account_name', 'text', array('read_only' => true))
            ->add('name', 'text', array(
                'required' => true,
                'trim' => true,
                'constraints' => array(new CodeConstraint(array(
                        //'message' => 'Name can contain lower case, number and - only',
                    )),
                ),
            ))
            ->add('description', 'textarea', array('required' => false));
        if (property_exists($space, 'fqdn')) {
            $form = $form->add('fqdn', 'text', array('required' => false));
        }
        $form = $form->getForm();

        // handle form submission
        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();
            $space->setName($data['name'])
                ->setDescription($data['description']);
            if (method_exists($space, 'setCreatedAt')) {
                $space->setCreatedAt();
            }
            if (property_exists($space, 'fqdn')) {
                $space->setFqdn($data['fqdn']);
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
        if (!isset($app['current_user'])) {
            return $app->redirect($app['url_generator']->generate('login'));
        }
        $this->isAccountOwner($app['current_user'], $accountName);

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

    public function newAction(Application $app, Request $request, $id = null)
    {
        if (!isset($app['current_user'])) {
            return $app->redirect($app['url_generator']->generate('login'));
        }
        $error = $request->query->get('error');
        $repo = $app->getSpaceRepository();
        $space = $repo->findOrCreate($id);
        $add = !$id;

        if ($add) {
            if ($accountName = $request->get('accountName')) {
                $space->setAccountName($accountName);
            } else {
                $space->setAccountName($app['current_user']->getName());
            }
        }
        // GENERATE FORM //
        $defaults = array();
        $accounts = $app['current_user']->getAccounts();

        $accountArray = array();
        foreach ($accounts as $account) {
            foreach ($account->getAccountUsers() as $accountUser) {
                if ($accountUser->getUserName() == $app['current_user']->getName() && $accountUser->isOwner()) {
                    $accountArray[$accountUser->getAccountName()] = $accountUser->getAccountName();
                }
            }
        }

        $form = $app['form.factory']->createBuilder('form', $space)
        ->add('account_name', 'choice', array(
            'required' => true,
            'trim' => true,
            'choices' => $accountArray,
            'constraints' => array(new Assert\NotBlank(array('message' => 'Account Name Required'))),
            'attr' => array(
                'autofocus' => true,
            ),
        ))
        ->add('name', 'text', array(
            'required' => true,
            'trim' => true,
            'constraints' => array(new CodeConstraint(array(
                    //'message' => 'Name can contain lower case, number and - only',
                )),
            ),
        ))
        ->add('description', 'textarea', array(
            'required' => false,
            'trim' => true,
        ))
        ->getForm();

        // handle form submission
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            $data = $form->getData();

            // check user is owner of account //
            if (!in_array($data->account_name, $accountArray)) {
                $form->get('account_name')->addError(new FormError('You do not have permission for this account.'));
            }
            // Check accoutname and spacename exists//
            if ($findSpace = $repo->findByNameAndAccountName($data->name, $data->account_name)) {
                $form->get('name')->addError(new FormError('Name already exists in current account.'));
            }

            if ($form->isValid()) {
                if (method_exists($space, 'setCreatedAt')) {
                    $space->setCreatedAt();
                }
                $space->loadFromArray($data);
                if (!$repo->persist($space)) {
                    return $app->redirect($app['url_generator']->generate('space_index', array(
                            'error' => 'Space exists',
                            'accountName' => $space->getAccountName(),
                        )));
                } else {
                    // auto-add permission
                    if ($add) {
                        $app->getPermissionRepository()->add($app['current_user']->getName(), $space->getId(), 'ADMIN');
                    }
                }

                return $app->redirect($app['url_generator']->generate('space_index',
                    array('accountName' => $space->getAccountName())
                ));
            }
        }

        return new Response($app['twig']->render('@BaseTemplates/space/new.html.twig',
            array(
                'form' => $form->createView(),
                'error' => $error,
            )
        ));
    }
}
