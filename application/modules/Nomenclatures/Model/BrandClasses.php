<?php

namespace Nomenclatures\Model;

use Micro\Model\DatabaseAbstract;

class BrandClasses extends DatabaseAbstract
{
    protected $table = Table\BrandClasses::class;

    protected $entity = Entity\BrandClass::class;
}