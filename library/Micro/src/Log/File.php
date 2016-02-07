<?php

namespace Micro\Log;

use Micro\Container\ContainerAwareInterface;
use Micro\Container\ContainerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\AbstractLogger;

class File extends AbstractLogger implements LoggerInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function log($level, $message, array $context = array())
    {
        $config = $this->container->get('config');

        if (!$config->get('log.enabled')) {
            return;
        }

        $path = $config->get('log.path');

        if (!$path) {
            return;
        }

        $type = 'logs';

        if (isset($context['type'])) {
            $type = $context['type'];
            unset($context['type']);
        }

        if (isset($context['message'])) {
            unset($context['message']);
        }

        $context['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
        $context['REQUEST_URI'] = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'NO REQUEST URI';

        $extra = ' :: ';

        foreach ($context as $k => $v) {
            $extra .= " - " . $k . " [" . (is_object($v) || is_array($v) ? json_encode($v) : $v) . "]";
        }

        \file_put_contents(\rtrim($path, '/') . '/' . $type . '.txt', '[' . \date('d M Y (h:m:i)') . '] ' . $message . $extra . "\n", \FILE_APPEND | \LOCK_EX);
    }
}