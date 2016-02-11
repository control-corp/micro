<?php

namespace Micro\Log;

use Psr\Log\LoggerInterface;
use Psr\Log\AbstractLogger;

class File extends AbstractLogger implements LoggerInterface
{
    private $enabled;
    private $path;

    public function __construct(array $options = \null)
    {
        if (isset($options['enabled'])) {
            $this->enabled = (bool) $options['enabled'];
        }

        if (isset($options['path'])) {
            $this->path = $options['path'];
        }
    }

    public function log($level, $message, array $context = [])
    {
        if (!$this->enabled || !$this->path) {
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

        $extra = '';

        foreach ($context as $k => $v) {
            if (\is_int($k)) {
                continue;
            }
            $extra .= " - " . $k . " [" . (\is_object($v) || \is_array($v) ? \json_encode($v) : $v) . "]";
        }

        \file_put_contents(\rtrim($this->path, '/') . '/' . $type . '.txt', '[' . \date('d M Y (h:m:i)') . '] "' . $message . '"' . $extra . "\n", \FILE_APPEND | \LOCK_EX);
    }
}