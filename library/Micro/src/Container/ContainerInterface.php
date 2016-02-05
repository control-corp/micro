<?php

namespace Micro\Container;

interface ContainerInterface extends \ArrayAccess
{
    public function get($service);
    public function has($service);
    public function set($service, $callback);
}