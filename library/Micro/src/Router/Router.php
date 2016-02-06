<?php

namespace Micro\Router;

use Micro\Exception\Exception as CoreException;
use Micro\Http\Request;
use Micro\Application\Utils;
use Micro\Container\ContainerAwareInterface;
use Micro\Container\ContainerAwareTrait;

class Router implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var \Micro\Http\Request
     */
    protected $request;

    /**
     * @var array
     */
    protected $routes = [];

    /**
     * @var array
     */
    protected $routesStatic = [];

    /**
     * @var array
     */
    protected $globalParams = [];

    /**
     * @var \Micro\Router\Route
     */
    protected $currentRoute;

    /**
     * @var string
     */
    const URL_DELIMITER = '/';

    /**
     * @param Request $requestUri
     * @return Route|null
     */
    public function match(Request $request)
    {
        $uri = $request->getUri()->getPath();

        if (empty($uri) || $uri[0] !== static::URL_DELIMITER) {
            $uri = static::URL_DELIMITER . $uri;
        }

        if ($uri !== static::URL_DELIMITER) {
            $uri = rtrim($uri, static::URL_DELIMITER);
        }

        if (isset($this->routesStatic[$uri])) {
            return $this->currentRoute = $this->routes[$this->routesStatic[$uri]];
        }

        foreach ($this->routes as $route) {
            if ($route instanceof Route && $route->match($uri)) {
                return $this->currentRoute = $route;
            }
        }

        return \null;
    }

    /**
     * @param string $pattern
     * @param \Closure|string $handler
     * @param string $name
     * @throws \Exception
     * @return \Micro\Router\Route
     */
    public function map($pattern, $handler, $name = \null)
    {
        if (\null === $name) {
            $name = preg_replace('~[^\w]~', '-', $pattern);
            $name = preg_replace('~[\-]+~', '-', $name);
            $name = trim('route-' . trim($name, '-'), '-');
        }

        if (isset($this->routes[$name])) {
            throw new CoreException(sprintf('[' . __METHOD__ . '] Route "%s" already exists!', $name), 500);
        }

        if (empty($pattern) || $pattern[0] !== static::URL_DELIMITER) {
            $pattern = static::URL_DELIMITER . $pattern;
        }

        if ($pattern !== static::URL_DELIMITER) {
            $pattern = rtrim($pattern, static::URL_DELIMITER);
        }

        $route = new Route($name, $pattern, $handler);

        if (Route::isStatic($pattern)) {
            if (isset($this->routesStatic[$pattern])) {
                throw new CoreException(sprintf('[' . __METHOD__ . '] Route pattern "%s" already exists!', $pattern), 500);
            }
            $this->routesStatic[$pattern] = $name;
        }

        $this->routes[$name] = $route;

        return $route;
    }

    /**
     * @param string $name
     * @param array $data
     * @param boolean $reset
     * @param boolean $qsa
     * @throws \Exception
     * @return string
     */
    public function assemble($name = \null, array $data = [], $reset = \false, $qsa = \true)
    {
        static $request, $basePath, $queryParams;

        if ($name === \null && $this->currentRoute instanceof Route) {
            $name = $this->currentRoute->getName();
        }

        if (!isset($this->routes[$name])) {
            throw new CoreException(\sprintf('[' . __METHOD__ . '] Route "%s" not found!', $name), 500);
        }

        $route = $this->routes[$name];

        $pattern = $route->getPattern();

        $data += $this->globalParams;

        if (isset($this->routesStatic[$pattern])) {
            $url = $pattern;
        } else {
            $url = $route->assemble($data, $reset);
        }

        if ($request === \null) {

            $request = $this->container->get('request');

            if ($basePath === \null) {
                $basePath = $request->getUri()->getBasePath();
            }

            if ($queryParams === \null) {
                $queryParams = $request->getQueryParams();
            }
        }

        if ($qsa === \true && !empty($data)) {

            $queryParams = $data + $queryParams;

            if (!empty($queryParams)) {
                $url .= '?' . \http_build_query($queryParams);
            }
        }

        if ($url === self::URL_DELIMITER) {
            return $basePath . $url;
        }

        return $basePath . rtrim($url, static::URL_DELIMITER);
    }

    /**
     * @param string $key
     * @param string $value
     * @return \Micro\Router\Router
     */
    public function setGlobalParam($key, $value)
    {
        $this->globalParams[$key] = $value;

        return $this;
    }

    /**
     * @param string $key
     * @param string|null $value
     * @return mixed
     */
    public function getGlobalParam($key, $value = \null)
    {
        if (isset($this->globalParams[$key])) {
            return $this->globalParams[$key];
        }

        return $value;
    }

    /**
     * @return array of \Micro\Router\Route's
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * @param string $name
     * @return \Micro\Router\Route|\null
     */
    public function getRoute($name)
    {
        if (isset($this->routes[$name])) {
            return $this->routes[$name];
        }

        return \null;
    }

    /**
     * @param \Micro\Router\Route $route
     * @return \Micro\Router\Router
     */
    public function setCurrentRoute(Route $route)
    {
        $this->currentRoute = $route;

        return $this;
    }

    /**
     * @return \Micro\Router\Route
     */
    public function getCurrentRoute()
    {
        return $this->currentRoute;
    }

    /**
     * @return \Micro\Router\Router
     */
    public function loadDefaultRoutes()
    {
        if (!isset($this->routes['admin'])) {

            $route = $this->map('/admin[/{package}][/{controller}][/{action}][/{id}]', function () {

                static $cache = [];

                $params = $this->getParams();

                $hash = 'admin_' . $params['package'] . '_' . $params['controller'] . '_' . $params['action'] . '_' . $params['id'];

                if (isset($cache[$hash])) {
                    return $cache[$hash];
                }

                $package = \ucfirst(Utils::camelize($params['package']));
                $controller = \ucfirst(Utils::camelize($params['controller']));
                $action = \lcfirst(Utils::camelize($params['action']));

                return $cache[$hash] = $package . '\\Controller\Admin\\' . $controller . '@' . $action;

            }, 'admin');

            $route->setDefaults(['package' => 'app', 'controller' => 'index', 'action' => 'index', 'id' => \null]);
        }

        if (!isset($this->routes['default'])) {

            $route = $this->map('/{package}[/{controller}][/{action}][/{id}]', function () {

                static $cache = [];

                $params = $this->getParams();

                $hash = 'front_' . $params['package'] . '_' . $params['controller'] . '_' . $params['action'] . '_' . $params['id'];

                if (isset($cache[$hash])) {
                    return $cache[$hash];
                }

                $package = \ucfirst(Utils::camelize($params['package']));
                $controller = \ucfirst(Utils::camelize($params['controller']));
                $action = \lcfirst(Utils::camelize($params['action']));

                return $cache[$hash] = $package . '\\Controller\Front\\' . $controller . '@' . $action;

            }, 'default');

            $route->setDefaults(['package' => 'app', 'controller' => 'index', 'action' => 'index', 'id' => \null]);
        }

        return $this;
    }

    /**
     * @return \Micro\Router\Router
     */
    public function mapFromConfig()
    {
        $routes = $this->container->get('config')->get('routes', []);

        foreach ($routes as $name => $config) {

            if (!isset($config['pattern']) || !isset($config['handler'])) {
                continue;
            }

            $route = $this->map($config['pattern'], $config['handler'], $name);

            if (isset($config['conditions'])) {
                $route->setConditions($config['conditions']);
            }

            if (isset($config['defaults'])) {
                $route->setDefaults($config['defaults']);
            }
        }

        return $this;
    }
}