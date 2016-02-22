<?php

namespace Mail\Model\Entity;

use Micro\Model\EntityAbstract;

class Template extends EntityAbstract
{
    protected $id;
    protected $name;
    protected $alias;
    protected $active = 1;
    protected $description;

    /**
     * @return the $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param field_type $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return the $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param field_type $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return the $alias
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @param field_type $alias
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
    }

    /**
     * @return the $active
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @param number $active
     */
    public function setActive($active)
    {
        if (empty($active)) {
            $active = 0;
        }

        $this->active = (int) $active ? 1 : 0;
    }

    /**
     * @return the $description
     */
    public function getDescription()
    {
        return $this->description;
    }

     /**
     * @param field_type $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }
}