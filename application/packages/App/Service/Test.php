<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

class Test
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}