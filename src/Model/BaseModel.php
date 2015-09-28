<?php

namespace LinkORB\Framework\Model;

use LinkORB\Framework\Exception\BadMethodCallException;

abstract class BaseModel
{
    public static function createNew()
    {
        $class = get_called_class();
        return new $class;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return get_object_vars($this);
    }

    public function __toString()
    {
        if (property_exists($this, 'name')) {
            return (string)$this->getName();
        }

        return (string)$this->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function loadFromArray($data, $allowed_keys = null)
    {
        // $data = (array)$data;

        // if (is_null($allowed_keys)) {
        //     $allowed_keys = array_keys($data);
        // }

        foreach ($data as $key => $value) {
            // if (!in_array($key, $allowed_keys)) {
            //     continue;
            // }

            $setter = sprintf('set%s', $key);
            $this->$setter($value);
        }

        return $this;
    }

    /**
     * Magic getters/setters
     *
     * @param  mixed $name
     * @param  mixed $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (!preg_match('/^(get|set)(.+)$/', $name, $matchesArray)) {
            throw new BadMethodCallException(
                sprintf(
                    'Method "%s" does not exist on entity "%s"',
                    $name,
                    get_class($this)
                )
            );
        }

        // CamelCase to underscored
        $propertyName = strtolower(preg_replace('/([A-Z])/', '_$1', lcfirst($matchesArray[2])));

        if (!property_exists($this, $propertyName)) {
            throw new BadMethodCallException(
                sprintf(
                    'Entity %s does not have a property named %s',
                    get_class($this),
                    $propertyName
                )
            );
        }

        switch ($matchesArray[1]) {
            case 'set':
                $this->$propertyName = $arguments[0];
                return $this;

            case 'get':
                return $this->$propertyName;
        }
    }
}
