<?php

namespace Micro\Model;

use Micro\Database\Table\TableAbstract;
use Micro\Database\Expr;
use Micro\Database\Select;
use Micro\Paginator\Adapter\AdapterInterface;
use Micro\Database\Table\Row\RowAbstract;
use Micro\Database\Table\Rowset\RowsetAbstract;
use Micro\Cache;

abstract class DatabaseAbstract implements AdapterInterface, ModelInterface
{
    /**
     * @var TableAbstract
     */
    protected $table;

    /**
     * @var EntityInterface
     */
    protected $entity;

    /**
     * @var Select
     */
    protected $select;

    protected $selectIsDirty = \true;

    protected $where = [];

    protected $order = [];

    protected $join  = [];

    protected static $transactionLevel = 0;

    public function __construct()
    {
        if ($this->table === \null || !class_exists($this->table, \true)) {
            throw new \Exception(get_class($this) . ' Table is not set or not exists');
        }

        if ($this->entity === \null || !class_exists($this->entity, \true)) {
            throw new \Exception(get_class($this) . ' Entity is not set or not exists');
        }

        $this->table = new $this->table;

        if (!$this->table instanceof TableAbstract) {
            throw new \Exception(get_class($this) . ' Table is not instanceof ' . TableAbstract::class);
        }
    }

    public function getTable()
    {
        return $this->table;
    }

    /**
     * @return EntityInterface
     */
    public function createEntity()
    {
        $entity = new $this->entity;

        if (!$entity instanceof EntityInterface) {
            throw new \Exception(get_class($this) . ' Entity is not instanceof ' . EntityInterface::class);
        }

        return $entity;
    }

    public function getTableByColumn($column)
    {
        if (in_array($column, $this->table->info(TableAbstract::COLS))) {
            return $this->table;
        }

        foreach ($this->table->getDependentTables() as $dependentTable) {
            $dependentTableInstance = $this->table->getDependentTableInstance($dependentTable);
            if (in_array($column, $dependentTableInstance->info(TableAbstract::COLS))) {
                return $dependentTableInstance;
            }
        }

        return \null;
    }

    public function find()
    {
        $this->resetSelect(\true);

        $pkInfo = $this->table->info(TableAbstract::PRIMARY);

        $ids = func_get_args();

        foreach ($pkInfo as $field) {
            $id = array_shift($ids);
            if ($id === \null) {
                throw new \Exception('Insufficient values for compound key');
            }
            $this->addWhere($field, $id);
        }

        $row = $this->table->fetchRow($this->getJoinSelect());

        return ($row === \null) ? \null : $this->rowToObject($row);
    }

    public function addWhere($field, $value = \null)
    {
        $this->selectIsDirty = \true;

        if ($field instanceof Expr) {
            $this->where[] = $field;
            return $this;
        }

        $table = $this->getTableByColumn($field);

        if (!$table instanceof TableAbstract) {
            return $this;
        }

        $alias = $table->info(TableAbstract::NAME);

        if ($value === \null) {
            $this->where[] = new Expr($field . ' IS NULL');
        } else if (!is_scalar($value) || (strpos($value, '%') === \false)) {
            $this->where["{$alias}.{$field}"] = $value;
        } else {
            $this->where[] = new Expr("lower({$alias}.{$field}) LIKE " . mb_strtolower($table->getAdapter()->quote($value), 'UTF-8'));
        }

        return $this;
    }

    public function addOrder($field, $direction = \null)
    {
        if ($direction === \null) {
            $direction = 'ASC';
        }

        $this->selectIsDirty = \true;

        if ($field instanceof Expr) {
            $this->order[] = $field;
            return $this;
        }

        if (!in_array(strtolower($direction), ['asc', 'desc'])) {
            return $this;
        }

        $table = $this->getTableByColumn($field);

        if (!$table instanceof TableAbstract) {
            return $this;
        }

        $alias = $table->info(TableAbstract::NAME);

        $this->order[] = "{$alias}.{$field} {$direction}";

        return $this;
    }

    public function setOrder(array $order)
    {
        $this->selectIsDirty = \true;

        $this->order = [];

        foreach ($order as $key => $value) {
            if (is_numeric($key)) {
                $this->addOrder($value);
            } else {
                $this->addOrder($key, $value);
            }
        }

        return $this;
    }

