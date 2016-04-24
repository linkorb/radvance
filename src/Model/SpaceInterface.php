<?php

namespace Radvance\Model;

interface SpaceInterface extends ModelInterface
{
    public function getName();
    public function getAccountName();
    public function getDescription();
}
