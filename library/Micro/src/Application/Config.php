<?php

namespace Micro\Application;

class Config implements \ArrayAccess
{
    protected $config = [];
    protected $cacheable = \true;
    protected $cache = [];

    public function __construct($data = \null, $cacheable = \true)
    {
        if ($data !== \null) {
            $this->load($data);
        }

        $this->cacheable = $cacheable;
    }

    public function load(array $data)
    {
        $this->config = \array_replace_recursive(
            $this->config,
            $data
        );

        return $this;
    }

    public function get($prop = \null, $default = \null)
    {
        $config = $this->config;

        if ($prop !== \null && \is_string($prop)) {

            if ($this->cacheable && array_key_exists($prop, $this->cache)) {
                return $this->cache[$prop];
            }

            foreach (explode('.', $prop) as $key) {
                if (!array_key_exists($key, $config)) {
                    if ($this->cacheable) {
                        $this->cache[$prop] = $default;
                    }
                    return $default;
                }
                $config = &$config[$key];
            }

            if ($this->cacheable) {
                $this->cache[$prop] = $config;
            }
        }

        return $config;
    }

    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
    }
}