    public function resetSelect($all = \false)
    {
        $this->selectIsDirty = \true;

        $all = (bool) $all;

        if ($all) {
            $this->select = \null;
        }

        $this->order = [];
        $this->where = [];
        $this->join  = [];

        return $this;
    }

    public function addJoinCondition($field, $value)
    {
        $this->selectIsDirty = \true;

        $table = $this->getTableByColumn($field);

        if (!$table instanceof TableAbstract) {
            return $this;
        }

        $alias = $table->info(TableAbstract::NAME);

        if (!isset($this->join[$alias])) {
            $this->join[$alias] = [];
        }

        foreach ($this->join[$alias] as $key => $oldValue) {
            if ($oldValue[0] === $field) {
                unset($this->join[$alias][$key]);
                break;
            }
        }

        $this->join[$alias][] = [$field, $value];

        return $this;
    }

    public function applyWhereConditions(Select $select)
    {
        foreach ($this->where as $field => $condition) {

            if ($condition instanceof Expr) {
                $select->where($condition);
                continue;
            }

            if (is_array($condition)) {
                $select->where($field . ' IN (?)', $condition);
            } elseif (is_string($condition && preg_match('/(^%|%$)/i', $condition))) {
                $select->where($field . ' LIKE ?', $condition);
            } else {
                $select->where($field . ' = ?', $condition);
            }
        }
    }

    public function getJoinSelect()
    {
        if (\null === $this->select || $this->selectIsDirty === \true) {
            $this->select = $this->buildSelect();
        }

        return $this->select;
    }

    public function setJoinSelect(Select $select)
    {
        $this->select = $select;

        return $this;
    }

    public function addFilters(array $params)
    {
        foreach ($params as $key => $value) {

            $table = $this->getTableByColumn($key);

            if (!$table) {
                continue;
            }

            $metadata = $table->info('metadata');

            switch ($metadata[$key]['DATA_TYPE']) {
                case 'char':
                case 'varchar':
                case 'text':
                    $this->addWhere($key, "{$value}%");
                    break;
                default:
                    $this->addWhere($key, $value);
                    break;
            }
        }

        return $this;
    }

    public function getItems($offset = \null, $itemCountPerPage = \null)
    {
        $select = $this->getJoinSelect();

        $select->limit($itemCountPerPage, $offset);

        $result = $this->rowsetToObjects(
            $this->table->fetchAll($select)
        );

        return $result;
    }

    public function getItem()
    {
        $this->getJoinSelect()->order(new Expr('NULL'));

        $items = $this->getItems(0, 1);

        if (!empty($items)) {
            return current($items);
        }

        return \null;
    }

    public function buildSelect()
    {
        $this->selectIsDirty = \false;

        $select = $this->table
                       ->select($this->table)
                       ->setIntegrityCheck(\false);

        $tableInfo = $this->table->info();

        $columns = $tableInfo['cols'];

        foreach ($this->table->getDependentTables() as $dependentTable) {

            $dependentTableInstance = $this->table->getDependentTableInstance($dependentTable);

            $dependentTableInfo = $dependentTableInstance->info();

            $columns = array_merge($columns, $dependentTableInfo['cols']);

            $reference = $dependentTableInstance->getReference(get_class($this->table));

            $onCondition = [];

            foreach ($reference['columns'] as $k => $column) {

                $refColumn = $reference['refColumns'][$k];

                $onCondition[] = $this->table->getAdapter()->quoteIdentifier($tableInfo['name']) . '.' . $this->table->getAdapter()->quoteIdentifier($refColumn) . ' = ' . $this->table->getAdapter()->quoteIdentifier($dependentTableInfo['name']) . '.' . $this->table->getAdapter()->quoteIdentifier($column);
            }

            if (empty($onCondition)) {
                throw new \Exception('Cannot join tables', 500);
            }

            $dependentTableAlias = $dependentTableInfo['name'];

            if (isset($this->join[$dependentTableAlias])) {
                foreach ($this->join[$dependentTableAlias] as $condition) {
                    $onCondition[] = $this->table->getAdapter()->quoteIdentifier($dependentTableAlias) . "." . $this->table->getAdapter()->quoteIdentifier($condition[0]) . " = " . $this->table->getAdapter()->quote($condition[1]);
                }
            }

            $joinType = 'joinLeft';

            if (isset($reference['joinType'])) {
                switch ($reference['joinType']) {
                    case 'inner' :
                        $joinType = 'joinInner';
                        break;
                }
            }

            $select->$joinType(
                $dependentTableInfo['name'],
                implode(" AND ", $onCondition),
                array_diff($dependentTableInfo['cols'], $tableInfo['cols'])
            );
        }

        $this->applyWhereConditions($select);

        foreach ($this->order as $condition) {
            $select->order((string) $condition);
        }

        return $select;
    }

