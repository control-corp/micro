<?php

namespace Micro\Log;

interface LogInterface
{
    public static function write($message, $type);
}