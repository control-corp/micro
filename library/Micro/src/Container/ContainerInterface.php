<?php

namespace Micro\Container;

use Interop\Container\ContainerInterface as Interop;

interface ContainerInterface extends Interop, \ArrayAccess
{
    public function set($id, $service, $override = \true);
    public function setBindings($binder, array $bindings);
}