<?php

namespace Micro\Validate;

use Micro\Session\SessionNamespace;
use Micro\Application\Utils;

class Csrf extends ValidateAbstract
{
    protected $templates = [
        self::ERROR => 'Формата е невалидна'
    ];

    protected $session;

    protected $name = 'default';

    public function getSession()
    {
        if ($this->session === \null) {
            $this->session = new SessionNamespace($this->name . '_form_csrf');
        }

        return $this->session;
    }

    public function getValue()
    {
        return $this->getSession()->value = md5(Utils::randomSentence(10) . time());
    }

    public function setName($value)
    {
        $this->name = $value;

        return $this;
    }

    public function isValid($value, $context = \null)
    {
        if ($this->getSession()->value !== $value) {
            $this->messages[] = $this->templates[self::ERROR];
            return \false;
        }

        return \true;
    }
}
