<?php

namespace Micro\Validate;

class Email extends ValidateAbstract
{
    protected $templates = [
        self::ERROR => 'Невалидна електронна поща'
    ];

    public function isValid($value)
    {
        if (filter_var($value, FILTER_VALIDATE_EMAIL) === \false) {
            $this->messages[] = $this->templates[self::ERROR];
            return \false;
        }

        return \true;
    }
}
