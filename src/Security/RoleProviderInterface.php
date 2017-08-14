<?php

namespace Radvance\Security;

interface RoleProviderInterface
{
    // return array of ROLE_ names for given username
    public function getUserRoles($username);
}
