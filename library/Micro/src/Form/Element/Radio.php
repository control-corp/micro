<?php

namespace Micro\Form\Element;

use Micro\Validate;

class Radio extends Select
{
    public function isValid($value, array $context = \null)
    {
        if ($this->isRequired() && !isset($this->validators[Validate\NotIdentical::class])) {
            $this->prependValidator(new Validate\NotIdentical([
                'value' => "",
                'error' => 'Полето е задължително'
            ]));
        }

        return parent::isValid($value, $context);
    }

    public function render()
    {
        $tmp = '';

        $name = $this->getFullyName();

        $tmp .= '<input type="hidden" name="' . $name . '" value="" />';

        foreach ($this->multiOptions as $k => $v) {

            $tmp .= '<div class="radio">';

            $checked = '';

            if ($this->value == $k) {
                $checked = ' checked="checked"';
            }

            $tmp .= '<label>';
            $tmp .= '<input type="radio" name="' . $name . '" value="' . escape($k) . '"' . $checked . $this->htmlAttributes() . ' />';
            $tmp .= '<span class="element-radio-label">' . escape($v) . '</span>';
            $tmp .= '</label>';

            $tmp .=  '</div>';
        }

        if ($this->showErrors === \true) {
            $tmp .= $this->renderErrors();
        }

        return $tmp;
    }
}