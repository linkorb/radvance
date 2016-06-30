<?php

namespace Radvance\Repository;

interface SpaceRepositoryInterface extends GlobalRepositoryInterface
{
    public function getModelClassName();
    public function getNameOfSpace($plural = false);
    public function findByUsername($username);
    public function findByAccountName($accountName);
    public function findByNameAndAccountName($name, $accountName);
    public function findByAccountNameSpaceNameUsername($accountName, $spaceName, $username);
}
