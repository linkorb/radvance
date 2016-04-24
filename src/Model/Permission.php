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
}
