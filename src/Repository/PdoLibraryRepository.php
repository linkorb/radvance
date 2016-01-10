<?php

namespace Radvance\Repository;

use Radvance\Model\Library;

class PdoLibraryRepository extends BaseRepository implements RepositoryInterface
{
    public function createEntity()
    {
        return Library::createNew();
    }

    public function findByAccountName($accountName)
    {
        return $this->findBy(
            array('account_name' => $accountName)
        );
    }

    public function findByNameAndAccountName($name, $accountName)
    {
        return $this->findOneOrNullBy(
            array(
                'account_name' => $accountName,
                'name' => $name,
            )
        );
    }
}
