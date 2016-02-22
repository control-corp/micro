<?php

namespace Navigation\Model\Entity;

use Micro\Model\EntityAbstract;

class Item extends EntityAbstract
{
    protected $id;
    protected $parentId;
    protected $menuId;
    protected $name;
    protected $alias;
    protected $url;
    protected $route;
    protected $reset = 1;
    protected $qsa = 0;
    protected $qsaData;
    protected $routeData;
    protected $active = 1;
    protected $order;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getParentId()
    {
        return $this->parentId;
    }

    public function setParentId($parentId)
    {
        $this->parentId = $parentId;
    }

    public function getMenuId()
    {
        return $this->menuId;
    }

    public function setMenuId($id)
    {
        $this->menuId = $id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getAlias()
    {
        return $this->alias;
    }

    public function setAlias($alias)
    {
        $this->alias = $alias;
    }

    public function getRoute()
    {
        return $this->route;
    }

    public function setRoute($route)
    {
        $this->route = $route;
    }

    public function getRouteData()
    {
        return $this->routeData;
    }

    public function setRouteData($routeData)
    {
        $this->routeData = $routeData;
    }

    public function getActive()
    {
        return $this->active;
    }

    public function setActive($active)
    {
        $this->active = $active;
    }

    public function getReset()
    {
        return $this->reset;
    }

    public function setQsa($qsa)
    {
        $this->qsa = $qsa;
    }

    public function getQsa()
    {
        return $this->qsa;
    }

    public function getQsaData()
    {
        return $this->qsaData;
    }

    public function setQsaData($qsaData)
    {
        $this->qsaData = $qsaData;
    }


    public function setReset($reset)
    {
        $this->reset = $reset;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setUrl($url)
    {
        $this->url = $url;
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function setOrder($order)
    {
        $this->order = $order;
    }
}