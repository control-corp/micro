<?php

namespace UserManagement\Model;

use Micro\Application\Security;
use Micro\Auth\Auth;
use Micro\Model\DatabaseAbstract;

class Users extends DatabaseAbstract
{
    protected $table = Table\Users::class;

    protected $entity = Entity\User::class;

    public function login($username, $password)
    {
        $this->addWhere('username', $username);

        $user = $this->getItem();
        $user->loadRole();

        if ($user !== \null && Security::verity($password, $user['password'])) {
            Auth::getInstance()->setIdentity($user);
            return \true;
        }

        return \false;
    }
}