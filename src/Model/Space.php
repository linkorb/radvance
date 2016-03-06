<?php

namespace Radvance\Model;

class Space extends BaseModel implements ModelInterface
{
    protected $id;
    protected $name;
    protected $account_name;
    protected $description;
    protected $created_at;
    protected $deleted_at;
}
