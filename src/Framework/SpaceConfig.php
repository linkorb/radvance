<?php

namespace Radvance\Framework;

class SpaceConfig
{
    private $tableName;
    
    public function getTableName()
    {
        return $this->tableName;
    }
    
    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
        return $this;
    }
    
    private $displayName;
    public function getDisplayName()
    {
        return $this->displayName;
    }
    
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;
        return $this;
    }
    
    private $displayNamePlural;
    
    public function getDisplayNamePlural()
    {
        return $this->displayNamePlural;
    }
    
    public function setDisplayNamePlural($displayNamePlural)
    {
        $this->displayNamePlural = $displayNamePlural;
        return $this;
    }
}
