<?php

namespace Pages\Model;

use Micro\Model\DatabaseAbstract;

class Pages extends DatabaseAbstract
{
    protected $table = Table\Pages::class;

    protected $entity = Entity\Page::class;
}