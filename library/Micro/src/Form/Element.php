<?php

namespace Micro\Form;

use Micro\Form\Form;
use Micro\Validate;
use Micro\Validate\ValidateInterface;

abstract class Element
{
    protected $name;
    protected $value;
    protected $label;
    protected $labelClass;
    protected $class;
    protected $belongsTo;
    protected $required = \false;
    protected $validators = [];
    protected $errors = [];
    protected $showErrors = \true;
    protected $attributes = [];
    protected $isArray = \false;
    protected $translate = \false;
    protected $form;

    abstract public function render();

    /**
     * @param string $name
     * @param array $options
     */
    public function __construct($name, array $options = [])
    {
        $this->name = $name;

        if (!empty($options)) {
            $this->setOptions($options);
        }
    }

    public function setOptions(array $options)
    {
        foreach ($options as $k => $v) {
            $method = 'set' . ucfirst($k);
            if (method_exists($this, $method)) {
                $this->$method($v);
            }
        }

        return $this;
    }

    public function bindTo(Form $form)
    {
        $this->form = $form;

        return $this;
    }

    public function setIsArray($flag = \true)
    {
        $this->isArray = (bool) $flag;

        return $this;
    }

    public function getIsArray()
    {
        return $this->isArray;
    }

    public function setClass($value)
    {
        $this->attributes['class'] = $value;

        return $this;
    }

    public function getClass()
    {
        return isset($this->attributes['class']) ? $this->attributes['class'] : \null;
    }

    public function setShowErrors($value = \true)
    {
        $this->showErrors = $value;

        return $this;
    }

    public function getShowErrors()
    {
        return $this->showErrors;
    }