    public function count()
    {
        $select = $this->getJoinSelect();

        $select = clone $select;

        $select->reset('columns');
        $select->reset('limitcount');
        $select->reset('limitoffset');
        $select->reset('order');

        $select->columns(new Expr('count(*) as cnt'));

        $result = $this->table->getAdapter()->fetchCol($select);

        if (count($select->getPart('group')) > 0) {
            $count = count($result);
        } else {
            $count = $result[0];
        }

        return $count;
    }

    public function save(EntityInterface $entity)
    {
        $this->trigger('beforesave', compact('entity'));

        $data = $originalData = $entity->toArray();

        try {

            $this->beginTransaction();

            $data = array_merge($data, $this->saveToTable($this->table, $data));

            foreach ($this->table->getDependentTables() as $dependentTable) {

                $dependentTableInstance = $this->table->getDependentTableInstance($dependentTable);

                $reference = $dependentTableInstance->getReference(get_class($this->table));

                foreach ($data as $k => $v) {
                    $refColumnKey = array_search($k, $reference['refColumns']);
                    if ($refColumnKey !== \false) {
                        $data[$reference['columns'][$refColumnKey]] = $v;
                    }
                }

                $data = array_merge($data, $this->saveToTable($dependentTableInstance, $data));
            }


            $entity->setFromArray($data);

            $this->trigger('aftersave', compact('entity'));

            $this->commit();

        } catch (\Exception $e) {
            $this->rollBack();
            $entity->setFromArray($originalData);
            throw $e;
        }

        $this->removeCache();

        return $entity;
    }

    public function saveToTable(TableAbstract $table, array $data)
    {
        /**
         * Force primary keys as array in case we use compound primary key
         */
        $primaryKeys = (array) $table->info(TableAbstract::PRIMARY);

        $primaryValues = [];

        $primaryValuesFilled = \false;

        foreach ($primaryKeys as $primaryKey) {

            if (!array_key_exists($primaryKey, $data)) {
                throw new \Exception(get_class($table) . 'Primary key doesn\'t match object type properties');
            }

            if (!empty($data[$primaryKey])) {
                $primaryValuesFilled = \true;
            }

            $primaryValues[] = $data[$primaryKey];
        }

        $rowSet = \null;

        /**
         * Try to find row from primary table based on primary key(s) value(s)
         */
        if ($primaryValuesFilled) {
            $rowSet = call_user_func_array([$table, 'find'], $primaryValues);
        }

        /**
         * Check if primary key is a sequence and exclude it from data
         * @todo \Micro\Database\Table\TableAbstract seems to set sequence = true even
         *       if primary key is compound containing foreign sequence
         */
        if ($table->info(TableAbstract::SEQUENCE) && (count($primaryKeys) == 1)) {
            $tempArray = array_flip($primaryKeys);
            $data = array_diff_key($data, $tempArray);
        }

        /**
         * Get current row from rowset or fetch new one if we'll insert
         */
        if ($rowSet && $rowSet->count()) {
            $row = $rowSet->current();
        } else {
            $row = $table->createRow();
        }

        try {
            foreach ($data as $column => $value) {
                if ($value instanceof \DateTime) {
                    $data[$column] = $value->format('Y-m-d H:i:s');
                }
            }
            $row->setFromArray($data);
            $row->save();
        } catch (\Exception $e) {
            throw $e;
        }

        /**
         * Merge type data with possible new info from database
         */
        $data = array_merge($data, $row->toArray());

        return $data;
    }

    public function getIdentifier()
    {
        return current($this->getTable()->info('primary'));
    }

    public function delete(EntityInterface $entity)
    {
        $affected = $this->getTable()->delete(['id = ?' => $entity[$this->getIdentifier()]]);

        $this->removeCache();

        return $affected;
    }

