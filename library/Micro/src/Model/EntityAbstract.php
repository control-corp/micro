<?php

namespace Micro\Model;

abstract class EntityAbstract implements EntityInterface
{
    public function setFromArray(array $data)
    {
        $reflection = new \ReflectionClass($this);

        foreach ($data as $k => $v) {
            $method = 'set' . ucfirst($k);
            if ($reflection->hasMethod($method)) {
                $this->$method($v);
            } else {
                $this->$k = $v;
            }
        }

        return $this;
    }

    public function toArray()
    {
        $data = [];

        foreach ($this as $k => $v) {
            $data[$k] = $v;
        }

        return $data;
    }

    public function toJson()
    {
        return json_encode($this->toArray());
    }

    public function __toString()
    {
        return get_class($this);
    }

    public function offsetExists ($offset)
    {
        return property_exists($this, $offset);
    }

    /**
     * @param mixed $offset
     * @return \null
     */
    public function offsetGet ($offset)
    {
        return $this->$offset;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet ($offset, $value)
    {
        $this->$offset = $value;
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset ($offset)
    {
        $this->$offset = \null;
    }

    /**
     * @param string $offset
     * @return \null
     */
    public function __get($offset)
    {
        return $this->$offset;
    }

    /**
     * @param string $offset
     * @param mixed $value
     */
    public function __set($offset, $value)
    {
        $this->$offset = $value;
    }

    /**
     * @param string $offset
     * @return bool
     */
    public function __isset($offset)
    {
        return property_exists($this, $offset);
    }

    /**
     * @param string $offset
     */
    public function __unset($offset)
    {
        $this->$offset = \null;
    }
}