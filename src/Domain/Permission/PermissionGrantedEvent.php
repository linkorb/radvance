<?php

namespace Radvance\Domain\Permission;

use Radvance\Event\BaseStoredEvent;

class PermissionGrantedEvent extends BaseStoredEvent
{
    protected $username;
    protected $grantee;
    protected $role;
}
