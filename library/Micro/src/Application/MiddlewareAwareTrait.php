<?php

namespace Micro\Application;

use RuntimeException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use UnexpectedValueException;
use Micro\Container\ContainerInterface;

trait MiddlewareAwareTrait
{
    /**
     * @var array
     */
    protected $middlewarePending = [];

    /**
     * @var array
     */
    protected $middlewareStack;

    /**
     * @var bool
     */
    protected $middlewareLock = \false;

    /**
     * @param mixed $middleware
     * @param int $priority
     * @return self
     */
    public function add($middleware, $priority = 1)
    {
        if (\is_array($middleware)) {
            foreach ($middleware as $k => $v) {
                if (is_int($k)) {
                    $this->add($v, $priority);
                } else {
                    $this->add($k, $v);
                }
            }
            return $this;
        }

        if (!isset($this->middlewarePending[$priority])) {
            $this->middlewarePending[$priority] = [];
        }

        $this->middlewarePending[$priority][] = $middleware;

        return $this;
    }

    /**
     * @return boolean
     */
    public function hasMiddleware()
    {
        return !empty($this->middlewarePending);
    }

    /**
     * Add middleware
     *
     * This method prepends new middleware to the application middleware stack.
     *
     * @param callable $callable Any callable that accepts three arguments:
     *                           1. A Request object
     *                           2. A Response object
     *                           3. A "next" middleware callable
     * @return static
     *
     * @throws RuntimeException         If middleware is added while the stack is dequeuing
     * @throws UnexpectedValueException If the middleware doesn't return a Psr\Http\Message\ResponseInterface
     */
    protected function addMiddleware(callable $callable)
    {
        if ($this->middlewareLock) {
            throw new RuntimeException('Middleware can’t be added once the stack is dequeuing');
        }

        if ($this->middlewareStack === \null) {
            $this->seedMiddlewareStack();
        }

        $next = array_pop($this->middlewareStack);

        $this->middlewareStack[] = function (ServerRequestInterface $req, ResponseInterface $res) use ($callable, $next) {

            $result = call_user_func($callable, $req, $res, $next);

            if ($result instanceof ResponseInterface === false) {
                throw new UnexpectedValueException(
                    'Middleware must return instance of \Psr\Http\Message\ResponseInterface'
                );
            }

            return $result;
        };

        return $this;
    }

    /**
     * Seed middleware stack with first callable
     *
     * @param callable $kernel The last item to run as middleware
     *
     * @throws RuntimeException if the stack is seeded more than once
     */
    protected function seedMiddlewareStack(callable $middleware = null)
    {
        if ($this->middlewareStack !== \null) {
            throw new RuntimeException('MiddlewareStack can only be seeded once.');
        }

        if ($middleware === \null) {
            $middleware = $this;
        }

        $this->middlewareStack = [$middleware];

        return $this;
    }

    /**
     * Call middleware stack
     *
     * @param  ServerRequestInterface $req A request object
     * @param  ResponseInterface      $res A response object
     * @param  ContainerInterface     $container A container object
     *
     * @return ResponseInterface
     */
    public function callMiddlewareStack(ServerRequestInterface $req, ResponseInterface $res, ContainerInterface $container)
    {
        if ($this->middlewareStack === \null) {
            $this->seedMiddlewareStack();
        }

        ksort($this->middlewarePending);

        foreach ($this->middlewarePending as $priority => $middlewares) {

            krsort($middlewares);

            foreach ($middlewares as $k => $middleware) {

                unset($this->middlewarePending[$priority][$k]);

                if (\is_string($middleware) && $container->has($middleware)) {
                    $middleware = $container->get($middleware);
                } elseif (\is_string($middleware) && \class_exists($middleware)) {
                    $middleware = new $middleware;
                }

                if (!\is_callable($middleware)) {
                    continue;
                }

                $this->addMiddleware($middleware);
            }

            unset($this->middlewarePending[$priority]);
        }

        $start = array_pop($this->middlewareStack);

        $this->middlewareLock = \true;

        $res = $start($req, $res);

        $this->middlewareLock = \false;

        return $res;
    }
}
