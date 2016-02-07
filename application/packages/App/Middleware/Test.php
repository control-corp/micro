<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Micro\Application\View;
use Micro\Event\Message;
use Micro\Http\Response\HtmlResponse;
use Micro\Http\Stream;

class Test
{
    protected $view;

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        $this->before();

        $response = $next($request, $response);

        $this->after($response);

        return $response;
    }

    public function before()
    {
        if (\config('micro_debug.handlers.dev_tools')) {

            \app('event')->attach('render.start', function (Message $message) {
                $view = $message->getParam('view');
                $view->section('styles', (string) $this->view->partial('css'));
            });

            $this->view = new View('debug');

            $this->view->addPath(package_path('App', 'Resources'));
        }

        if (\config('micro_debug.handlers.fire_php')) {

            $db = \app('db');

            if ($db) {
                $db->setProfiler(\true);
            }
        }
    }

    public function after(ResponseInterface $response)
    {
        if (\config('micro_debug.handlers.dev_tools')) {

            if ($response instanceof HtmlResponse) {

                $body = $response->getBody();

                if ($body->isSeekable()) {
                    $body->rewind();
                }

                $b = $body->getContents();
                $b = explode('</body>', $b);
                $b[0] .= str_replace(array("\n", "\t", "\r"), "", $this->view->render()) . '</body>';

                $body = new Stream(fopen('php://temp', 'r+'));
                $body->write(implode('', $b));
                $body->rewind();

                $response->withBody($body);
            }
        }

        if (config('micro_debug.handlers.fire_php')) {

            $db = \app('db');

            if ($db) {

                $profiler = $db->getProfiler();

                if ($profiler->getEnabled()) {

                    $totalTime    = $profiler->getTotalElapsedSecs();
                    $queryCount   = $profiler->getTotalNumQueries();
                    $longestTime  = 0;
                    $longestQuery = \null;

                    $total = sprintf('%.6f', microtime(\true) - $_SERVER['REQUEST_TIME_FLOAT']);

                    if ($profiler->getQueryProfiles()) {
                        $label = 'Executed ' . $queryCount . ' queries in ' . sprintf('%.6f', $totalTime) . ' seconds. (' . ($total ? \round(($totalTime / $total) * 100, 2) : 0) . '%)';
                        $table = [];
                        $table[] = ['Time', 'Event', 'Parameters'];
                        foreach ($profiler->getQueryProfiles() as $k => $query) {
                            if ($query->getElapsedSecs() > $longestTime) {
                                $longestTime  = $query->getElapsedSecs();
                                $longestQuery = $k;
                            }
                        }
                        foreach ($profiler->getQueryProfiles() as $k => $query) {
                            $table[] = [\sprintf('%.6f', $query->getElapsedSecs()) . ($k == $longestQuery ? ' !!!' : ''), $query->getQuery(), ($params = $query->getQueryParams()) ? $params : \null];
                        }
                        FirePHP\FirePHP::getInstance()->table('DB - ' . $label, $table);
                    }
                }
            }
        }

        if (($file = \config('micro_debug.handlers.performance')) !== \null) {

            $forStore = [];

            foreach (\MicroLoader::getFiles() as $class => $file) {
                if (\substr($class, 0, 6) === 'Micro\\') {
                    $forStore[$class] = $file;
                }
            }

            \file_put_contents($file, "<?php\nreturn " . \var_export($forStore, \true) . ";", \LOCK_EX);
        }
    }
}