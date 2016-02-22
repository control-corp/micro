<?php

namespace UserManagement;

use Micro\Application\Module as BaseModule;
use Micro\Acl\Acl;
use Micro\Cache;
use Micro\Container\ContainerInterface;

class Module extends BaseModule
{
    public function boot(ContainerInterface $container)
    {
        $container['event']->attach('application.start', array($this, 'onApplicationStart'));
    }

    public function getConfig()
    {
        return include __DIR__ . '/Resources/configs/module.php';
    }

    public function onApplicationStart()
    {
        /**
         * Acl
         */
        Acl::setResolver(function () {

            $cache = app('cache');
            $db = app('db');

            if (!$db) {
                return [];
            }

            if ($cache === \null || ($data = $cache->load('Acl')) === \false) {

                $groups = app('db')->fetchAll('
                    SELECT a.alias, b.alias as parentAlias, a.rights
                    FROM Groups a
                    LEFT JOIN Groups b ON b.id = a.parentId
                ');

                $data = [];

                foreach ($groups as $group) {
                    $data[$group['alias']] = [
                        'group'     => $group['alias'],
                        'parent'    => $group['parentAlias'],
                        'resources' => []
                    ];
                    $rights = $group['rights'] ? json_decode($group['rights'], \true) : [];
                    $rights = is_array($rights) ? $rights : [];
                    $data[$group['alias']]['resources'] = $rights;
                }

                if ($cache instanceof Cache\Core) {
                    $cache->save($data, 'Acl');
                }
            }

            return $data;
        });

        /**
         * Auth
         */
        /* \Micro\Auth\Auth::setResolver(function ($identity) {
            return \UserManagement\Model\Users::callFind((int) $identity);
        }); */
    }
}