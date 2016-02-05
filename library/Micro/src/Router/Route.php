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
     * @var array
     */
    protected $paramsOptional = [];

    /**
     * @var array
     */
    protected $paramsRequired = [];

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
        if (preg_match('~^' . $this->compile() . '$~ius', $requestUri, $matches)) {

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

            $this->compiled = $this->pattern;

            $lambdaOptional = function ($match) {

                $regex = '\w+';

                $this->paramsOptional[$match[2]] = isset($this->defaults[$match[2]]) ? $this->defaults[$match[2]] : \null;

                if (isset($this->conditions[$match[2]])) {
                    $regex = $this->conditions[$match[2]];
                }

                return '(' . $match[1] . '(?P<' . $match[2] . '>' . $regex . ')' . $match[3] . ')?';

            };

            $this->compiled = preg_replace_callback('~\[([^\]]*){([^}]+)}([^\]]*)\]~ius', $lambdaOptional->bindTo($this), $this->compiled);

            $lambdaRequired = function ($match) {

                $regex = '\w+';

                $this->paramsRequired[$match[1]] = isset($this->defaults[$match[1]]) ? $this->defaults[$match[1]] : \null;

                if (isset($this->conditions[$match[1]])) {
                    $regex = $this->conditions[$match[1]];
                }

                return '(?P<' . $match[1] . '>' . $regex . ')';

            };

            $this->compiled = preg_replace_callback('~{([^}]+)}~ius', $lambdaRequired->bindTo($this), $this->compiled);

            $this->params = $this->paramsRequired + $this->paramsOptional;
        }

        return $this->compiled;
    }

    /**
     * @param string $compiled
     * @return \Micro\Router\Route
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

        $url = $this->pattern;

        foreach ($data as $key => $value) {
            $count = 0;
            $url = preg_replace('#\{' . $key . '(:[^}]+)?\}#', $value, $url, -1, $count);
            if ($count) {
                unset($data[$key]);
            }
        }

        $url = str_replace(']', '', $url);
        $segs = array_reverse(explode('[', $url));

        foreach ($segs as $n => $seg) {
            if (strpos($seg, '{') !== false) {
                if (isset($segs[$n - 1])) {
                    throw new \InvalidArgumentException(sprintf(
                        'Optional segments with unsubstituted parameters cannot '
                        . 'contain segments with substituted parameters "%s"'
                    ), $this->pattern);
                }
                unset($segs[$n]);
            }
        }

        $url = implode('', array_reverse($segs));

        if (empty($url)) { // check something wrong
            throw new \InvalidArgumentException(sprintf('Too few arguments? "%s"!', $this->pattern));
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
     * @return \Micro\Router\Route
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
     * @return \Micro\Router\Route
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
     * @return \Micro\Router\Route
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
     * @return \Micro\Router\Route
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