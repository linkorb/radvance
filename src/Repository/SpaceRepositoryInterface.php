<?php

namespace Radvance\Repository;

interface SpaceRepositoryInterface extends RepositoryInterface
{
    public function getModelClassName();
    public function getNameOfSpace($plural = false);
    public function findByUsername($username);
    public function findByAccountName($accountName);
    public function findByAccountNameSpaceNameUsername($accountName, $spaceName, $username);
}
