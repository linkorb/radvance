<?php

namespace Radvance\Event;

class EventPresenter
{
    protected $name;
    protected $data;
    protected $metaData;
    protected $stamp;
    
    public function __construct($data)
    {
        $this->id = $data['id'];
        $this->name = $data['name'];
        $this->stamp = $data['stamp'];
        $this->data = json_decode($data['data'], true);
        $this->metaData = json_decode($data['meta_data'], true);
    }
    
    public function getShortName()
    {
        $part = explode("\\", $this->name);
        $res = end($part);
        $res = str_replace('Event', '', $res);
        return $res;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function getStamp()
    {
        return $this->stamp;
    }
    
    public function presentStamp()
    {
        return date('d/M/Y H:i', $this->stamp);
    }
    
    public function getId()
    {
        return $this->id;
    }
    
    public function getUsername()
    {
        return $this->metaData['username'];
    }
    public function getIp()
    {
        return $this->getMetaData('ip');
    }
    
    protected $description;
    
    public function getDescription()
    {
        return $this->description;
    }
    
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }
    
    public function getData($key)
    {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }
        return null;
    }
    public function getMetaData($key)
    {
        if (isset($this->metaData[$key])) {
            return $this->metaData[$key];
        }
        return null;
    }
}
