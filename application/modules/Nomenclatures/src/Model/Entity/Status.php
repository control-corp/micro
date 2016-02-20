<?php

namespace Nomenclatures\Model\Entity;

use Micro\Model\EntityAbstract;

class Status extends EntityAbstract
{
    protected $id;
    protected $name;
    protected $code;
    protected $color;
    protected $active = 1;

    public function setActive($value)
    {
        if (empty($value)) {
            $value = 0;
        }

        $this->active = (int) $value ? 1 : 0;

        return $this;
    }
}