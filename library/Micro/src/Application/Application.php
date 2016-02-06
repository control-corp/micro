<?php

namespace Micro\Application;

use Micro\Exception\Exception as CoreException;
use Micro\Http\Request;
use Micro\Http\Response;
use Micro\Router\Router;
use Micro\Event;
use Micro\Container\Container;
use Micro\Container\ContainerAwareInterface;
use Micro\Exception\ExceptionHandlerInterface;
use Micro\Acl\Acl;
use Micro\Database\Database;
use Micro\Database\Table\TableAbstract;
use Micro\Cache\Cache;
use Micro\Translator\Translator;
use Micro\Session\Session;
use Micro\Application\Resolver\ResolverAwareInterface;
use Micro\Log\Log as CoreLog;
use Micro\Log\File as FileLog;

class Application implements ExceptionHandlerInterface
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var array
     */
    private $packages = [];

    /**
     * @var array of collected Exceptions
     */
    private $exceptions = [];

    /**
     * @var Application is booted
     */
    private $booted = \false;

    /**
     * @param Container $container
     * @param string $name
     */
    public function __construct(Container $container, $name = 'app')
    {
        $this->container = $container;

        $this->container->set($name, $this);

        $this->registerDefaultServices();
    }

    /**
     * Start the application
     * @return \Micro\Application\Application
     */
    public function run()
    {
        try {

            $this->boot();

            $response = $this->dispatch();

            if (env('development')) {
                foreach ($this->exceptions as $exception) {
                    if ($exception instanceof \Exception) {
                        $response->write('<pre>' . $exception->getMessage() . '</pre>');
                    }
                }
            }

            $this->send($response);

        } catch (\Exception $e) {
            if (env('development')) {
                echo $e->getMessage();
            }
        }
    }

    /**
     * @return \Micro\Application\Application
     */
    public function registerDefaultServices()
    {
        if (!isset($this->container['request'])) {
            $this->container['request'] = Request::createFromEnvironment();
        }

        if (!isset($this->container['response'])) {
            $this->container['response'] = new Response\HtmlResponse();
        }

        if (!isset($this->container['event'])) {
            $this->container['event'] = new Event\Manager();
        }

        if (!isset($this->container['router'])) {
            $this->container['router'] = new Router();
        }

        if (!isset($this->container['exception.handler'])) {
            $this->container['exception.handler'] = $this;
        }

        if (!isset($this->container['exception.handler.fallback'])) {
            $this->container['exception.handler.fallback'] = $this;
        }

        $config = $this->container->get('config');

        if (!isset($this->container['acl'])) {
            $this->container['acl'] = function ($app) use ($config) {
                if ($config->get('acl.enabled')) {
                    return new Acl();
                }
                return \null;
            };
        }

        if (!isset($this->container['caches'])) {
            $this->container['caches'] = function ($app) use ($config) {
                $adapters = $config->get('cache.adapters', []);
                $caches = [];
                foreach ($adapters as $adapter => $adapterConfig) {
                    $caches[$adapter] = Cache::factory(
                        $adapterConfig['frontend']['adapter'], $adapterConfig['backend']['adapter'],
                        $adapterConfig['frontend']['options'], $adapterConfig['backend']['options']
                    );
                }
                return $caches;
            };
        }

        if (!isset($this->container['cache'])) {
            $this->container['cache'] = function ($container) use ($config) {
                $adapters = $container->get('caches');
                $default  = (string) $config->get('cache.default');
                return isset($adapters[$default]) ? $adapters[$default] : \null;
            };
        }

        if (!isset($this->container['db'])) {
            $this->container['db'] = function ($container) use ($config) {
                $default  = $config->get('db.default');
                $adapters = $config->get('db.adapters', []);
                if (!isset($adapters[$default])) {
                    return \null;
                }
                return Database::factory($adapters[$default]['adapter'], $adapters[$default]);
            };
        }

        if ($config->get('db.set_default_adapter')) {
            TableAbstract::setDefaultAdapter($this->container->get('db'));
        }

        if ($config->get('db.set_default_cache')) {
            TableAbstract::setDefaultMetadataCache($this->container->get('cache'));
        }

        if (!isset($this->container['translator'])) {
            $this->container['translator'] = function () {
                return new Translator();
            };
        }

        /**
         * Register default logger
         */
        if (!isset($this->container['logger'])) {
            $this->container['logger'] = new FileLog();
        }

        CoreLog::setLogger($this->container->get('logger'));

        /**
         * Register session config
         */
        $sessionConfig = $config->get('session', []);

        if (!empty($sessionConfig)) {
            Session::register($sessionConfig);
        }

        CoreLog::register();
        CoreException::register();

        return $this;
    }

    /**
     * @param string $pattern
     * @param mixed $handler
     * @param string $name
     * @return Route
     */
    public function map($pattern, $handler, $name = \null)
    {
        return $this->getRouter()->map($pattern, $handler, $name);
    }

    /**
     * Unpackage the application request
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function dispatch(Request $request = \null, Response $response = \null)
    {
        $request = $request ?: $this->container->get('request');
        $response = $response ?: $this->container->get('response');
        $router = $this->getRouter();

        try {

            if ($this->container->get('config')->get('router.default_routes')) {
                $router->loadDefaultRoutes();
            }

            if (($route = $router->match($request)) === \null) {
                throw new CoreException('Route not found', 404);
            }

            $request->withAttributes(
                $route->getParams()
            );

            $routeHandler = $route->getHandler();

            if (is_string($routeHandler) && strpos($routeHandler, '@') !== \false) { // package format
                $routeHandler = $this->resolve($routeHandler, $request, $response);
            }

            if ($routeHandler instanceof Response) {
                $response = $routeHandler;
            } else {
                $response->write((string) $routeHandler);
            }

        } catch (\Exception $e) {

            try {

                $exceptionHandler = $this->container->get('exception.handler');

                if (!$exceptionHandler instanceof ExceptionHandlerInterface) {
                    throw $e;
                }

                if (($exceptionResponse = $exceptionHandler->handleException($e, $request, $response)) instanceof Response) {
                    return $exceptionResponse;
                }

                if (env('development')) {
                    $response->write((string) $exceptionResponse);
                }

            } catch (\Exception $e) {

                if (env('development')) {
                    $response->write($e->getMessage());
                }
            }
        }

        return $response;
    }

    public function send(Response $response)
    {
        if ($response->isEmpty()) {
            $response->withoutHeader('Content-Type')
                     ->withoutHeader('Content-Length');
        }

        $size = $response->getBody()->getSize();

        if ($size !== null && !$response->hasHeader('Content-Length')) {
            $response->withHeader('Content-Length', (string) $size);
        }

        // Send response
        if (!headers_sent()) {
            // Status
            header(sprintf(
                'HTTP/%s %s %s',
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase()
            ));

            // Headers
            foreach ($response->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header(sprintf('%s: %s', $name, $value), false);
                }
            }
        }

        // Body
        if (!$response->isEmpty()) {

            $body = $response->getBody();

            if ($body->isSeekable()) {
                $body->rewind();
            }

            $chunkSize      = $this->container->get('config')->get('settings.responseChunkSize', 4096);
            $contentLength  = $response->getHeaderLine('Content-Length');

            if (!$contentLength) {
                $contentLength = $body->getSize();
            }

            $totalChunks    = ceil($contentLength / $chunkSize);

            $lastChunkSize  = $contentLength % $chunkSize;

            $currentChunk   = 0;

            while (!$body->eof() && $currentChunk < $totalChunks) {

                if (++$currentChunk == $totalChunks && $lastChunkSize > 0) {
                    $chunkSize = $lastChunkSize;
                }

                echo $body->read($chunkSize);

                if (connection_status() != CONNECTION_NORMAL) {
                    break;
                }
            }
        }
    }

    /**
     * @param \Exception $e
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function handleException(\Exception $e, Request $request, Response $response)
    {
        $errorHandler = $this->container->get('config')->get('error');

        if (empty($errorHandler)) {
            throw $e;
        }

        $currentRoute = $this->container->get('router')->getCurrentRoute();

        $package = current($errorHandler);

        if ($currentRoute) {
            if (isset($errorHandler[$currentRoute->getName()])) {
                $package = $errorHandler[$currentRoute->getName()];
            }
        }

        $request->withAttribute('exception', $e);

        return $this->resolve($package, $request, $response);
    }

    /**
     * Boot the application
     * @throws CoreException
     */
    public function boot()
    {
        if (\true === $this->booted) {
            return;
        }

        $packages = $this->container->get('config')->get('packages', []);

        foreach ($packages as $package => $path) {

            $packageInstance = $package . '\\Package';

            if (\class_exists($packageInstance, \true)) {
                $instance = new $packageInstance($this);
                if (!$instance instanceof Package) {
                    throw new CoreException(\sprintf('%s must be instance of Micro\Application\Package', $packageInstance), 500);
                }
                $instance->setContainer($this)->boot();
                $this->packages[$package] = $instance;
            }
        }

        $this->booted = \true;
    }

    /**
     * @param string $package
     * @param Request $request
     * @param Response $response
     * @param bool $subRequest
     * @throws CoreException
     * @return Response
     */
    public function resolve($package, Request $request, Response $response, $subRequest = \false)
    {
        if (!is_string($package) || strpos($package, '@') === \false) {
            throw new CoreException('Package must be in [Package\Handler@action] format', 500);
        }

        list($package, $action) = explode('@', $package);

        if (!class_exists($package, \true)) {
            throw new CoreException('Package class "' . $package . '" not found', 404);
        }

        $parts = explode('\\', $package);

        $packageParam = Utils::decamelize($parts[0]);
        $controllerParam = Utils::decamelize($parts[count($parts) - 1]);
        $actionParam = Utils::decamelize($action);

        $request->withAttribute('package', $packageParam);
        $request->withAttribute('controller', $controllerParam);
        $request->withAttribute('action', $actionParam);

        if ($this->container->has($package)) {
            $packageInstance = $this->container->get($package);
        } else {
            $packageInstance = new $package($request, $response, $this->container);
        }

        if ($packageInstance instanceof Controller) {
            $action = lcfirst(Utils::camelize($action)) . 'Action';
        } else {
            $action = lcfirst(Utils::camelize($action));
        }

        if (!method_exists($packageInstance, $action)) {
            throw new CoreException('Method "' . $action . '" not found in "' . $package . '"', 404);
        }

        if ($packageInstance instanceof ContainerAwareInterface) {
            $packageInstance->setContainer($this->container);
        }

        if ($packageInstance instanceof ResolverAwareInterface) {
            $packageInstance->setRequest($request)
                            ->setResponse($response);
        }

        $scope = '';

        if ($packageInstance instanceof Controller) {
            $packageInstance->init();
            $scope = $packageInstance->getScope();
        }

        if (($packageResponse = $packageInstance->$action()) instanceof Response) {
            return $packageResponse;
        }

        if (is_object($packageResponse) && !$packageResponse instanceof View) {
            throw new CoreException('Package response is object and must be instance of View', 500);
        }

        if ($packageResponse === \null || is_array($packageResponse)) {

            if ($packageInstance instanceof Controller) {
                $view = $packageInstance->getView();
            } else {
                $view = new View();
            }

            if (is_array($packageResponse)) {
                $view->addData($packageResponse);
            }

            $packageResponse = $view;
        }

        if ($packageResponse instanceof View) {

            if ($packageResponse->getTemplate() === \null) {
                $packageResponse->setTemplate(($scope ? $scope . '/' : '') . $controllerParam . '/' . $actionParam);
            }

            $packageResponse->injectPaths((array) package_path($parts[0], 'Resources/views'));

            if (($eventResponse = $this->container->get('event')->trigger('render.start', ['view' => $packageResponse])) instanceof Response) {
                return $eventResponse;
            }

            if ($subRequest) {
                $packageResponse->setRenderParent(\false);
            }

            $response->write((string) $packageResponse->render());

        } else {

            $response->write((string) $packageResponse);
        }

        return $response;
    }

    /**
     * @return array of \Micro\Application\Package's
     */
    public function getPackages()
    {
        return $this->packages;
    }

    /**
     * @param string $package
     * @throws CoreException
     * @return \Micro\Application\Package
     */
    public function getPackage($package)
    {
        if (!isset($this->packages[$package])) {
            throw new CoreException('Package "' . $package . '" not found');
        }

        return $this->packages[$package];
    }

    /**
     * @param \Exception $e
     */
    public function collectException(\Exception $e)
    {
        $this->exceptions[] = $e;
    }

    /**
     * @return Router
     */
    public function getRouter()
    {
        return $this->container->get('router');
    }
}