<?php

namespace Radvance\Model\Traits;

trait CreatedAtTrait
{
    protected $created_at;

    public function getCreatedAt()
    {
        if (!$this->created_at) {
            return new \DateTime();
        }

        return $this->created_at;
    }
}
