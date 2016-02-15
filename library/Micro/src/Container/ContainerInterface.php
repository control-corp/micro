<?php

namespace Micro\Container;

interface ContainerInterface extends \ArrayAccess
{
    public function get($id);

    public function has($id);

    public function set($id, $service, $override = \true);

    public function setBindings($binder, array $bindings);

    public function configure(array $config);
}