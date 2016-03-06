<?php

namespace Radvance\Repository;

use Radvance\Model\Space;
use PDO;

class PdoSpaceRepository extends BaseRepository implements RepositoryInterface
{
    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
    }
    
    public function getTable()
    {
        return $this->tableName;
    }
    
    public function createEntity()
    {
        return Space::createNew();
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
            WHERE p.username='%s'
            ORDER BY l.account_name, l.name",
            $this->getTable(),
            'permission',
            $username
        ));
        $statement->execute();

        return $this->rowsToObjects($statement->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getByAccountNameSpaceNameUsername($accountName, $spaceName, $username)
    {
        $statement = $this->pdo->prepare(sprintf(
            'SELECT l.* FROM `%s` AS l
            INNER JOIN `%s` AS p ON p.library_id = l.id
            WHERE p.username=:username AND l.name=:space_name AND l.account_name=:account_name
            ORDER BY l.account_name, l.name LIMIT 1',
            $this->getTable(),
            'permission'
        ));
        $statement->execute(
            [
                'username' => $username,
                'space_name' => $spaceName,
                'account_name' => $accountName,
            ]
        );

        return $this->rowToObject($statement->fetch(PDO::FETCH_ASSOC));
    }
}
