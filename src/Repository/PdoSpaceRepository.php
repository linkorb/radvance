<?php

namespace Radvance\Repository;

use PDO;

abstract class PdoSpaceRepository extends BaseRepository implements SpaceRepositoryInterface
{
    protected $modelClassName = null;
    protected $nameOfSpace = null;
    protected $nameOfSpacePlural = null;
    protected $permissionTableName = null;
    protected $permissionTableForeignKeyName = null;

    public function createEntity()
    {
        $class = $this->getModelClassName();

        return $class::createNew();
    }

    public function getModelClassName()
    {
        return $this->modelClassName;
    }

    public function getNameOfSpace($plural = false)
    {
        if ($plural && $this->nameOfSpacePlural) {
            return $this->nameOfSpacePlural;
        }

        return $this->nameOfSpace;
    }

    public function getPermissionTableName()
    {
        return $this->permissionTableName;
    }

    public function getPermissionTableForeignKeyName()
    {
        return $this->permissionTableForeignKeyName;
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
            $this->permissionTableName,
            $this->permissionTableForeignKeyName,
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
            $this->permissionTableName,
            $this->permissionTableForeignKeyName
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
