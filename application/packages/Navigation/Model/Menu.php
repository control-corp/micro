<?php

namespace Navigation\Model;

use Micro\Model\DatabaseAbstract;
use Micro\Cache;

class Menu extends DatabaseAbstract
{
    protected $table = Table\Menu::class;
    protected $entity = Entity\Menu::class;

    public function removeCache()
    {
        $cache = app('cache');

        if ($cache instanceof Cache\Core) {
            $cache->clean(Cache\Cache::CLEANING_MODE_MATCHING_TAG, array($this->getCacheId()));
            $cache->clean(Cache\Cache::CLEANING_MODE_MATCHING_TAG, array('Navigation_Model_Items'));
        }
    }
}