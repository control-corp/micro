<?php

namespace Nomenclatures\Model;

use Micro\Model\DatabaseAbstract;

class Statuses extends DatabaseAbstract
{
    protected $table = Table\Statuses::class;

    protected $entity = Entity\Status::class;
}