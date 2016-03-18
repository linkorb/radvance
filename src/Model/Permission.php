<?php

namespace Radvance\Model;

class Permission extends BaseModel implements PermissionInterface
{
    protected $id;
    protected $username;
    protected $space_id;
    protected $level;

    public function getUsername()
    {
        return $this->username;
    }
}
