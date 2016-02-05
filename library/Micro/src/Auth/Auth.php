<?php

namespace Micro\Auth;

class Auth
{
    /**
     * @var \Micro\Auth\Auth
     */
    protected static $instance;

    /**
     * @var string
     */
    protected $namespace = 'default';

    /**
     * @var \Micro\Auth\Storage\StorageInterface
     */
    protected $storage;

    /**
     * @var callable
     */
    protected static $resolver;

    protected function __construct() {}

    protected function __clone() {}

    public function getStorage()
    {
        if ($this->storage === \null) {
            $this->storage = new Storage\Session($this->getNamespace());
        }

        return $this->storage;
    }

    /**
     * @param \Micro\Auth\Storage\StorageInterface $storage
     */
    public function setStorage(Storage\StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * @return \Micro\Auth\Auth
     */
    public static function getInstance()
    {
        if (static::$instance === \null) {
            static::$instance = new self();
        }

        return static::$instance;
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        $default = $this->namespace;

        if (($namespace = config('auth.namespace')) !== \null) {
            try {
                $route = app('router')->getCurrentRoute();
                if ($route) {
                    foreach ($namespace as $nsk => $ns) {
                        if (in_array($route->getName(), (array) $ns)) {
                            $default = $nsk;
                            break;
                        }
                    }
                }
            } catch (\Exception $e) {

            }
        }

        return $default;
    }

    /**
     * @param string $namespace
     * @return \Micro\Auth\Auth
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getIdentity()
    {
        return $this->getStorage()->read();
    }

    /**
     * @param mixed $data
     * @return \Micro\Auth\Auth
     */
    public function setIdentity($data)
    {
        $this->getStorage()->write($data);

        return $this;
    }

    /**
     * @return \Micro\Auth\Auth
     */
    public function clearIdentity()
    {
        $this->getStorage()->clear();

        return $this;
    }

    /**
     * @param boolean $force
     * @return mixed
     */
    public static function identity($force = \false)
    {
        static $cache = \false;

        if ($force === \false && $cache !== \false) {
            return $cache;
        }

        $identity = static::getInstance()->getIdentity();

        if ($identity instanceof Identity || $identity === \null) {
            return $cache = $identity;
        }

        if (static::$resolver !== \null) {
            if (($identity = call_user_func(static::$resolver, $identity)) instanceof Identity) {
                return $cache = $identity;
            }
        }

        throw new \Exception('Auth resolver must returns instance of Micro\Auth\Identity', 500);
    }

    /**
     * @param callable $resolver
     */
    public static function setResolver($resolver)
    {
        static::$resolver = $resolver;
    }
}