    public function setName($value)
    {
        $this->name = $value;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getFullyName()
    {
        return $this->belongsTo !== \null
                ? $this->belongsTo . '[' . $this->name . ']'
                : $this->name;
    }

    public function setTranslate($value)
    {
        $this->translate = (bool) $value;

        return $this;
    }

    public function getTranslate()
    {
        return $this->translate;
    }

    public function setBelongsTo($value)
    {
        $this->belongsTo = $value;

        return $this;
    }

    public function getBelongsTo()
    {
        return $this->belongsTo;
    }

    public function setLabel($value)
    {
        $this->label = $value;

        return $this;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function setLabelClass($value)
    {
        $this->labelClass = $value;

        return $this;
    }

    public function getLabelClass()
    {
        return $this->labelClass;
    }

    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setRequired($flag = \true)
    {
        $this->required = (bool) $flag;

        return $this;
    }

    public function getRequired()
    {
        return $this->isRequired();
    }

    public function isRequired()
    {
        return $this->required;
    }

    public function setAttributes(array $attributes)
    {
        foreach ($attributes as $k => $v) {
            $this->attributes[$k] = $v;
        }

        return $this;
    }

    public function getAttribute($key = \null)
    {
        if ($key === \null) {
            return $this->attributes;
        }

        return isset($this->attributes[$key]) ? $this->attributes[$key] : \null;
    }

    public function clearAttributes()
    {
        $this->attributes = [];

        return $this;
    }

    public function setValidators(array $validators)
    {
        $this->clearValidators();

        $this->addValidators($validators);

        return $this;
    }

    public function clearValidators()
    {
        $this->validators = [];

        return $this;
    }

    public function addValidators(array $validators)
    {
        foreach ($validators as $validatorInfo) {

            if (is_string($validatorInfo) || $validatorInfo instanceof ValidateInterface) {

                $this->addValidator($validatorInfo);

            } elseif (is_array($validatorInfo)) {

                if (isset($validatorInfo['validator'])) {

                    $validator = $validatorInfo['validator'];

                    $options = [];

                    if (isset($validatorInfo['options'])) {

                        $options = $validatorInfo['options'];
                    }

                    $this->addValidator($validator, $options);

                } else {

                    throw new \Exception('Invalid validator config passed to addValidators()');
                }
            }
        }

        return $this;
    }

    public function addValidator($validator, array $options = [])
    {
        if ($validator instanceof ValidateInterface) {
            $name = get_class($validator);
        } else if (is_string($validator)) {
            $name = $validator;
            $validator = [
                'validator' => $validator,
                'options'   => $options,
            ];
        } else {
            throw new \Exception('Invalid validator provided to addValidator; must be string or Micro\Validate\ValidateInterface');
        }

        $this->validators[$name] = $validator;

        return $this;
    }

    public function prependValidator(ValidateInterface $validator)
    {
        $validators = $this->getValidators();

        array_unshift($validators, $validator);

        $this->setValidators($validators);

        return $this;
    }

    public function getValidators()
    {
        $validators = [];

        foreach ($this->validators as $key => $value) {
            if ($value instanceof ValidateInterface) {
                $validators[$key] = $value;
                continue;
            }
            $validator = $this->loadValidator($value);
            $validators[get_class($validator)] = $validator;
        }

        return $validators;
    }

    protected function loadValidator(array $validator)
    {
        if (!isset($validator['validator'])) {
            throw new \Exception(sprintf('[%s] Validator key does not exists', __METHOD__));
        }

        $origName = $validator['validator'];

        if (class_exists($origName)) {
            $name = $origName;
        } else {
            $name = 'Micro\Validate\\' . ucfirst($origName);
            if (!class_exists($name)) {
                throw new \Exception('Class [' . $name . '] does not exists');
            }
        }

        $instance = new $name((isset($validator['options']) ? $validator['options'] : []));

        if ($origName != $name) {

            $validatorNames     = array_keys($this->validators);
            $order              = array_flip($validatorNames);
            $order[$name]       = $order[$origName];
            $validatorsExchange = [];

            unset($order[$origName]);

            asort($order);

            foreach ($order as $key => $index) {
                if ($key == $name) {
                    $validatorsExchange[$key] = $instance;
                    continue;
                }

                $validatorsExchange[$key] = $this->validators[$key];
            }

            $this->validators = $validatorsExchange;

        } else {
            $this->validators[$name] = $instance;
        }

        return $instance;
    }

    public function isValid($value, array $context = \null)
    {
        $this->setValue($value);

        $value = $this->getValue();

        if ((('' === $value) || (\null === $value)) && $this->required === \false) {
            return \true;
        }

        if ($this->required === \true && !isset($this->validators[Validate\NotEmpty::class])) {
            $this->prependValidator(new Validate\NotEmpty());
        }

        foreach ($this->getValidators() as $key => $validator) {
            if (!$validator->isValid($value, $context)) {
                foreach ($validator->getMessages() as $message) {
                    $this->addError($message);
                }
            }
        }

        if ($this->hasErrors()) {
            return \false;
        }

        return \true;
    }

    public function addError($message)
    {
        $this->errors[] = $message;

        if ($this->form instanceof Form) {
            $this->form->markAsError();
        }

        return $this;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function hasErrors()
    {
        return !empty($this->errors);
    }

    public function __toString()
    {
        try {
            return $this->render();
        } catch (\Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return '';
        }
    }

    protected function htmlAttributes()
    {
        $xhtml = '';

        foreach ($this->attributes as $key => $val) {

            if (is_array($val)) {
                $val = implode(' ', $val);
            }

            if (is_numeric($key)) {
                $xhtml .= " $val";
                continue;
            }

            if (is_array($val)) {
                $val = implode(' ', $val);
            }

            if (strpos($val, '"') !== \false) {
                $xhtml .= " $key='$val'";
            } else {
                $xhtml .= " $key=\"$val\"";
            }

        }

        return $xhtml;
    }

    public function renderLabel()
    {
        if ($this->label) {
            return '<label class="' . $this->name . '-element-label' . ($this->labelClass ? ' ' . $this->labelClass : ' element-label') . ($this->required ? ' required' : '') . '">' . $this->translateData($this->label) . ($this->required ? ' <span class="asterisk">*</span>' : '') . '</label>';
        }

        return '';
    }

    public function renderErrors()
    {
        $tmp = '';

        foreach ($this->errors as $error) {
            $tmp .= '<span class="' . $this->name . '-element-error element-error">' . $this->translateData($error) . '</span>';
        }

        return $tmp;
    }

    public function translateData($data)
    {
        if ($this->translate === \false) {
            return $data;
        }

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->translateData($value);
            }
        } else {
            $data = translate($data);
        }

        return $data;
    }
}