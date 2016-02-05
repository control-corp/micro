<?php

namespace Micro\Log;

interface LogInterface
{
    public function write($message, $type);
}