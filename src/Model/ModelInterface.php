<?php

namespace Radvance\Model;

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
