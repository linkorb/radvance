<?php

namespace LinkORB\Framework\Model;

interface ModelInterface
{
    /**
     * @return array
     */
    public function toArray();

    /**
     * @param  array $data
     * @return ModelInterface
     */
    public function loadFromArray($data);
}
