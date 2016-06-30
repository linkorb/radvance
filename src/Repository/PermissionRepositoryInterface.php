<?php

namespace Radvance\Repository;

interface PermissionRepositoryInterface extends GlobalRepositoryInterface
{
    public function getModelClassName();
    public function findBySpaceId($spaceId);
}
