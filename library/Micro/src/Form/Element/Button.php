<?php

namespace Micro\Form\Element;

use Micro\Form\Element;

class Button extends Element
{
    public function render()
    {
        $tmp = '';

        $tmp .= '<button name="' . $this->getFullyName() . '"' . $this->htmlAttributes() . '>' . escape($this->value) . '</button>';

        if ($this->showErrors === \true) {
            $tmp .= $this->renderErrors();
        }

        return $tmp;
    }
}