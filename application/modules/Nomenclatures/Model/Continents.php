<?php

namespace Nomenclatures\Model;

use Micro\Model\DatabaseAbstract;

class Continents extends DatabaseAbstract
{
    protected $table = Table\Continents::class;
    protected $entity = Entity\Continent::class;
}