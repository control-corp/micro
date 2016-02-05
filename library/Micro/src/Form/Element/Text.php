<?php

namespace Micro\Form\Element;

use Micro\Form\Element;

class Text extends Element
{
    public function render()
    {
        $tmp = '';

        $tmp .= '<input type="text" name="' . $this->getFullyName() . '" value="' . escape($this->value) . '"' . $this->htmlAttributes() . ' />';

        if ($this->showErrors === \true) {
            $tmp .= $this->renderErrors();
        }

        return $tmp;
    }
}