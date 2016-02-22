<?php

namespace UserManagement\Model\Entity;

use Micro\Model\EntityAbstract;
use Micro\Acl\RoleInterface;
use Micro\Auth\Identity;

class User extends EntityAbstract implements RoleInterface, Identity
{
    protected $id;
    protected $groupId;
    protected $username;
    protected $password;
    protected $role;

    public function getRoleId()
    {
        return $this->loadRole();
    }

    public function loadRole()
    {
        if ($this->role === \null) {
            $this->role = app('db')->fetchOne('SELECT alias FROM Groups WHERE id = ?', array((int) $this->groupId));
        }

        return $this->role;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getEmail()
    {
        return \null;
    }

    public function getGroups()
    {
        return [$this->groupId];
    }

    public function isActive()
    {
        return \true;
    }
}