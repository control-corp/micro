<?php

namespace Navigation\View;

use Micro\Application\View;
use Micro\Navigation\Page\Page;

class HookToMenu
{
    protected $view;

    public function __construct()
    {
        $this->view = new View();
    }

    public function __invoke($menuId, $itemId, $page, $visible = \true)
    {
        $menuItem = $this->view->navigation($menuId)->findOneBy('alias', $itemId);

        if (is_string($page)) {
            $page = new Page([
                'label'   => $page,
                'active'  => 1,
                'visible' => $visible
            ]);
        }

        if ($menuItem !== \null) {
            $menuItem->addPage($page);
        }
    }
}