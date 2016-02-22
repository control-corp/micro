<?php

namespace UserManagement\Model;

use Micro\Model\DatabaseAbstract;
use Micro\Model\EntityInterface;

class Groups extends DatabaseAbstract
{
    protected $table = Table\Groups::class;

    protected $entity = Entity\Group::class;

    public function delete(EntityInterface $entity)
    {
        if ($entity['disabled']) {
            return 0;
        }

        return parent::delete($entity);
    }
}