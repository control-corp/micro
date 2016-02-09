<?php

namespace MicroDebug\Handler;

use Micro\Event\Message;

class FirePHP
{
    public function boot()
    {
        if (!config('micro_debug.handlers.fire_php', 0)) {
            return;
        }

        $eventManager = app('event');

        $eventManager->attach('application.start', [$this, 'onApplicationStart']);
        $eventManager->attach('application.end', [$this, 'onApplicationEnd']);
    }

    public function onApplicationStart()
    {
        $db = app('db');

        if ($db) {
            $db->setProfiler(\true);
        }
    }

    public function onApplicationEnd(Message $message)
    {
        $db = app('db');

        if (!$db) {
            return;
        }

        $profiler = $db->getProfiler();

        if ($profiler->getEnabled()) {

            $totalTime    = $profiler->getTotalElapsedSecs();
            $queryCount   = $profiler->getTotalNumQueries();
            $longestTime  = 0;
            $longestQuery = \null;

            $total = sprintf('%.6f', microtime(\true) - $_SERVER['REQUEST_TIME_FLOAT']);

            if ($profiler->getQueryProfiles()) {
                $label = 'Executed ' . $queryCount . ' queries in ' . sprintf('%.6f', $totalTime) . ' seconds. (' . ($total ? round(($totalTime / $total) * 100, 2) : 0) . '%)';
                $table = [];
                $table[] = ['Time', 'Event', 'Parameters'];
                foreach ($profiler->getQueryProfiles() as $k => $query) {
                    if ($query->getElapsedSecs() > $longestTime) {
                        $longestTime  = $query->getElapsedSecs();
                        $longestQuery = $k;
                    }
                }
                foreach ($profiler->getQueryProfiles() as $k => $query) {
                    $table[] = [sprintf('%.6f', $query->getElapsedSecs()) . ($k == $longestQuery ? ' !!!' : ''), $query->getQuery(), ($params = $query->getQueryParams()) ? $params : \null];
                }
                FirePHP\FirePHP::getInstance()->table('DB - ' . $label, $table);
            }
        }
    }
}