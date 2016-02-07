<?php

namespace Micro\Router;

use Micro\Exception\Exception as CoreException;
use Micro\Application\MiddlewareAwareTrait;
use Micro\Container\ContainerAwareInterface;
use Micro\Container\ContainerAwareTrait;
use Micro\Application\Resolver\ResolverInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class Route implements ContainerAwareInterface
{
    use ContainerAwareTrait, MiddlewareAwareTrait;

    const REGEX = '~
        ([^{}\[\]]+)|
        (\[)?
        ([^{}\[\]]+)?
        {([^}]+)}
        ([^{}\[\]]+)?
        (\])?
    ~x';

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
    protected $middleware = [];

    /**
     * @var boolean
     */
    protected $middlewareAreAdded = \false;

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

            $pattern = $this->pattern;

            if (preg_match_all(static::REGEX, $pattern, $matches)) {

                foreach ($matches[4] as $k => $param) {

                    if (empty($param)) {
                        $compiled .= $matches[1][$k];
                        continue;
                    }

                    $regex = '[^/]+';

                    if (isset($this->conditions[$param])) {
                        $regex = $this->conditions[$param];
                    }

                    if ($matches[2][$k] === '[') {
                        $compiled .= '(' . preg_quote($matches[3][$k]) . '(?P<' . $param . '>' . $regex . ')' . preg_quote($matches[5][$k]) . ')?';
                    } else {
                        $compiled .= preg_quote($matches[3][$k]) . '(?P<' . $param . '>' . $regex . ')' . preg_quote($matches[5][$k]);
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

        if (preg_match_all(static::REGEX, $this->pattern, $matches)) {
            foreach ($matches[4] as $k => $v) {
                if (empty($v)) { // literal
                    $url .= $matches[1][$k];
                    unset($data[$v]);
                    continue;
                }
                if (array_key_exists($v, $data)) { // exists in user params
                    $matches[4][$k] = $data[$v];
                    unset($data[$v]);
                } else {
                    if ($matches[2][$k] === '[') { // optional parameter. remove optionals if not exists
                        unset($matches[2][$k]);
                        unset($matches[3][$k]);
                        unset($matches[4][$k]);
                        unset($matches[5][$k]);
                    } else { // required parameter. mark as error
                        $error = true;
                        $matches[4][$k] = '{' . $v . '}';
                    }
                }
                // build url
                if (!empty($matches[3][$k])) $url .= $matches[3][$k];
                if (!empty($matches[4][$k])) $url .= $matches[4][$k];
                if (!empty($matches[5][$k])) $url .= $matches[5][$k];
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

    /**
     * @param string $key
     * @param string $value
     * @return Route
     */
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

    /**
     * @param string $key
     * @param string $value
     * @return Route
     */
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

    /**
     * Prepend middleware to the middleware collection
     *
     * @param mixed $callable The callback routine
     *
     * @return Route
     */
    public function add($callable)
    {
        if (is_string($callable)) {
            $callable = $this->container->get($callable);
        }

        $this->middleware[] = $callable;

        return $this;
    }

    /**
     * Run route
     *
     * This method traverses the middleware stack, including the route's callable
     * and captures the resultant HTTP response object. It then sends the response
     * back to the Application.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function run(ServerRequestInterface $request, ResponseInterface $response)
    {
        if ($this->middlewareAreAdded === \false) {
            foreach ($this->middleware as $middleware) {
                $this->addMiddleware($middleware);
            }
            $this->middlewareAreAdded = \true;
        }

        return $this->callMiddlewareStack($request, $response);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @throws CoreException
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $resolver = $this->container->get('resolver');

        if ($resolver instanceof ResolverInterface) {
            return $resolver->resolve($this->getHandler(), $request, $response);
        }

        throw new CoreException('Resolver is not instanceof ResolverInterface', 500);
    }
}