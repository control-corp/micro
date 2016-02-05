<?php

namespace Micro\Translator\Adapter;

class TranslatorArray extends AdapterAbstract
{
    protected $path;

    public function __construct()
    {
        $this->path = \config('translator.options.path');
    }

    protected function loadTranslations($code)
    {
        $this->translations[$code] = [];

        if ($this->path !== \null) {
            $files = \glob(\rtrim($this->path, '/') . '/' . $code . '/*');
            if (!empty($files)) {
                \asort($files);
                foreach ($files as $file) {
                    $translations = include $file;
                    $translations = \is_array($translations) ? $translations : [];
                    $this->translations[$code] = \array_merge($this->translations[$code], $translations);
                }
            }
        }

        return $this->translations;
    }
}