<?php

namespace Radvance\Model;

abstract class Space extends BaseModel implements SpaceInterface
{
    protected $id;
    protected $name;
    protected $account_name;
    protected $description;
    protected $created_at;
    protected $deleted_at;

    public function getName()
    {
        return $this->name;
    }

    public function getAccountName()
    {
        return $this->account_name;
    }

    public function getDescription()
    {
        return $this->description;
    }
}
