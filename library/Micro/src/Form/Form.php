<?php

namespace Micro\Form;

class Form
{
    /**
     * @var array
     */
    protected $elements = [];

    /**
     * @var boolean
     */
    protected $errorsExist = \false;

    /**
     * Form constructor
     * @param array|string $options
     * @throws \Exception
     */
    public function __construct($options)
    {
        if (is_string($options) && file_exists($options)) {
            $options = include $options;
            $options = is_array($options) ? $options : [];
        }

        if (!is_array($options)) {
            throw new \Exception('Invalid data in ' . __METHOD__ . ' (' . (is_string($options) ? $options : json_encode($options)) . ')');
        }

        $this->setOptions($options);
    }

    /**
     * @param array $options
     * @return \Micro\Form\Form
     */
    public function setOptions(array $options)
    {
        if (isset($options['elements']) && is_array($options['elements'])) {
            $this->setElements($options['elements']);
        }

        return $this;
    }

    /**
     * @param array $elements
     * @return \Micro\Form\Form
     */
    public function setElements(array $elements)
    {
        foreach ($elements as $name => $config) {
            $this->addElement($name, $config);
        }

        return $this;
    }

    /**
     * @param string $name
     * @param array $config
     * @throws CoreException
     */
    public function addElement($name, $config = \null)
    {
        $instance = \null;

        if ($name instanceof Element) {
            $instance = $name;
        } else if (is_string($name) && is_array($config) && isset($config['type'])) {
            $instance = $this->createElement($name, $config['type'], (isset($config['options']) ? $config['options'] : []));
        } else if (is_string($name) && is_string($config)) {
            $instance = $this->createElement($name, $config, []);
        }

        if (!$instance instanceof Element) {
            throw new \Exception('The element is not instance of Micro\\Form\\Element');
        }

        $instance->bindTo($this);

        $this->elements[$instance->getName()] = $instance;
    }

    /**
     * @param string $name
     * @param string $element
     * @param array $options
     * @throws \Exception
     * @return \Micro\Form\Element
     */
    protected function createElement($name, $element, array $options)
    {
        if (class_exists($element, \true)) {
            $instance = $element;
        } else {
            $instance = 'Micro\Form\Element\\' . ucfirst($element);
            if (!class_exists($instance, \true)) {
                throw new \Exception('[' . __METHOD__ . '] Element class [' . $instance . '] does not exists');
            }
        }

        return new $instance($name, $options);
    }

    /**
     * @param $name
     * @return Form\Element|null
     */
    public function getElement($name)
    {
        return isset($this->elements[$name]) ? $this->elements[$name] : \null;
    }

    /**
     * @param $name
     * @return \Micro\Form\Form
     */
    public function removeElement($name)
    {
        if (is_array($name)) {
            foreach ($name as $singleName) {
                $this->removeElement($singleName);
            }
            return $this;
        }

        if (isset($this->elements[$name])) {
            unset($this->elements[$name]);
        }

        return $this;
    }

    /**
     * @param array $data
     * @return boolean
     */
    public function isValid(array $data)
    {
        $valid = \true;

        foreach ($this->elements as $key => $element) {
            if (isset($data[$key])) {
                $valid = $element->isValid($data[$key], $data) && $valid;
            } else {
                $valid = $element->isValid(\null, $data) && $valid;
            }
        }

        $this->errorsExist = !$valid;

        return $valid;
    }

    /**
     * @param string $name
     * @return array
     */
    public function getErrors($name = \null)
    {
        if (\null !== $name) {
            if (isset($this->elements[$name])) {
                return $this->getElement($name)->getErrors();
            }
            return [];
        }

        $errors = [];

        foreach ($this->elements as $key => $element) {
            $errors[$key] = $element->getErrors();
        }

        return $errors;
    }

    /**
     * @return boolean
     */
    public function hasErrors()
    {
        return $this->errorsExist;
    }

    /**
     * @param array $values
     */
    public function populate(array $values)
    {
        foreach ($this->elements as $name => $element) {
            if (array_key_exists($name, $values)) {
                $this->elements[$name]->setValue($values[$name]);
            }
        }
    }

    /**
     * @return array
     */
    public function getValues()
    {
        $values = [];

        foreach ($this->elements as $key => $element) {
            $values[$key] = $element->getValue();
        }

        return $values;
    }

    /**
     * @param string $key
     * @return \Micro\Form\Element|null
     */
    public function __get($key)
    {
        if (isset($this->elements[$key])) {
            return $this->elements[$key];
        }

        return \null;
    }

    /**
     * @return \Micro\Form\Form
     */
    public function markAsError()
    {
        $this->errorsExist = \true;

        return $this;
    }
}