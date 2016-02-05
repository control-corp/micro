<?php

namespace Micro\Log;

use Micro\Container\ContainerAwareInterface;
use Micro\Container\ContainerAwareTrait;

class File implements LogInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function write($message, $type = 'logs')
    {
        $config = $this->container->get('config');

        if (!$config->get('log.enabled')) {
            return;
        }

        $path = $config->get('log.path');

        if (!$path) {
            return;
        }

        \file_put_contents(\rtrim($path, '/') . '/' . $type . '.txt', \date('d M Y (h:m:i)') . ' - ' . $message . "\n", \FILE_APPEND | \LOCK_EX);
    }
}