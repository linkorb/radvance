<?php

namespace Radvance\Repository;

use Radvance\Model\Library;
use PDO;

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

    public function findByUsername($username)
    {
        $statement = $this->pdo->prepare(sprintf(
            "SELECT l.* FROM `%s` AS l
            INNER JOIN `%s` AS p ON p.library_id = l.id
            ORDER BY l.account_name, l.name",
            $this->getTable(),
            'permission'
        ));
        $statement->execute();

        return $this->rowsToObjects($statement->fetchAll(PDO::FETCH_ASSOC));
    }
}
