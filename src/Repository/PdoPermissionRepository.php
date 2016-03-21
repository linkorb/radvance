<?php

namespace Radvance\Repository;

abstract class PdoPermissionRepository extends BaseRepository implements PermissionRepositoryInterface
{
    protected $modelClassName = null;
    protected $spaceTableForeignKeyName = null;

    public function createEntity()
    {
        $class = $this->getModelClassName();
        return $class::createNew();
    }

    public function getModelClassName()
    {
        return $this->modelClassName;
    }

    public function getSpaceTableForeignKeyName()
    {
        return $this->spaceTableForeignKeyName;
    }

    public function findBySpaceId($spaceId)
    {
        return $this->findBy(
            array($this->spaceTableForeignKeyName => $spaceId)
        );
    }

    public function add($username, $spaceId)
    {
        $error = null;

        $class = $this->getModelClassName();
        $foreignMethod = 'set';
        $foreignKey = explode('_', $this->spaceTableForeignKeyName);
        foreach ($foreignKey as $key) {
            $foreignMethod .= ucfirst($key);
        }

        $permission = new $class();
        $permission->setUsername($username);
        $permission->$foreignMethod($spaceId);
        try {
            $this->persist($permission);
        } catch (\Exception $e) {
            $error = 'user exists';
        }

        return $error;
    }
}
