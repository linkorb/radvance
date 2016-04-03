<?php

namespace Radvance\Framework;

class SpaceConfig
{
    private $tableName;

    public function getTableName()
    {
        return $this->tableName;
    }

    public function setTableName($tableName)
    {
        $this->tableName = $tableName;

        return $this;
    }

    private $permissionToSpaceForeignKeyName = 'space_id';

    public function getPermissionToSpaceForeignKeyName()
    {
        return $this->permissionToSpaceForeignKeyName;
    }

    public function setPermissionToSpaceForeignKeyName($permissionToSpaceForeignKeyName)
    {
        $this->permissionToSpaceForeignKeyName = $permissionToSpaceForeignKeyName;

        return $this;
    }

    private $modelClassName;

    public function getModelClassName()
    {
        return $this->modelClassName;
    }

    public function setModelClassName($modelClassName)
    {
        $this->modelClassName = $modelClassName;

        return $this;
    }

    private $repositoryClassName;

    public function getRepositoryClassName()
    {
        return $this->repositoryClassName;
    }

    public function setRepositoryClassName($repositoryClassName)
    {
        $this->repositoryClassName = $repositoryClassName;

        return $this;
    }

    private $permissionClassName;

    public function getPermissionClassName()
    {
        return $this->permissionClassName;
    }

    public function setPermissionClassName($permissionClassName)
    {
        $this->permissionClassName = $permissionClassName;

        return $this;
    }

    private $displayName;
    public function getDisplayName()
    {
        return $this->displayName;
    }

    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;

        return $this;
    }

    private $displayNamePlural;

    public function getDisplayNamePlural()
    {
        return $this->displayNamePlural;
    }

    public function setDisplayNamePlural($displayNamePlural)
    {
        $this->displayNamePlural = $displayNamePlural;

        return $this;
    }
}
