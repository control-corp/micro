<?php

namespace Micro\Router;

class Route
{
    /**
     * @var string
     */
    protected $pattern;

    /**
     * @var array
     */
    protected $conditions = [];

    /**
     * @var string
     */
    protected $compiled;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var \Closure|string
     */
    protected $handler;

    /**
     * @var array
     */
    protected $defaults = [];

    /**
     * @var array
     */
    protected $params = [];

    /**
     * @param string $name
     * @param string $pattern
     * @param \Closure|string $handler
     */
    public function __construct($name, $pattern, $handler)
    {
        $this->setPattern($pattern);

        $this->name = $name;

        if ($handler instanceof \Closure) {
            $handler = $handler->bindTo($this);
        }

        $this->handler = $handler;
    }

    /**
     * @param string $pattern
     * @return boolean
     */
    public static function isStatic($pattern)
    {
        return !preg_match('~[{}\[\]]~', $pattern);
    }

    /**
     * @param string $requestUri
     * @return boolean
     */
    public function match($requestUri)
    {
        if (preg_match('~^' . $this->compile() . '$~', $requestUri, $matches)) {

            foreach ($this->params as $k => $v) {
                if (isset($matches[$k])) {
                    $this->params[$k] = $matches[$k];
                }
            }

            return \true;
        }

        return \false;
    }

    /**
     * Compile route pattern to regex
     * @return string
     */
    public function compile()
    {
        if ($this->compiled === \null) {

            $compiled = '';

            if (preg_match_all('~(\[)?([^{}\[\]]*){([^}]+)}([^{}\[\]]*)(\])?~', $this->pattern, $matches)) {

                foreach ($matches[3] as $k => $param) {

                    $regex = '[^/]+';

                    if (isset($this->conditions[$param])) {
                        $regex = $this->conditions[$param];
                    }

                    if ($matches[1][$k] === '[') {
                        $compiled .= '(' . preg_quote($matches[2][$k]) . '(?P<' . $param . '>' . $regex . ')' . preg_quote($matches[4][$k]) . ')?';
                    } else {
                        $compiled .= preg_quote($matches[2][$k]) . '(?P<' . $param . '>' . $regex . ')' . preg_quote($matches[4][$k]);
                    }

                    $this->params[$param] = isset($this->defaults[$param]) ? $this->defaults[$param] : \null;
                }
            }

            $this->compiled = $compiled;
        }

        return $this->compiled;
    }

    /**
     * @param string $compiled
     * @return Route
     */
    public function setCompiled($compiled)
    {
        $this->compiled = $compiled;

        return $this;
    }

    /**
     * @param array $data
     * @param bool $reset
     * @throws \Exception
     * @return string
     */
    public function assemble(array &$data = [], $reset = \false)
    {
        $data += ($reset ? [] : $this->params) + $this->defaults;

        $url = '';

        $error = false;

        if (preg_match_all('~(\[)?([^{}\[\]]*){([^}]+)}([^{}\[\]]*)(\])?~', $this->pattern, $matches)) {
            foreach ($matches[3] as $k => $v) {
                if (array_key_exists($v, $data)) {
                    $matches[3][$k] = $data[$v];
                    unset($data[$v]);
                } else {
                    if ($matches[1][$k] === '[') { // optional parameter
                        unset($matches[1][$k]);
                        unset($matches[2][$k]);
                        unset($matches[3][$k]);
                        unset($matches[4][$k]);
                        unset($matches[5][$k]);
                    } else {
                        $error = true;
                        $matches[3][$k] = '{' . $v . '}'; // required parameter
                    }
                }
                if (!empty($matches[2][$k])) $url .= $matches[2][$k];
                if (!empty($matches[3][$k])) $url .= $matches[3][$k];
                if (!empty($matches[4][$k])) $url .= $matches[4][$k];
            }
        }

        if ($error) { // check something wrong
            throw new \InvalidArgumentException(sprintf('Too few arguments? "%s"!', $url), 500);
        }

        return $url;
    }

    /**
     * @return \Closure|string
     */
    public function getHandler($invoke = \true)
    {
        if ($invoke === \true && $this->handler instanceof \Closure) {
            return $this->handler->__invoke();
        }

        return $this->handler;
    }

    public function addCondition($key, $value)
    {
        $this->conditions[$key] = $value;

        return $this;
    }

    /**
     * @param array $conditions
     * @return Route
     */
    public function setConditions(array $conditions)
    {
        $this->conditions = $conditions;

        return $this;
    }

    /**
     * @return array
     */
    public function getConditions()
    {
        return $this->conditions;
    }

    public function addDefault($key, $value)
    {
        $this->defaults[$key] = $value;

        return $this;
    }

    /**
     * @param array $defaults
     * @return Route
     */
    public function setDefaults(array $defaults)
    {
        $this->defaults = $defaults;

        return $this;
    }

    /**
     * @return array
     */
    public function getDefaults()
    {
        return $this->defaults;
    }

    /**
     * @param string $pattern
     * @return Route
     */
    public function setPattern($pattern)
    {
        $this->pattern = $pattern;

        return $this;
    }

    /**
     * @return string
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * @return array
     */
    public function getParams($withDefaults = \true)
    {
        if ($withDefaults === \true) {
            return $this->params + $this->defaults;
        }

        return $this->params;
    }

    /**
     * @param array $params
     * @return Route
     */
    public function setParams(array $params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}