    /**
     * @param $ids
     * @param $active
     * @return int
     * @throws \Exception
     */
    public function activate(EntityInterface $entity, $active)
    {
        $cols = $this->getTable()->info('cols');

        if (!in_array('active', $cols)) {
            return 0;
        }

        $affected = $this->getTable()->update(array('active' => ((int) $active ? 1 : 0)), array($this->getIdentifier() . ' = ?' => $entity[$this->getIdentifier()]));

        $this->removeCache();

        return $affected;
    }

    public function rowToObject($row )
    {
        if (!is_array($row) && !$row instanceof RowAbstract) {
            return \null;
        }

        $entity = $this->createEntity();

        $entity->setFromArray(
            is_array($row)
            ? $row
            : $row->toArray()
        );

        $this->trigger('load', compact('entity'));

        return $entity;
    }

    public function rowsetToObjects($rowset)
    {
        $results = [];

        if (!is_array($rowset) && !$rowset instanceof RowsetAbstract) {
            return [];
        }

        if (count($rowset) === 0) {
            return [];
        }

        $primary = $this->table->info('primary');
        $primary = current($primary);

        foreach ($rowset as $row) {
            $results[$row->{$primary}] = $this->rowToObject($row);
        }

        return $results;
    }

    public function fetchPairs(array $where = \null, array $fields = \null, array $order = \null, $cacheIt = \false)
    {
        $this->resetSelect(\true);

        $adapter = $this->getTable()->getAdapter();
        $cache = \null;

        if ($cacheIt !== \false) {
            $cache = app('cache');
        }

        if ($where !== \null) {
            foreach ($where as $k => $v) {
                if ($v instanceof Expr) {
                    $this->addWhere($v);
                } else {
                    $this->addWhere($k, $v);
                }
            }
        }

        if ($order !== \null) {
            foreach ($order as $k => $v) {
                $this->addOrder($k, $v);
            }
        } else {
            $this->addOrder('id', 'DESC');
        }

        if ($fields !== \null) {
            $key = $fields[0];
            $value = $fields[1];
        } else {
            $key   = $this->getIdentifier();
            $value = $this->getIdentifier();
            $table = $this->getTableByColumn('name');
            if ($table) {
                $value = $table->info('name') . '.name';
            }
        }

        $select = $this->getJoinSelect();

        $select->reset('columns')->columns([$key, $value]);

        $cacheId = $this->getCacheId() . '_' . md5($select->__toString());

        if ($cache === \null || ($pairs = $cache->load($cacheId)) === \false) {

            $pairs = $adapter->fetchPairs($select);

            if ($cache instanceof Cache\Core) {
                $cache->save($pairs, $cacheId, array($this->getCacheId()));
            }
        }

        return $pairs;
    }

    public function fetchCachedPairs(array $where = \null, array $fields = \null, array $order = \null)
    {
        return $this->fetchPairs($where, $fields, $order, \true);
    }

    public function beginTransaction()
    {
        if (self::$transactionLevel === 0) {
            $db = $this->table->getAdapter();
            if ($db) {
                $db->beginTransaction();
            }
        }

        self::$transactionLevel++;
    }

    public function commit()
    {
        if (self::$transactionLevel === 1) {
            $db = $this->table->getAdapter();
            if ($db) {
                $db->commit();
            }
        }

        self::$transactionLevel--;
    }

    public function rollback()
    {
        if (self::$transactionLevel === 1) {
            $db = $this->table->getAdapter();
            if ($db) {
                $db->rollBack();
            }
        }

        self::$transactionLevel--;
    }

    public function removeCache()
    {
        try {
            $cache = app('cache');
            if ($cache instanceof Cache\Core) {
                $cache->clean(Cache\Cache::CLEANING_MODE_MATCHING_TAG, array($this->getCacheId()));
            }
        } catch (\Exception $e) {

        }
    }

    public function getCacheId()
    {
        return preg_replace('~[^a-zA-Z0-9_]~', '_', get_class($this));
    }

    public function trigger($event, array $params = \null)
    {
        $event = str_replace('\\', '.', get_class($this)) . '.' . ucfirst($event);

        app('event')->trigger($event, $params);
    }

    public static function __callstatic($name, $args)
    {
        $called = get_called_class();

        if (($pos = strpos($name, 'call')) !== 0) {
            throw new \Exception(__METHOD__, 500);
        }

        return call_user_func_array([new $called, substr($name, 4)], $args);
    }
}