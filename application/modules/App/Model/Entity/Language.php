<?php

namespace App\Model\Entity;

use Micro\Translator\Language\LanguageInterface;
use Micro\Model\EntityAbstract;

class Language extends EntityAbstract implements LanguageInterface
{
    protected $id;
    protected $code;

    public function __construct($id = \null, $code = \null)
    {
        $this->id = $id;
        $this->code = $code;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getCode()
    {
        return $this->code;
    }
}