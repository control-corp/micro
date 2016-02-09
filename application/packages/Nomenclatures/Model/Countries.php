<?php

namespace Nomenclatures\Model;

use Micro\Model\DatabaseAbstract;

class Countries extends DatabaseAbstract
{
    protected $table = Table\Countries::class;

    protected $entity = Entity\Country::class;
}