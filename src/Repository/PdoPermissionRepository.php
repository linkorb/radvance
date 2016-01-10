<?php

namespace Radvance\Repository;

use Radvance\Model\Permission;

class PdoPermissionRepository extends BaseRepository implements RepositoryInterface
{
    public function createEntity()
    {
        return Permission::createNew();
    }

    public function findByLibraryId($libraraId)
    {
        return $this->findBy(
            array('library_id' => $libraraId)
        );
    }
}
