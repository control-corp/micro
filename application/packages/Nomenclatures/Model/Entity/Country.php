<?php

namespace Nomenclatures\Model\Entity;

use Micro\Model\EntityAbstract;

class Country extends EntityAbstract
{
    protected $id;
    protected $continentId;
    protected $name;
    protected $ISO3166Code;
    protected $population = 0;
    protected $countBrands = 0;
    protected $color;
    protected $active = 1;

    public function setPopulation($value)
    {
        if (empty($value)) {
            $value = 0;
        }

        $this->population = $value;

        return $this;
    }

    public function setCountBrands($value)
    {
        if (empty($value)) {
            $value = 0;
        }

        $this->countBrands = $value;

        return $this;
    }

    public function setActive($value)
    {
        if (empty($value)) {
            $value = 0;
        }

        $this->active = (int) $value ? 1 : 0;

        return $this;
    }
}