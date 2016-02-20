<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Micro\Application\View;
use Micro\Event\Message;
use Micro\Http\Response\HtmlResponse;
use Micro\Database\Adapter\AdapterAbstract;
use Micro\Http\TempStream;
use Micro\Database\Profiler;

class Test
{
    /**
     * @var View
     */
    protected $view;

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        $this->before();

        $response = $next($request, $response);

        return $this->after($response);
    }

    public function before()
    {
        if (\config('micro_debug.handlers.dev_tools')) {

            $view = $this->view = new View('debug');
            $view->addPath(module_path('App', '/views/middleware'));

            \app('event')->attach('render.start', function (Message $message) use ($view) {
                $message->getParam('view')->section('styles', (string) $view->partial('css'));
            });
        }

        if (\config('micro_debug.handlers.fire_php') && ($db = \app('db')) instanceof AdapterAbstract) {
            $profiler = $db->getProfiler();
            if ($profiler instanceof Profiler) {
                $profiler->setEnabled(\true);
            } else {
                $db->setProfiler(\true);
            }
        }
    }

    public function after(ResponseInterface $response)
    {
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

                    $label = 'Executed ' . $queryCount . ' queries in ' . sprintf('%.6f', $totalTime) . ' seconds. (' . ($total ? \round(($totalTime / $total) * 100, 2) : 0) . '%)';
                    $table = [];
                    $table[] = ['Time', 'Event', 'Parameters'];

                    if ($profiler->getQueryProfiles()) {
                        foreach ($profiler->getQueryProfiles() as $k => $query) {
                            if ($query->getElapsedSecs() > $longestTime) {
                                $longestTime  = $query->getElapsedSecs();
                                $longestQuery = $k;
                            }
                        }
                        foreach ($profiler->getQueryProfiles() as $k => $query) {
                            $table[] = [\sprintf('%.6f', $query->getElapsedSecs()) . ($k == $longestQuery ? ' !!!' : ''), $query->getQuery(), ($params = $query->getQueryParams()) ? $params : \null];
                        }
                    }

                    FirePHP\FirePHP::getInstance()->table('DB - ' . $label, $table);
                }
            }
        }

        if (\config('micro_debug.handlers.dev_tools')) {

            if ($response instanceof HtmlResponse) {

                $body = $response->getBody();

                if ($body->isSeekable()) {
                    $body->rewind();
                }

                $b = $body->getContents();
                $b = explode('</body>', $b);
                $b[0] .= str_replace(array("\n", "\t", "\r"), "", $this->view->render()) . '</body>';

                $response->withBody(new TempStream(implode('', $b)));
            }
        }


        if (($fileForCache = \config('micro_debug.handlers.performance')) !== \null) {

            $forStore = [];

            foreach (\MicroLoader::getFiles() as $class => $file) {
                if (\substr($class, 0, 6) === 'Micro\\') {
                    $forStore[$class] = $file;
                }
            }

            \file_put_contents($fileForCache, "<?php\nreturn " . \var_export($forStore, \true) . ";", \LOCK_EX);
        }

        return $response;
    }
}