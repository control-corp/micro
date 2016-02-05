<?php

namespace Micro\Form\Element;

use Micro\Form\Element;
use Micro\Validate;

class Csrf extends Element
{
    protected $csrfValidator;

    public function getCsrfValidator()
    {
        if ($this->csrfValidator === \null) {
            $this->csrfValidator = new Validate\Csrf(['name' => $this->name]);
        }

        return $this->csrfValidator;
    }

    public function isValid($value, array $context = \null)
    {
        $this->setValue($value);

        $validator = $this->getCsrfValidator();

        $validator->isValid($value, $context);

        foreach ($validator->getMessages() as $message) {
            $this->addError($message);
        }

        if ($this->hasErrors()) {
            return \false;
        }

        return \true;
    }

    public function render()
    {
        $tmp = '';

        $name = $this->getFullyName();

        $tmp .= '<input type="hidden" name="' . $name . '" value="' . $this->getCsrfValidator()->getValue() . '" />';

        if ($this->showErrors === \true) {
            $tmp .= $this->renderErrors();
        }

        return $tmp;
    }
}