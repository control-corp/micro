<?php

namespace Micro\Application;

use Micro\Exception\Exception as CoreException;
use Micro\Router\Router;
use Micro\Event;
use Micro\Container\Container;
use Micro\Container\ContainerAwareInterface;
use Micro\Acl\Acl;
use Micro\Database\Database;
use Micro\Database\Table\TableAbstract;
use Micro\Cache\Cache;
use Micro\Translator\Translator;
use Micro\Session\Session;
use Micro\Log\File as FileLog;
use Micro\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Micro\Http\Request;
use Micro\Http\Response\HtmlResponse;
use Micro\Router\Route;

class Application
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

        $this->registerServices();
    }

    /**
     * Start the application
     * @return Application
     */
    public function run()
    {
        try {

            $this->boot();

            if ($this->useEvent === \true
                && ($eventResponse = $this->container->get('event')->trigger('application.start')) instanceof ResponseInterface
            ) {
                $response = $eventResponse;
            } else {
                $response = $this->dispatch();
            }

            if ($this->useEvent === \true
                && ($eventResponse = $this->container->get('event')->trigger('application.end', ['response' => $response])) instanceof ResponseInterface
            ) {
                $response = $eventResponse;
            }

            $this->send($response);

        } catch (\Exception $e) {
            if (env('development')) {
                echo $e->getMessage();
            }
        }
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

            if ($this->useMiddleware === \true && $this->hasMiddleware()) {
                return $this->callMiddlewareStack($request, $response, $this->container);
            } else {
                return $this->__invoke($request, $response);
            }

        } catch (\Exception $e) {

            try {

                $exceptionHandler = $this->container->get('exception.handler');

                if (!\is_object($exceptionHandler) || !\method_exists($exceptionHandler, 'handleException')) {
                    throw $e;
                }

                if (($exceptionResponse = $exceptionHandler->handleException($e, $request, $response)) instanceof ResponseInterface) {
                    return $exceptionResponse;
                }

                $response->getBody()->write((string) $exceptionResponse);

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

        if ($this->container['config']['router.default_routes']) {
            $router->loadDefaultRoutes();
        }

        if (($route = $router->match($request->getUri()->getPath())) === \null) {
            throw new CoreException('Route not found', 404);
        }

        $request->withAttribute(Route::class, $route);

        foreach ($route->getParams() as $k => $v) {
            $request->withAttribute($k, $v);
        }

        if ($this->useEvent === \true
            && ($eventResponse = $this->container->get('event')->trigger('route.end', ['route' => $route])) instanceof ResponseInterface
        ) {
            return $eventResponse;
        }

        if ($this->useMiddleware === \true && $route->hasMiddleware()) {
            return $route->callMiddlewareStack($request, $response, $this->container);
        } else {
            return $route->__invoke($request, $response);
        }
    }

    /**
     * @param ResponseInterface $response
     */
    public function send(ResponseInterface $response)
    {
        $empty = \false;

        if (in_array($response->getStatusCode(), [204, 205, 304])) {
            $empty = \true;
        }

        if ($empty === \true) {
            $response->withoutHeader('Content-Type')
                     ->withoutHeader('Content-Length');
        }

        $size = $response->getBody()->getSize();

        if ($size !== \null && !$response->hasHeader('Content-Length')) {
            $response->withHeader('Content-Length', (string) $size);
        }

        // Send response
        if (!\headers_sent()) {
            // Status
            \header(\sprintf(
                'HTTP/%s %s %s',
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase()
            ));

            // Headers
            foreach ($response->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    \header(\sprintf('%s: %s', $name, $value), false);
                }
            }
        }

        // Body
        if ($empty === \false) {

            $body = $response->getBody();

            if ($body->isSeekable()) {
                $body->rewind();
            }

            $chunkSize      = $this->container->get('config')->get('response.responseChunkSize', 4096);
            $contentLength  = $response->getHeaderLine('Content-Length');

            if (!$contentLength) {
                $contentLength = $body->getSize();
            }

            $totalChunks    = \ceil($contentLength / $chunkSize);

            $lastChunkSize  = $contentLength % $chunkSize;

            $currentChunk   = 0;

            while (!$body->eof() && $currentChunk < $totalChunks) {

                if (++$currentChunk == $totalChunks && $lastChunkSize > 0) {
                    $chunkSize = $lastChunkSize;
                }

                echo $body->read($chunkSize);

                if (\connection_status() != \CONNECTION_NORMAL) {
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

        if (\is_object($resolver) && \method_exists($resolver, 'resolve')) {
            return $resolver->resolve($package, $request, $response);
        }

        throw new CoreException(\sprintf('Resolver [%s] does not have method "resolve"', \is_object($resolver) ? get_class($resolver) : gettype($resolver)), 500);
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
     * @return array|null
     */
    public function matchResolve($package)
    {
        if (!\is_string($package)) {
            return \null;
        }

        if (\preg_match('~^([^\@]+)\@([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$~', $package, $matches)) {
            return [$matches[1], $matches[2]];
        }

        return \null;
    }

    /**
     * @param mixed $package
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param bool $subRequest
     * @throws CoreException
     * @return ResponseInterface
     */
    public function resolve($package, ServerRequestInterface $request, ResponseInterface $response, $subRequest = \false)
    {
        if ($package instanceof ResponseInterface) {
            return $package;
        }

        if (($matches = $this->matchResolve($package)) === \null) {

            if (\is_string($package) || (\is_object($package) && \method_exists($package, '__toString'))) {

                $response->getBody()->write((string) $package);

                return $response;
            }

            throw new CoreException(\sprintf('Package [%s] must be in [Handler@action] format', (\is_object($package) ? \get_class($package) : $package)));
        }

        $package = $matches[0];
        $action = $matches[1];

        if ($this->container->has($package)) {

            $packageInstance = $this->container->get($package);

            if (!\is_object($packageInstance)) {
                throw new CoreException('Package "' . $package . '" is container service but it is not object', 500);
            }

            $package = \get_class($packageInstance);

        } else {

            if (!\class_exists($package, \true)) {
                throw new CoreException('Package class "' . $package . '" not found', 404);
            }

            $packageInstance = new $package($request, $response, $this->container);

            if ($packageInstance instanceof ContainerAwareInterface) {
                $packageInstance->setContainer($this->container);
            }
        }

        $parts = explode('\\', $package);

        $packageParam = Utils::decamelize($parts[0]);
        $controllerParam = Utils::decamelize($parts[count($parts) - 1]);
        $actionParam = Utils::decamelize($action);

        $request->withAttribute('package', $packageParam);
        $request->withAttribute('controller', $controllerParam);
        $request->withAttribute('action', $actionParam);

        if ($packageInstance instanceof Controller) {
            $action = $action . 'Action';
        }

        if (!\method_exists($packageInstance, $action) && !\method_exists($packageInstance, '__call')) {
            throw new CoreException('Method "' . $action . '" not found in "' . get_class($packageInstance) . '"', 404);
        }

        $scope = '';

        if ($packageInstance instanceof Controller) {
            $packageInstance->init();
            $scope = $packageInstance->getScope();
        }

        if (($packageResponse = $packageInstance->$action($request, $response, $this->container)) instanceof ResponseInterface) {
            return $packageResponse;
        }

        if (\is_object($packageResponse) && !$packageResponse instanceof View) {
            throw new CoreException('Package response is object and must be instance of View', 500);
        }

        // resolve View object

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

            try {
                $packageResponse->injectPaths((array) package_path($parts[0], 'Resources/views'));
            } catch (\Exception $e) {

            }

            if ($this->useEvent === \true
                && ($eventResponse = $this->container->get('event')->trigger('render.start', ['view' => $packageResponse])) instanceof ResponseInterface
            ) {
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

    public function registerResolverBinder()
    {
        $this->container->set('resolver',
            $this,
            \false
        );
    }

    public function registerExceptionBinder()
    {
        $this->container->set('exception.handler',
            $this,
            \false
        );
    }

    public function registerTranslatorBinder()
    {
        $this->container->set('translator', function () {
            return new Translator();
        }, \false);
    }

    public function registerCacheBinder()
    {
        $config = $this->container->get('config');

        $this->container->set('caches', function () use ($config) {
            $adapters = $config->get('cache.adapters', []);
            $caches = [];
            foreach ($adapters as $adapter => $adapterConfig) {
                $caches[$adapter] = Cache::factory(
                    $adapterConfig['frontend']['adapter'], $adapterConfig['backend']['adapter'],
                    $adapterConfig['frontend']['options'], $adapterConfig['backend']['options']
                );
            }
            return $caches;
        }, \false);

        $this->container->set('cache', function ($container) use ($config) {
            $adapters = $container->get('caches');
            $default  = (string) $config->get('cache.default');
            return isset($adapters[$default]) ? $adapters[$default] : \null;
        }, \false);
    }

    public function registerAclBinder()
    {
        $this->container->set('acl', function ($c) {
            if ($c->get('config')->get('acl.enabled')) {
                return new Acl();
            }
            return \null;
        }, \false);
    }

    public function registerDbBinder()
    {
        $config = $this->container->get('config');

        $this->container->set('db', function ($container) use ($config) {
            $default  = $config->get('db.default');
            $adapters = $config->get('db.adapters', []);
            if (!isset($adapters[$default])) {
                return \null;
            }
            return Database::factory($adapters[$default]['adapter'], $adapters[$default]);
        }, \false);

        if ($config->get('db.set_default_adapter')) {
            TableAbstract::setDefaultAdapter($this->container->get('db'));
        }

        if ($config->get('db.set_default_cache')) {
            TableAbstract::setDefaultMetadataCache($this->container->get('cache'));
        }
    }

    public function registerEventBinder()
    {
        $this->container->set('event', function () {
            return new Event\Manager();
        }, \false);
    }

    public function registerServices()
    {
        $config = $this->container->get('config');

        $this->container->set('request', function () {
            return Request::createFromEnvironment();
        }, \false);

        $this->container->set('response', function () {
            return new HtmlResponse();
        }, \false);

        $this->container->set('router', function () {
            return new Router();
        }, \false);

        $this->container->set('logger', function () use ($config) {
            return new FileLog($config->get('log'));
        }, \false);

        if ($config->get('log.enabled')) {
            $logger = $this->container->get('logger');
            ErrorHandler::register($logger);
            CoreException::setLogger($logger);
        }

        foreach((array) $config->get('middleware', []) as $middleware) {
            $this->add($middleware);
        }

        $sessionConfig = $config->get('session', []);

        if (!empty($sessionConfig)) {
            Session::register($sessionConfig);
        }

        $this->container->setBindings($this, [
            'event' => 'registerEventBinder',
            'resolver' => 'registerResolverBinder',
            'exception.handler' => 'registerExceptionBinder',
            'translator' => 'registerTranslatorBinder',
            'caches' => 'registerCacheBinder',
            'cache' => 'registerCacheBinder',
            'acl' => 'registerAclBinder',
            'db' => 'registerDbBinder',
        ]);
    }
}