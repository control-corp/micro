<?php

namespace Nomenclatures\Model;

use Micro\Model\DatabaseAbstract;

class Types extends DatabaseAbstract
{
    protected $table = Table\Types::class;

    protected $entity = Entity\Type::class;
}