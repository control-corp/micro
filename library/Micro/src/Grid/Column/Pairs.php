<?php

namespace Micro\Grid\Column;

use Micro\Grid\Column;

class Pairs extends Column
{
    protected $pairs;
    protected $callable;
    protected $params = [];

    public function setCallable($value)
    {
        $this->callable = $value;
    }

    public function setParams(array $value)
    {
        $this->params = $value;
    }

    public function setFalse($false)
    {
        $this->false = $false;
    }

    public function render()
    {
        $value = parent::render();

        $pairs = [];

        if ($this->callable !== \null && is_callable($this->callable)) {
            if ($this->pairs === \null) {
                $this->pairs = call_user_func_array($this->callable, $this->params);
            }
        } else {
            $this->pairs = [];
        }

        return isset($this->pairs[$value]) ? $this->pairs[$value] : '';
    }
}