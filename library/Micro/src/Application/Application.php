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

class Application extends Container implements ExceptionHandlerInterface
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
            $this->container['request'] = function () {
                return Request::createFromEnvironment();
            };
        }

        if (!isset($this->container['response'])) {
            $this->container['response'] = function () {
                return new Response\HtmlResponse();
            };
        }

        if (!isset($this->container['router'])) {
            $this->container['router'] = function ($container) {
                return new Router($container['request']);
            };
        }

        if (!isset($this->container['event'])) {
            $this->container['event'] = function () {
                return new Event\Manager();
            };
        }

        if (!isset($this->container['exception.handler'])) {
            $this->container['exception.handler'] = $this;
        }

        if (!isset($this->container['exception.handler.fallback'])) {
            $this->container['exception.handler.fallback'] = $this;
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
        return $this->container->get('router')->map($pattern, $handler, $name);
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

        try {

            if (($route = $this->container->get('router')->match()) === \null) {
                throw new CoreException('[' . __METHOD__ . '] Route not found', 404);
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
            $response = $response->withHeader('Content-Length', (string) $size);
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
                    throw new CoreException(\sprintf('[' . __METHOD__ . '] %s must be instance of Micro\Application\Package', $packageInstance), 500);
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
            throw new CoreException('[' . __METHOD__ . '] Package must be in [Package\Handler@action] format', 500);
        }

        list($package, $action) = explode('@', $package);

        if (!class_exists($package, \true)) {
            throw new CoreException('[' . __METHOD__ . '] Package class "' . $package . '" not found', 404);
        }

        $parts = explode('\\', $package);

        $packageParam = Utils::decamelize($parts[0]);
        $controllerParam = Utils::decamelize($parts[count($parts) - 1]);
        $actionParam = Utils::decamelize($action);

        $request->withAttribute('package', $packageParam);
        $request->withAttribute('controller', $controllerParam);
        $request->withAttribute('action', $actionParam);

        $packageInstance = new $package($request, $response);

        if (!method_exists($packageInstance, $action)) {
            throw new CoreException('[' . __METHOD__ . '] Method "' . $action . '" not found in "' . $package . '"', 404);
        }

        if ($packageInstance instanceof ContainerAwareInterface) {
            $packageInstance->setContainer($this);
        }

        if (($packageResponse = $packageInstance->$action()) instanceof Response) {
            return $packageResponse;
        }

        $response->write((string) $packageResponse);

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
            throw new CoreException('[' . __METHOD__ . '] Package "' . $package . '" not found');
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