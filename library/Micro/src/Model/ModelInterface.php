<?php

namespace Micro\Model;

interface ModelInterface extends \Countable
{
    public function getIdentifier();

    public function createEntity();

    public function find();

    public function addWhere($field, $value = \null);

    public function addOrder($field, $direction = \null);

    public function setOrder(array $order);

    public function addJoinCondition($field, $value);

    public function addFilters(array $params);

    public function getItems($offset = \null, $itemCountPerPage = \null);

    public function getItem();

    public function fetchPairs(array $where = \null, array $fields = \null, array $order = \null);

    public function beginTransaction();

    public function commit();

    public function rollback();

    public function trigger($event, array $params = \null);

    public function save(EntityInterface $entity);

    public function delete(EntityInterface $entity);

    public function activate(EntityInterface $entity, $activate);
}