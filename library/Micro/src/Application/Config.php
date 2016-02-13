<?php

namespace Micro\Application;

class Config implements \ArrayAccess
{
    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var boolean
     */
    protected $cacheable = \true;

    /**
     * @var array
     */
    protected $cache = [];

    /**
     * @param array $data
     * @param boolean $cacheable
     */
    public function __construct($data = \null, $cacheable = \true)
    {
        if ($data !== \null) {
            $this->load($data);
        }

        $this->cacheable = (bool) $cacheable;
    }

    /**
     * @param array $data
     * @return Config
     */
    public function load(array $data)
    {
        $this->config = \array_replace_recursive(
            $this->config,
            $data
        );

        return $this;
    }

    /**
     * @param string $prop
     * @param mixed $default
     * @return mixed
     */
    public function get($prop = \null, $default = \null)
    {
        $config = $this->config;

        if ($prop !== \null && \is_string($prop)) {

            if ($this->cacheable && (isset($this->cache[$prop]) || array_key_exists($prop, $this->cache))) {
                return $this->cache[$prop];
            }

            foreach (explode('.', $prop) as $key) {
                if (!isset($config[$key]) && !array_key_exists($key, $config)) {
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

    /**
     * (non-PHPdoc)
     * @see ArrayAccess::offsetExists()
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * (non-PHPdoc)
     * @see ArrayAccess::offsetGet()
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * (non-PHPdoc)
     * @see ArrayAccess::offsetSet()
     */
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    /**
     * (non-PHPdoc)
     * @see ArrayAccess::offsetUnset()
     */
    public function offsetUnset($offset)
    {
    }
}