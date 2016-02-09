<?php

namespace MicroDebug\Handler;

use Micro\Event\Message;

class Performance
{
    public function boot()
    {
        if (!\config('micro_debug.handlers.performance')) {
            return;
        }

        \app('event')->attach('application.end', [$this, 'onApplicationEnd']);
    }

    public function onApplicationEnd(Message $message)
    {
        $files = \MicroLoader::getFiles();
        $forStore = [];

        foreach ($files as $class => $file) {
            if (\substr($class, 0, 6) === 'Micro\\') {
                $forStore[$class] = $file;
            }
        }

        \file_put_contents(config('micro_debug.handlers.performance'), "<?php\nreturn " . \var_export($forStore, \true) . ";", \LOCK_EX);
    }
}