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
    private $modules = [];

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

            $this->marshalConfigKeys();

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
     * Dispatch the application request
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
            $router->mapFromConfig($this->container['config']->get('router.routes', []))
                   ->loadDefaultRoutes();
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

        $module = current($errorHandler);

        if ($currentRoute) {
            if (isset($errorHandler[$currentRoute->getName()])) {
                $module = $errorHandler[$currentRoute->getName()];
            }
        }

        $request->withAttribute('exception', $e);

        $resolver = $this->container->get('resolver');

        if (\is_object($resolver) && \method_exists($resolver, 'resolve')) {
            return $resolver->resolve($module, $request, $response);
        }

        throw new CoreException(\sprintf('Resolver [%s] does not have method "resolve"', \is_object($resolver) ? get_class($resolver) : gettype($resolver)), 500);
    }

    /**
     * Boot the application
     * @throws CoreException
     * @return Application
     */
    public function boot()
    {
        if (\true === $this->booted) {
            return $this;
        }

        $modules = $this->container->get('config')->get('modules', []);

        \MicroLoader::addPath($modules);

        foreach ($modules as $module => $path) {

            $moduleInstance = $module . '\\Module';

            if (\class_exists($moduleInstance, \true)) {

                $instance = new $moduleInstance($this->container);

                if (!$instance instanceof Module) {
                    throw new CoreException(\sprintf('%s must be instance of Micro\Application\Module', $moduleInstance), 500);
                }

                $instance->boot($this->container);

                $this->modules[$module] = $instance;

                continue;
            }

            throw new CoreException(sprintf('[' . __METHOD__ . '] Module [%s] is loaded but class [%s\Module] is missing', $module, $module));
        }

        $this->booted = \true;

        return $this;
    }

    /**
     * @param string $module
     * @return array|null
     */
    public function matchResolve($module)
    {
        if (!\is_string($module)) {
            return \null;
        }

        if (\preg_match('~^([^\@]+)\@([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$~', $module, $matches)) {
            return [$matches[1], $matches[2]];
        }

        return \null;
    }

    /**
     * @param mixed $module
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param bool $subRequest
     * @throws CoreException
     * @return ResponseInterface
     */
    public function resolve($module, ServerRequestInterface $request, ResponseInterface $response, $subRequest = \false)
    {
        if ($module instanceof ResponseInterface) {
            return $module;
        }

        if (($matches = $this->matchResolve($module)) === \null) {

            // write string to the response
            if (\is_string($module) || (\is_object($module) && \method_exists($module, '__toString'))) {

                $response->getBody()->write((string) $module);

                return $response;
            }

            throw new CoreException(\sprintf('Handler [%s] must be in [Handler@action] format', (\is_object($module) ? \get_class($module) : $module)));
        }

        $module = $matches[0];
        $action = $matches[1];

        if ($this->container->has($module)) {

            $moduleInstance = $this->container->get($module);

            if (!\is_object($moduleInstance) || $moduleInstance instanceof \Closure) {
                throw new CoreException('Handler "' . $module . '" is container service but it is not object', 500);
            }

            $module = \get_class($moduleInstance);

        } else {

            if (!\class_exists($module, \true)) {
                throw new CoreException('Handler class "' . $module . '" not found', 404);
            }

            $moduleInstance = new $module($request, $response, $this->container);

            if ($moduleInstance instanceof ContainerAwareInterface) {
                $moduleInstance->setContainer($this->container);
            }
        }

        $parts = explode('\\', $module);

        $moduleParam = Utils::decamelize($parts[0]);
        $controllerParam = Utils::decamelize($parts[count($parts) - 1]);
        $actionParam = Utils::decamelize($action);

        $request->withAttribute('module', $moduleParam);
        $request->withAttribute('controller', $controllerParam);
        $request->withAttribute('action', $actionParam);

        if ($moduleInstance instanceof Controller) {
            $action = $action . 'Action';
        }

        if (!\method_exists($moduleInstance, $action) && !\method_exists($moduleInstance, '__call')) {
            throw new CoreException('Method "' . $action . '" not found in "' . $module . '"', 404);
        }

        if ($moduleInstance instanceof Controller) {
            $moduleInstance->init();
        }

        $scope = '';

        if (\method_exists($moduleInstance, 'getScope')) {
            $scope = $moduleInstance->getScope();
        }

        if (($moduleResponse = $moduleInstance->$action()) instanceof ResponseInterface) {
            return $moduleResponse;
        }

        if (\is_object($moduleResponse) && !$moduleResponse instanceof View) {
            throw new CoreException('Handler response is object and must be instance of View', 500);
        }

        // resolve View object

        if ($moduleResponse === \null || is_array($moduleResponse)) {

            if ($moduleInstance instanceof Controller) {
                $view = $moduleInstance->getView();
            } else {
                $view = new View();
            }

            if (is_array($moduleResponse)) {
                $view->assign($moduleResponse);
            }

            $moduleResponse = $view;
        }

        if ($moduleResponse instanceof View) {

            if ($moduleResponse->getTemplate() === \null) {
                $moduleResponse->setTemplate(($scope ? $scope . '/' : '') . $controllerParam . '/' . $actionParam);
            }

            try {
                $moduleResponse->addPath(module_path($parts[0], '/Resources/views'), $moduleParam);
                $moduleResponse->addPath(config('view.paths', []));
            } catch (\Exception $e) {

            }

            if ($this->useEvent === \true
                && ($eventResponse = $this->container->get('event')->trigger('render.start', ['view' => $moduleResponse])) instanceof ResponseInterface
            ) {
                return $eventResponse;
            }

            if ($subRequest) {
                $moduleResponse->setRenderParent(\false);
            }

            $response->getBody()->write((string) $moduleResponse->render());

        } else {

            $response->getBody()->write((string) $moduleResponse);
        }

        return $response;
    }

    /**
     * @return array of Module 's
     */
    public function getModules()
    {
        return $this->modules;
    }

    /**
     * @param string $module
     * @throws CoreException
     * @return Module
     */
    public function getModule($module)
    {
        if (!isset($this->modules[$module])) {
            throw new CoreException('Module "' . $module . '" not found');
        }

        return $this->modules[$module];
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
            if (!isset($adapters[$default]) || !isset($adapters[$default]['adapter'])) {
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

        $this->container->set('event', function () {
            return new Event\Manager();
        }, \false);

        $this->container->set('logger', function () use ($config) {
            return new FileLog($config->get('log'));
        }, \false);

        if ($config->get('log.enabled')) {
            $logger = $this->container->get('logger');
            ErrorHandler::register($logger);
            CoreException::setLogger($logger);
        }

        $this->container->setBindings($this, [
            'resolver' => 'registerResolverBinder',
            'exception.handler' => 'registerExceptionBinder',
            'translator' => 'registerTranslatorBinder',
            'caches' => 'registerCacheBinder',
            'cache' => 'registerCacheBinder',
            'acl' => 'registerAclBinder',
            'db' => 'registerDbBinder',
        ]);
    }

    public function marshalConfigKeys()
    {
        $config = $this->container->get('config');

        $middlewares = $config->get('middleware', []);
        if (!empty($middlewares)) {
            foreach($middlewares as $middleware) {
                if (is_array($middleware)) {
                    $this->add($middleware[0], $middleware[1]);
                } else {
                    $this->add($middleware);
                }
            }
        }

        $dependencies = $config->get('dependencies', []);
        if (!empty($dependencies)) {
            $this->container->configure($dependencies);
        }

        $files = $config->get('microloader.files', []);
        if (!empty($files)) {
            \MicroLoader::addFiles($files);
        }

        $session = $config->get('session', []);
        if (!empty($session)) {
            Session::register($session);
        }
    }
}