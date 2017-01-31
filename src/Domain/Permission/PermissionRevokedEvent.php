<?php

namespace Radvance\Domain\Permission;

use Radvance\Event\BaseStoredEvent;

class PermissionRevokedEvent extends BaseStoredEvent
{
    protected $username;
    protected $grantee;
    protected $role;
}
