<?php

namespace Radvance\Model;

class Permission extends BaseModel implements ModelInterface
{
    protected $id;
    protected $username;
    protected $space_id;
    protected $level;
}
