<?php

namespace Micro\Container;

class Container implements ContainerInterface
{
    /**
     * @var array
     */
    protected $services = [];

    /**
     * @var array
     */
    protected $resolved = [];

    /**
     * @var array
     */
    protected $aliases = [];

    public function __construct(array $config = [], $useAsDefault = \true)
    {
        if ($useAsDefault === \true) {
            SharedContainer::setInstance($this);
        }

        if (isset($config['services'])) {
            foreach ($config['services'] as $id => $service) {
                $this->set($id, $service);
            }
        }

        if (isset($config['aliases'])) {
            foreach ($config['aliases'] as $alias => $service) {
                $this->alias($alias, $service);
            }
        }
    }

    /**
     * (non-PHPdoc)
     * @see \Micro\Container\ContainerInterface::get()
     */
    public function get($service)
    {
        if (isset($this->aliases[$service])) {
            $service = $this->resolveAlias($service);
        }

        if (!isset($this->services[$service])) {
            throw new \InvalidArgumentException(sprintf('[' . __METHOD__ . '] Service "%s" not found!', $service), 500);
        }

        // call resolved
        if (array_key_exists($service, $this->resolved)) {
            return $this->resolved[$service];
        }

        $result = $this->services[$service];

        if ($result instanceof \Closure) {
            $result = $result->__invoke($this);
        }

        if (is_string($result) && class_exists($result)) {
            $result = new $result;
        }

        if ($result instanceof ContainerAwareInterface) {
            $result->setContainer($this);
        }

        if (is_object($result) && method_exists($result, 'create')) {
            $result = $result->create($this, $service);
        }

        return $this->resolved[$service] = $result;
    }

    /**
     * (non-PHPdoc)
     * @see \Micro\Container\ContainerInterface::set()
     */
    public function set($service, $callback)
    {
        if (isset($this->resolved[$service])) {
            throw new \InvalidArgumentException(sprintf('[' . __METHOD__ . '] Service "%s" is resolved!', $service), 500);
        }

        $this->services[$service] = $callback;
    }

    /**
     * (non-PHPdoc)
     * @see \Micro\Container\ContainerInterface::has()
     */
    public function has($service)
    {
        return isset($this->services[$service]);
    }

    /**
     * @param string $offset
     * @param callable $callback
     * @throws \InvalidArgumentException
     * @return mixed
     */
    public function extend($offset, $callback)
    {
        if (isset($this->resolved[$offset])) {
            throw new \InvalidArgumentException(sprintf('[' . __METHOD__ . '] Service "%s" is resolved!', $offset), 500);
        }

        if (!is_object($callback) || !method_exists($callback, '__invoke')) {
            throw new \InvalidArgumentException('[' . __METHOD__ . '] Provided callback must be \Closure or implements __invoke!', 500);
        }

        $service = $this->services[$offset];

        $extended = function ($c) use ($service, $callback) {
            return $callback($service($c), $c);
        };

        return $this->set($offset, $extended);
    }

    /**
     * @param string $alias
     * @param string $service
     * @return Container
     */
    public function alias($alias, $service)
    {
        $this->aliases[$alias] = $service;

        return $this;
    }

    /**
     * @param sring $alias
     * @throws \Exception
     * @return string
     */
    public function resolveAlias($alias)
    {
        $stack = [];

        while (isset($this->aliases[$alias])) {

            if (isset($stack[$alias])) {
                throw new \Exception(sprintf(
                    'Circular alias reference: %s -> %s',
                    implode(' -> ', $stack),
                    $alias
                ));
            }

            $stack[$alias] = $alias;

            $alias = $this->aliases[$alias];
        }

        return $alias;
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
        return $this->set($offset, $value);
    }

    /**
     * (non-PHPdoc)
     * @see ArrayAccess::offsetExists()
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * (non-PHPdoc)
     * @see ArrayAccess::offsetUnset()
     */
    public function offsetUnset($offset)
    {
        if (isset($this->services[$offset])) {
            unset($this->services[$offset]);
        }

        if (isset($this->resolved[$offset])) {
            unset($this->resolved[$offset]);
        }
    }
}