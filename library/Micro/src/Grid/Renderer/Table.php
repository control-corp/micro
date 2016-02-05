<?php

namespace Micro\Grid\Renderer;

use Micro\Grid\Grid;
use Micro\Grid\Column;
use Micro\Form\Element;
use Micro\Application\View;
use Micro\Router\Router;

class Table implements RendererInterface
{
    protected $grid;
    protected $view;

    /**
     * @var Router
     */
    protected $router;

    public function __construct(Grid $grid)
    {
        $this->grid = $grid;
        $this->router = app('router');
    }

    public function getView()
    {
        if (\null === $this->view) {
            $this->view = new View();
            try {
                $this->view->injectPaths((array) package_path(current_package(), 'Resources/views'));
            } catch (\Exception $e) {
                app()->collectException($e);
            }
        }

        return $this->view;
    }

    public function setView(View $view)
    {
        $this->view = $view;

        return $this;
    }

    public function render()
    {
        $paginator = $this->grid->getPaginator();
        $buttons = $this->grid->getButtons();

        $output = '';
        $buttonsCode = '';

        $request = app('request');
        $requestParams = $request->getParams();
        $requestParams = array_diff_key($requestParams, $request->getPost());

        foreach ($requestParams as $key => $requestParam) {
            if (!is_array($requestParam)) {
                continue;
            }
            unset($requestParams[$key]);
        }

        if (!empty($buttons)) {
            $output .= '<form class="grid-form" method="post" action="' . $this->router->assemble(\null) . '">';
            $buttonsCode = '<div class="grid-buttons">';
            foreach ($buttons as $name => $button) {
                try {
                    $button = new Element\Submit($name, $button);
                    $buttonsCode .= $button->render() . ' ';
                } catch (\Exception $e) {
                    if (env('development')) {
                        $buttonsCode .= $e->getMessage();
                    }
                }
            }
            $buttonsCode .= '</div>';
            if ($this->grid->getButtonsPlacement() & Grid::PLACEMENT_TOP) {
                $output .= $buttonsCode;
            }
        }

        $renderPagination = '';

        if ($this->grid->getPaginatorPlacement() & Grid::PLACEMENT_TOP || $this->grid->getPaginatorPlacement() & Grid::PLACEMENT_BOTTOM) {
            $renderPagination = $this->renderPagination();
        }

        if ($this->grid->getPaginatorPlacement() & Grid::PLACEMENT_TOP) {
            $output .= $renderPagination;
        }

        $output .= '<div class="table-responsive">';
        $output .= '<table class="table table-bordered table-hover' . ($this->grid->getGridClass() ? ' ' . $this->grid->getGridClass() : '') . '">';
        $output .= '<thead>';

        $output .= '<tr class="table-row-head">';

        foreach ($this->grid->getColumns() as $column) {

            if (!$column instanceof Column) {
                continue;
            }

            if ($column->isSortable()) {

                $sortedClass = 'sorting';

                if ($column->isSorted()) {
                    $sortedClass = 'sorting_' . $column->getSorted();
                }

                $routeParams = array_merge($requestParams, ['orderField' => $column->getName(),
                                                            'orderDir'   => ($column->getSorted() == 'asc') ? 'desc' : 'asc']);

                $title = '<div class="' . $sortedClass . '" data-url="' . $this->router->assemble(\null, $routeParams) . '">' . $column->getTitle() . '</div>';
            } else {
                $title = $column->getTitle();
            }

            $output .= '<th' . ($column->getHeadStyle() ? ' style="' . $column->getHeadStyle() . '"' : '') . ' class="table-cell-head' . ($column->getHeadClass() ? ' ' . $column->getHeadClass() : '') . '">';
            $output .= $title;
            $output .= '</th>';
        }

        $output .= '</tr>';
        $output .= '</thead>';

        $output .= '<tbody>';

        foreach ($paginator as $key => $page) {

            $output .= '<tr class="table-row">';

            foreach ($this->grid->getColumns() as $column) {
                if (!$column instanceof Column) {
                    continue;
                }

                $columnRep = '<td' . ($column->getStyle() ? ' style="' . $column->getStyle() . '"' : '') . ' class="table-cell' . ($column->getClass() ? ' ' . $column->getClass() : '') . '">%s</td>';

                try {
                    $output .= sprintf($columnRep, $column->render());
                } catch (\Exception $e) {
                    if (env('development')) {
                        $output .= sprintf($columnRep, $e->getMessage());
                    } else {
                        $output .= sprintf($columnRep, '&nbsp;');
                    }
                }
            }

            $output .= '</tr>';
        }

        $output .= '</tbody>';

        $output .= '</table>';
        $output .= '</div>';


        if ($this->grid->getPaginatorPlacement() & Grid::PLACEMENT_BOTTOM) {
            $output .= $renderPagination;
        }

        if (!empty($buttons)) {
            if (($this->grid->getButtonsPlacement() & Grid::PLACEMENT_BOTTOM)) {
                $output .= $buttonsCode;
            }
            $output .= '</form>';
        }

        return $output;
    }

    public function renderPagination()
    {
        if (($this->grid->getPaginator()->count() <= 1) && !$this->grid->getPaginatorAlways()) {
            return "";
        }

        return pagination($this->grid->getPaginator(), $this->grid->getPaginationViewScript(), \null, $this->getView());
    }
}