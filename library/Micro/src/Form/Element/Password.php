<?php

namespace Micro\Form\Element;

use Micro\Form\Element;

class Password extends Element
{
    public function render()
    {
        $tmp = '';

        $tmp .= '<input type="password" name="' . $this->getFullyName() . '"' . $this->htmlAttributes() . ' />';

        if ($this->showErrors === \true) {
            $tmp .= $this->renderErrors();
        }

        return $tmp;
    }
}