<?php

namespace Navigation\Helper;

use Micro\Database\Expr;

class Tree
{
    protected $menu;

    public function __construct($menu)
    {
        $this->menu = $menu;
    }

    public function loadTree($active = 1)
    {
        $db = app('db');

        if (!$db) {
            return [];
        }

        $select = $db->select()
                     ->from('MenuItems')
                     ->joinInner('Menu', 'Menu.id = MenuItems.menuId', array())
                     ->where('Menu.alias = ?', $this->menu)
                     ->columns(array(new Expr('1 as level')))
                     ->order('MenuItems.order ASC');

        if ($active !== null) {
            $select->where('MenuItems.active = ?', ($active ? 1 : 0));
            $select->where('Menu.active = ?', ($active ? 1 : 0));
        }

        return $db->fetchAll($select);
    }

    public function getTree($active = 1)
    {
        $items = $this->loadTree($active);

        foreach ($items as $index => $element) {
            $items[$index]['children'] = $this->getChildren($element['id'], $items);
        }

        foreach ($items as $index => $element) {
            if ($element['parentId'] > 0) {
                unset($items[$index]);
            }
        }

        return $items;
    }

    protected function getChildren($id, $items)
    {
        $children = array();

        foreach ($items as $key => $item) {
            if ($item['parentId'] == $id) {
                $level = $item['level'];
                while (isset($children[$level])) {
                    $level++;
                }
                $children[$level] = $item;
            }
        }

        foreach ($children as $key => $child) {
            $children[$key]['children'] = $this->getChildren($child['id'], $items);
        }

        ksort($children);

        return $children;
    }

    public function flat(array $tree, $glue = '-',  array $exclude = null)
    {
        static $level = 1;

        $elements = array();

        foreach ($tree as $item) {

            if ($exclude !== null && in_array($item['id'], $exclude)) {
                continue;
            }

            $elements[$item['id']] = str_repeat($glue, $level) . ' ' . $item['name'];

            if (!empty($item['children'])) {
                $level++;
                $elements += $this->flat($item['children'], $glue, $exclude);
                $level--;
            }
        }

        return $elements;
    }
}