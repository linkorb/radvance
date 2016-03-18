<?php

namespace Radvance\Repository;

interface PermissionRepositoryInterface extends RepositoryInterface
{
    public function getModelClassName();
    public function findBySpaceId($spaceId);
}
