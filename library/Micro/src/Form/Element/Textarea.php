<?php

namespace Micro\Form\Element;

use Micro\Form\Element;

class Textarea extends Element
{
    public function render()
    {
        $tmp = '';

        $tmp .= '<textarea name="' . $this->getFullyName() . '"' . $this->htmlAttributes() . '>' . escape($this->value) . '</textarea>';

        if ($this->showErrors === \true) {
            $tmp .= $this->renderErrors();
        }

        return $tmp;
    }
}