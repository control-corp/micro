<?php

namespace Micro\Translator\Adapter;

use Micro\Translator\Language\LanguageInterface;

abstract class AdapterAbstract implements AdapterInterface
{
    protected $translations = [];

    public function translate($key, $code = \null)
    {
        $container = app();

        if ($code === \null
            && $container->has('language')
            && ($language = $container->get('language')) instanceof LanguageInterface
        ) {
            $code = $language->getCode();
        } else {
            $code = config('language.default');
        }

        if ($code === \null) {
            return $key;
        }

        if (!isset($this->translations[$code])) {
            $this->loadTranslations($code);
        }

        if (isset($this->translations[$code][$key])) {
            return $this->translations[$code][$key];
        }

        return $key;
    }

    abstract protected function loadTranslations($code);
}