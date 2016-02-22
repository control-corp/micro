<?php

namespace App\Model\Table;

use Micro\Database\Table\TableAbstract;
use Micro\Cache;

class Settings extends TableAbstract
{
    protected $_name = 'Settings';

    public static function removeCache()
    {
        $cache = app('cache');

        if ($cache instanceof Cache\Core) {
            $cache->remove('Settings');
        }
    }

    public static function getKey($key, $default = \null)
    {
        $model = new self();
        $settings = self::getSettings();

        return isset($settings[$key]) ? $settings[$key] : $default;
    }

    public static function getSettings()
    {
        $cache = app('cache');

        if ($cache === \null || ($settings = $cache->load('Settings')) === \false) {
            $settings = static::getDefaultAdapter()->fetchPairs('SELECT `key`, `value` FROM Settings');
            if ($cache instanceof Cache\Core) {
                $cache->save($settings, 'Settings');
            }
        }

        return $settings;
    }
}