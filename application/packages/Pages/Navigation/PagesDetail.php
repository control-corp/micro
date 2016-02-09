<?php

namespace Pages\Navigation;

use Pages\Model;
use Micro\Model\EntityInterface;

class PagesDetail
{
    public function resolve(array $routeData)
    {
        $model = new Model\Pages();

        return array(
            array (
                'label'   => 'Изберете страница',
                'field'   => 'id',
                'value'   => $model->fetchPairs(\null, ['Pages.id', 'Pages.name'])
            )
        );
    }

    public function decode(array &$routeData, EntityInterface $item)
    {
        if (isset($routeData['id'])) {
            $model = new Model\Pages();
            $item  = $model->find((int) $routeData['id']);
            if ($item) {
                $routeData['alias'] = str_slug($item['name']);
                return;
            }
        }

        $item->setRoute(\null);
        $routeData = [];
    }
}