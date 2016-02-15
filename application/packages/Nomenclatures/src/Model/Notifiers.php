<?php

namespace Nomenclatures\Model;

use Micro\Model\DatabaseAbstract;

class Notifiers extends DatabaseAbstract
{
    protected $table = Table\Notifiers::class;

    protected $entity = Entity\Notifier::class;
}