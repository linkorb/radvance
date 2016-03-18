<?php

namespace Radvance\Repository;

use Radvance\Model\Space;
use PDO;

class PdoSpaceRepository extends BaseRepository implements RepositoryInterface
{
    // protected $tableName;
    //
    // public function setTableName($tableName)
    // {
    //     $this->tableName = $tableName;
    //
    //     return $this;
    // }
    //
    // public function getTable()
    // {
    //     return $this->tableName;
    // }
    //
    // public function createEntity()
    // {
    //     $klass = $this->modelClassName;
    //
    //     return $klass::createNew();
    // }
    //
    // protected $modelClassName = '\Radvance\Model\Space';
    //
    // public function getModelClassName()
    // {
    //     return $this->modelClassName;
    // }
    //
    // public function setModelClassName($modelClassName)
    // {
    //     $this->modelClassName = $modelClassName;
    //
    //     return $this;
    // }
    //
    // protected $permissionToSpaceForeignKeyName = 'space_id';
    //
    // public function getPermissionToSpaceForeignKeyName()
    // {
    //     return $this->permissionToSpaceForeignKeyName;
    // }
    //
    // public function setPermissionToSpaceForeignKeyName($permissionToSpaceForeignKeyName)
    // {
    //     $this->permissionToSpaceForeignKeyName = $permissionToSpaceForeignKeyName;
    //
    //     return $this;
    // }

    public function createEntity()
    {
        return Space::createNew();
    }

    public function getNameOfSpace($plural = false)
    {
        return $plural ? 'Spaces' : 'Space';
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
            INNER JOIN `%s` AS p ON p.%s = l.id
            WHERE p.username='%s'
            ORDER BY l.account_name, l.name",
            $this->getTable(),
            'permission',
            $this->permissionToSpaceForeignKeyName,
            $username
        ));
        $statement->execute();

        return $this->rowsToObjects($statement->fetchAll(PDO::FETCH_ASSOC));
    }

    public function findByAccountNameSpaceNameUsername($accountName, $spaceName, $username)
    {
        $statement = $this->pdo->prepare(sprintf(
            'SELECT l.* FROM `%s` AS l
            INNER JOIN `%s` AS p ON p.%s = l.id
            WHERE p.username=:username AND l.name=:space_name AND l.account_name=:account_name
            ORDER BY l.account_name, l.name LIMIT 1',
            $this->getTable(),
            'permission',
            $this->permissionToSpaceForeignKeyName
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
