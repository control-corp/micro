<?php

namespace Micro\Form\Element;

use Micro\Form\Element;

class Datepicker extends Element
{
    protected $format = 'Y-m-d';

    public function render()
    {
        $tmp = '';

        $name = $this->getFullyName();

        $value = '';

        try {
            if ($this->value) {
                $date  = new \DateTime($this->value);
                $value = $date->format($this->format);
            }
        } catch (\Exception $e) {}

        $tmp .= '<input type="text" name="' . $name . '" value="' . escape($value) . '"' . $this->htmlAttributes() . ' />';

        if ($this->showErrors === \true) {
            $tmp .= $this->renderErrors();
        }

        return $tmp;
    }

    public function setFormat($value)
    {
        $this->format = $value;
    }
}