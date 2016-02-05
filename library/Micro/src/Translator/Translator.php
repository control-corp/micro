<?php

namespace Micro\Translator;

use Micro\Translator\Adapter\AdapterAbstract;

class Translator
{
    /**
     * @var null|false|AdapterAbstract
     */
    protected $adapter;

    public function __construct(AdapterAbstract $adapter = \null)
    {
        if ($adapter !== \null) {
            $this->setAdapter($adapter);
        }
    }

    public function getAdapter()
    {
        if ($this->adapter === \null) {
            $adapter = \config('translator.adapter');
            if ($adapter !== null) {
                if (!\class_exists($adapter)) {
                    $adapter = 'Micro\Translator\Adapter\\' . \ucfirst($adapter);
                    if (!\class_exists($adapter)) {
                        trigger_error('Invalid translator "' . $adapter . '"', E_USER_WARNING);
                        return $this->adapter = false;
                    }
                }
                $this->adapter = new $adapter;
                if (!$this->adapter instanceof AdapterAbstract) {
                    \trigger_error('Translator "' . \get_class($adapter) . '" must be instanceof ' . AdapterAbstract::class, \E_USER_WARNING);
                    return $this->adapter = \false;
                }
            } else {
                $this->adapter = \false;
            }
        }

        return $this->adapter;
    }

    public function setAdapter(AdapterAbstract $adapter)
    {
        $this->adapter = $adapter;

        return $this;
    }

    /**
     * @param $key
     * @param null $code
     * @return mixed
     */
    public function translate($key, $code = \null)
    {
        if (($adapter = $this->getAdapter()) !== \false) {
            return $adapter->translate($key, $code);
        }

        return $key;
    }
}