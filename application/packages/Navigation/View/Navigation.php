<?php

namespace Navigation\View;

use Micro\Navigation\Navigation as NavigationContainer;
use Micro\Navigation\Page\Page as NavigationPage;
use Navigation\Helper;

class Navigation
{
    protected static $menus = [];

    public function __invoke($menuId)
    {
        if (!isset(static::$menus[$menuId])) {

            $db = app('db');

            if (!$db) {
                return new NavigationContainer();
            }

            $cache = app('cache');

            $languageCode = 'no';

            try {
                $language     = app('language');
                $languageCode = $language ? $language->getCode() : 'no';
            } catch (\Exception $e) {}

            $cacheId = 'Menu_' . $menuId . '_' . $languageCode;
            $cacheId = preg_replace('~[^a-zA-Z0-9_]~', '_', $cacheId);

            if ($cache === \null || ($container = $cache->load($cacheId)) === \false) {
                $tree = new Helper\Tree($menuId);
                $container = new NavigationContainer();
                $container->setPages($this->buildPages($tree->getTree()));
                if ($cache !== \null) {
                    $cache->save($container, $cacheId, ['Navigation_Model_Items']);
                }
            }

            static::$menus[$menuId] = $container;
        }

        return static::$menus[$menuId];
    }

    public static function getMenus()
    {
        return static::$menus;
    }

    public function buildPages(array $tree, $parent = \null)
    {
        $pages = array();

        foreach ($tree as $item) {

            $page = new NavigationPage([
                'id'      => $item['id'],
                'label'   => $item['name'],
                'alias'   => ($item['alias'] ? $item['alias'] : $item['id']),
                'visible' => 1,
                'route'   => $item['route'],
                'reset'   => $item['reset'],
                'qsa'     => $item['qsa'],
                'uri'     => $item['url'],
            ]);

            if ($item['url'] === \null) {

                $routeData = $item['routeData']
                             ? json_decode($item['routeData'], \true)
                             : [];

                if ($item['qsaData']) {
                    $qsaData = [];
                    parse_str($item['qsaData'], $qsaData);
                    $routeData = array_merge($routeData, $qsaData);
                }

                $page->setRouteParams($routeData);
            }

            if (!empty($item['children'])) {
                $page->setPages(
                    $this->buildPages($item['children'], $page)
                );
            }

            $pages[] = $page;
        }

        return $pages;
    }
}