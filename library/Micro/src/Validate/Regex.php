<?php

namespace Micro\Validate;

class Regex extends ValidateAbstract
{
    protected $pattern;

    protected $templates = [
        self::ERROR => 'Невалидна стойност'
    ];

    public function setPattern($pattern)
    {
        $this->pattern = $pattern;
    }

    public function isValid($value)
    {
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            $this->messages[] = $this->templates[self::ERROR];
            return \false;
        }

        if ($this->pattern === \null) {
            return \true;
        }

        $status = @preg_match($this->pattern, $value);

        if ($status === 0 || $status === \false) {
            $this->messages[] = $this->templates[self::ERROR];
            return \false;
        }

        return \true;
    }
}
