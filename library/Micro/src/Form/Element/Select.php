<?php

namespace Micro\Form\Element;

use Micro\Form\Element;
use Micro\Application\Utils;

class Select extends Element
{
    protected $multiOptions = [];
    protected $emptyOption;
    protected $emptyOptionValue = '';

    public function isValid($value, array $context = \null)
    {
        $valid = parent::isValid($value, $context);

        if ($this->isArray && empty($value)) {
            $this->setValue([]);
        }

        return $valid;
    }

    public function render()
    {
        $tmp = '';

        $name = $this->getFullyName();

        if ($this->isArray) {
            $tmp .= '<input type="hidden" name="' . $name . '" value="" />';
            $name .= '[]';
        }

        $tmp .= '<select' . ($this->isArray ? ' multiple="multiple"' : '') . ' name="' . $name . '"' . $this->htmlAttributes() . '>' . Utils::buildOptions($this->translateData($this->multiOptions), $this->value, $this->emptyOption, $this->emptyOptionValue) . '</select>';

        if ($this->showErrors === \true) {
            $tmp .= $this->renderErrors();
        }

        return $tmp;
    }

    public function setEmptyOption($value)
    {
        $this->emptyOption = $value;

        return $this;
    }

    public function setEmptyOptionvalue($value)
    {
        $this->emptyOptionValue = $value;

        return $this;
    }

    public function setMultiOptions(array $options)
    {
        $this->clearMultiOptions();

        $this->addMultiOptions($options);

        return $this;
    }

    public function getMultiOptions()
    {
        return $this->multiOptions;
    }

    public function clearMultiOptions()
    {
        $this->multiOptions = [];

        return $this;
    }

    public function addMultiOptions(array $options)
    {
        foreach ($options as $k => $v) {
            $this->addMultiOption($k, $v);
        }

        return $this;
    }

    public function addMultiOption($k, $v)
    {
        $this->multiOptions[$k] = $v;

        return $this;
    }
}