<?php

namespace Radvance\Security;


use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class RadvanceUserProvider implements UserProviderInterface
{
    protected $parent;

    public function __construct(UserProviderInterface $parent, RoleProviderInterface $roleProvider)
    {
        $this->parent = $parent;
        $this->roleProvider = $roleProvider;
    }

    public function loadUserByUsername($username)
    {
        $user = $this->parent->loadUserByUsername($username);
        // enrich local permission
        $this->enrichUser($user);
        return $user;
    }

    public function refreshUser(UserInterface $user)
    {
        $res = $this->parent->refreshUser($user);
        $res = $this->enrichUser($user);
        return $res;
    }

    private function enrichUser($user)
    {
        $roleNames = $this->roleProvider->getUserRoles($user->getUsername());
        foreach ($roleNames as $roleName) {
            if (!in_array($roleName, $user->getRoles())) {
                $user->addRole($roleName);
            }
        }
        return $user;
    }

    public function supportsClass($class)
    {
        return $this->parent->supportsClass($class);
    }
}
