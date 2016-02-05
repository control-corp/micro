<?php

namespace Micro\Auth\Storage;

use Micro\Session\SessionNamespace;
use Micro\Application\Utils;

class Session implements StorageInterface
{
    protected $session;
    protected $namespace;

    public function __construct($namespace)
    {
        $this->namespace = $namespace;
        $this->session   = new SessionNamespace('identity');
    }

    public function write($data)
    {
        $this->session->{$this->namespace} = Utils::safeSerialize($data);
    }

    public function read()
    {
        return isset($this->session->{$this->namespace}) ? Utils::safeUnserialize($this->session->{$this->namespace}) : \null;
    }

    public function clear()
    {
        unset($this->session->{$this->namespace});
    }
}