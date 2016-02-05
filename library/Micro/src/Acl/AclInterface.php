<?php

namespace Micro\Acl;

interface AclInterface
{
    public function isAllowed($role = \null, $resource = \null, $privilege = \null);
}