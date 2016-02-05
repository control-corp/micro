<?php

namespace Micro\Form\Element;

use Micro\Form\Element;
use Micro\Validate;

class Checkbox extends Element
{
    protected $checkedValue = '1';
    protected $uncheckedValue = '0';

    public function isValid($value, array $context = \null)
    {
        if ($this->isRequired() && !isset($this->validators[Validate\NotIdentical::class])) {
            $this->prependValidator(new Validate\NotIdentical([
                'value' => $this->uncheckedValue,
                'error' => 'Полето е задължително'
            ]));
        }

        return parent::isValid($value, $context);
    }

    public function render()
    {
        $tmp = '';

        $checked = '';

        if ($this->value == $this->checkedValue) {
            $checked = ' checked="checked"';
        }

        $name = $this->getFullyName();

        $tmp .= '<input type="hidden" name="' . $name . '" value="' . escape($this->uncheckedValue) . '" />';
        $tmp .= '<input type="checkbox" name="' . $name . '" value="' . escape($this->checkedValue) . '"' . $checked . $this->htmlAttributes() . ' />';

        if ($this->showErrors === \true) {
            $tmp .= $this->renderErrors();
        }

        return $tmp;
    }

    public function setCheckedValue($value)
    {
        $this->checkedValue = $value;

        return $this;
    }

    public function setUnCheckedValue($value)
    {
        $this->uncheckedValue = $value;

        return $this;
    }
}