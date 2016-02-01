<?php

namespace Radvance\Repository;

use Radvance\Model\Permission;

class PdoPermissionRepository extends BaseRepository implements RepositoryInterface
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
}
