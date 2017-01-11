<?php

namespace Radvance\Model;

abstract class Permission extends BaseModel implements PermissionInterface
{
    protected $id;
    protected $username;

    public function getUsername()
    {
        return $this->username;
    }

    public function getRoles()
    {
        return property_exists($this, 'roles') ? $this->roles : '';
    }
    
    public function hasRole($name)
    {
        $roles = explode(",", $this->roles);
        return in_array($name, $roles);
    }
}
