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

    public function add($username, $spaceId, $roles = null, $expiredate = null)
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

        if (property_exists($permission, 'roles')) {
            $permission->setRoles($roles);
        }
        if (property_exists($permission, 'expiredate')) {
            $permission->setExpiredate($expiredate);
        }
        try {
            $this->persist($permission);
        } catch (\Exception $e) {
            $error = 'error persisting permission';
        }

        return $error;
    }

    public function findOneOrNullBySpaceIdAndId($spaceId, $id)
    {
        return $this->findOneOrNullBy(array($this->spaceTableForeignKeyName => $spaceId, 'id' => $id));
    }
}
