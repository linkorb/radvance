<?php

namespace Radvance\Component\Security;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class JsonFileUserProvider implements UserProviderInterface
{
    private $jsonFilePath;

    public function __construct($jsonFilePath)
    {
        if (!file_exists($jsonFilePath)) {
            throw new UsernameNotFoundException('security config file does not exist.');
        }
        $this->jsonFilePath = $jsonFilePath;
    }

    private function loadConfigurationFromFile()
    {
        return json_decode(file_get_contents($this->jsonFilePath), true);
    }

    public function loadUserByUsername($username)
    {
        $config = $this->loadConfigurationFromFile();
        $userConfig = $config['users'][$username];
        if (!$userConfig) {
            throw new UsernameNotFoundException(sprintf('User %s is not found.', $username));
        }

        return new User($username, $userConfig['password'], $userConfig['roles'], true, true, true, true);
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class)
    {
        return $class === 'Symfony\Component\Security\Core\User\User';
    }
}
