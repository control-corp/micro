<?php

namespace Micro\Application;

use Micro\Exception\Exception as CoreException;
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
use Micro\Log\ErrorHandler;
use Micro\Log\File as FileLog;
use Micro\Container\ContainerInterface;
use Micro\Application\Resolver\ResolverInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Micro\Http\Request;
use Micro\Http\Response\HtmlResponse;

class Application implements ExceptionHandlerInterface, ResolverInterface
{
    use MiddlewareAwareTrait;

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
     * @var boolean
     */
    private $booted = \false;

    /**
     * @var boolean
     */
    private $useMiddleware = \true;

    /**
     * @var boolean
     */
    private $useEvent = \true;

    /**
     * @param ContainerInterface $container
     * @param string $name
     */
    public function __construct(ContainerInterface $container, $useMiddleware = \true, $useEvent = \true)
    {
        $this->container = $container;

        $this->container->set('app', $this);

        $this->useMiddleware = $useMiddleware;
        $this->useEvent = $useEvent;

        $this->registerMinimumServices();

        $this->registerExtraServices();
    }

    /**
     * Start the application
     * @return \Micro\Application\Application
     */
    public function run()
    {
        try {

            $this->boot();

            if ($this->useEvent === \true && ($eventResponse = $this->container->get('event')->trigger('application.start')) instanceof ServerRequestInterface) {
                $response = $eventResponse;
            } else {
                $response = $this->dispatch();
            }

            if ($this->useEvent === \true && ($eventResponse = $this->container->get('event')->trigger('application.end', ['response' => $response])) instanceof ResponseInterface) {
                $response = $eventResponse;
            }

            if (env('development')) {
                foreach ($this->exceptions as $exception) {
                    if ($exception instanceof \Exception) {
                        $response->getBody()->write('<pre>' . $exception->getMessage() . '</pre>');
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
     * Add middleware
     *
     * This method prepends new middleware to the app's middleware stack.
     *
     * @param  mixed    $callable The callback routine
     *
     * @return static
     */
    public function add($callable)
    {
        if (is_string($callable)) {
            $callable = $this->container->get($callable);
        }

        return $this->addMiddleware($callable);
    }

    /**
     * @return Application
     */
    public function registerMinimumServices()
    {
        if ($this->container->has('request') === \false) {
            $this->container->set('request', function () {
                return Request::createFromEnvironment();
            });
        }

        if ($this->container->has('response') === \false) {
            $this->container->set('response', function () {
                return new HtmlResponse();
            });
        }

        if ($this->container->has('event') === \false) {
            $this->container->set('event', function () {
                return new Event\Manager();
            });
        }

        if ($this->container->has('router') === \false) {
            $this->container->set('router', function () {
                return new Router();
            });
        }

        if ($this->container->has('resolver') === \false) {
            $this->container->set('resolver', $this);
        }

        if ($this->container->has('exception.handler') === \false) {
            $this->container->set('exception.handler', $this);
        }

        if ($this->container->has('logger') === \false) {
            $this->container->set('logger', ($logger = new FileLog()));
        } else {
            $logger = $this->container->get('logger');
        }

        ErrorHandler::register($logger);

        CoreException::setLogger($logger);
    }

    /**
     * @return Application
     */
    public function registerExtraServices()
    {
        $config = $this->container->get('config');

        if ($this->container->has('acl') === \false) {
            $this->container->set('acl', function ($app) use ($config) {
                if ($config->get('acl.enabled')) {
                    return new Acl();
                }
                return \null;
            });
        }

        if ($this->container->has('caches') === \false) {
            $this->container->set('caches', function ($app) use ($config) {
                $adapters = $config->get('cache.adapters', []);
                $caches = [];
                foreach ($adapters as $adapter => $adapterConfig) {
                    $caches[$adapter] = Cache::factory(
                        $adapterConfig['frontend']['adapter'], $adapterConfig['backend']['adapter'],
                        $adapterConfig['frontend']['options'], $adapterConfig['backend']['options']
                    );
                }
                return $caches;
            });
        }

        if ($this->container->has('cache') === \false) {
            $this->container->set('cache', function ($container) use ($config) {
                $adapters = $container->get('caches');
                $default  = (string) $config->get('cache.default');
                return isset($adapters[$default]) ? $adapters[$default] : \null;
            });
        }

        if ($this->container->has('db') === \false) {
            $this->container->set('db', function ($container) use ($config) {
                $default  = $config->get('db.default');
                $adapters = $config->get('db.adapters', []);
                if (!isset($adapters[$default])) {
                    return \null;
                }
                return Database::factory($adapters[$default]['adapter'], $adapters[$default]);
            });
        }

        if ($config->get('db.set_default_adapter')) {
            TableAbstract::setDefaultAdapter($this->container->get('db'));
        }

        if ($config->get('db.set_default_cache')) {
            TableAbstract::setDefaultMetadataCache($this->container->get('cache'));
        }

        if ($this->container->has('translator') === \false) {
            $this->container->set('translator', function () {
                return new Translator();
            });
        }

        $sessionConfig = $config->get('session', []);

        if (!empty($sessionConfig)) {
            Session::register($sessionConfig);
        }

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
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function dispatch(ServerRequestInterface $request = \null, ResponseInterface $response = \null)
    {
        $request = $request ?: $this->container->get('request');
        $response = $response ?: $this->container->get('response');

        try {

            if ($this->useMiddleware === \true) {
                return $this->callMiddlewareStack($request, $response);
            } else {
                return $this($request, $response);
            }

        } catch (\Exception $e) {

            try {

                $exceptionHandler = $this->container->get('exception.handler');

                if (!$exceptionHandler instanceof ExceptionHandlerInterface) {
                    throw $e;
                }

                if (($exceptionResponse = $exceptionHandler->handleException($e, $request, $response)) instanceof ResponseInterface) {
                    return $exceptionResponse;
                }

                if (env('development')) {
                    $response->getBody()->write((string) $exceptionResponse);
                }

            } catch (\Exception $e) {

                if (env('development')) {
                    $response->getBody()->write($e->getMessage());
                }
            }
        }

        return $response;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @throws CoreException
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $router = $this->getRouter();

        if ($this->container->get('config')->get('router.default_routes')) {
            $router->loadDefaultRoutes();
        }

        if (($route = $router->match($request)) === \null) {
            throw new CoreException('Route not found', 404);
        }

        foreach ($route->getParams() as $k => $v) {
            $request->withAttribute($k, $v);
        }

        if ($this->useEvent === \true && ($eventResponse = $this->container->get('event')->trigger('route.end', ['route' => $route])) instanceof ResponseInterface) {
            return $eventResponse;
        }

        if ($this->useMiddleware === \true) {
            return $route->run($request, $response);
        } else {
            return $route($request, $response);
        }
    }

    /**
     * @param ResponseInterface $response
     */
    public function send(ResponseInterface $response)
    {
        $empty = false;

        if (method_exists($response, 'isEmpty')) {
            $empty = $response->isEmpty();
        }

        if ($empty === \true) {
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
        if ($empty === \false) {

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
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function handleException(\Exception $e, ServerRequestInterface $request, ResponseInterface $response)
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

        $resolver = $this->container->get('resolver');

        if ($resolver instanceof ResolverInterface) {
            return $resolver->resolve($package, $request, $response);
        }

        throw new CoreException('Resolver is not instanceof ResolverInterface', 500);
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
                $instance->setContainer($this->container)->boot();
                $this->packages[$package] = $instance;
            }
        }

        $this->booted = \true;
    }

    /**
     * @param string $package
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param bool $subRequest
     * @throws CoreException
     * @return ResponseInterface
     */
    public function resolve($package, ServerRequestInterface $request, ResponseInterface $response, $subRequest = \false)
    {
        if (!is_string($package) || strpos($package, '@') === \false) {

            if ($package instanceof \Closure) {
                $package = $package->__invoke($this->container);
            }

            if ($package instanceof ResponseInterface) {
                return $package;
            } else {
                $response->getBody()->write((string) $package);
                return $response;
            }
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
            if ($packageInstance instanceof ContainerAwareInterface) {
                $packageInstance->setContainer($this->container);
            }
        }

        if ($packageInstance instanceof Controller) {
            $action = lcfirst(Utils::camelize($action)) . 'Action';
        } else {
            $action = lcfirst(Utils::camelize($action));
        }

        if (!method_exists($packageInstance, $action)) {
            throw new CoreException('Method "' . $action . '" not found in "' . $package . '"', 404);
        }

        $scope = '';

        if ($packageInstance instanceof Controller) {
            $packageInstance->init();
            $scope = $packageInstance->getScope();
        }

        if (($packageResponse = $packageInstance->$action()) instanceof ResponseInterface) {
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

            if ($this->useEvent === \true && ($eventResponse = $this->container->get('event')->trigger('render.start', ['view' => $packageResponse])) instanceof ResponseInterface) {
                return $eventResponse;
            }

            if ($subRequest) {
                $packageResponse->setRenderParent(\false);
            }

            $response->getBody()->write((string) $packageResponse->render());

        } else {

            $response->getBody()->write((string) $packageResponse);
        }

        return $response;
    }

    /**
     * @return array of Package 's
     */
    public function getPackages()
    {
        return $this->packages;
    }

    /**
     * @param string $package
     * @throws CoreException
     * @return Package
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