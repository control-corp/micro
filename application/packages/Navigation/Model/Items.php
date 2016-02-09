<?php

namespace Navigation\Model;

use Micro\Model\DatabaseAbstract;

class Items extends DatabaseAbstract
{
    protected $table = Table\Items::class;
    protected $entity = Entity\Item::class;
}