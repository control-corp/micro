<?php

namespace Micro\Container;

use Interop\Container\ContainerInterface as Interop;

interface ContainerInterface extends Interop, \ArrayAccess
{
    public function set($id, $callback);
}