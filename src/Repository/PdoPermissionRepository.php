<?php

namespace Radvance\Repository;

use Radvance\Model\Permission;

class PdoPermissionRepository extends BaseRepository implements PermissionRepositoryInterface
{
    public function createEntity()
    {
        return Permission::createNew();
    }

    public function findBySpaceId($spaceId)
    {
        return $this->findBy(
            array('space_id' => $spaceId)
        );
    }

    public function getModelClassName()
    {
        return '\Radvance\Model\Permission';
    }
}
