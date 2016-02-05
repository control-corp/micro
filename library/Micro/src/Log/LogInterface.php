<?php

namespace Micro\Log;

interface LogInterface
{
    /**
     * @param string $message
     * @param string $type
     */
    public function write($message, $type);
}