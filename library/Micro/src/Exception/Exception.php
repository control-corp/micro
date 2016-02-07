<?php

namespace Micro\Exception;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class Exception extends \Exception
{
    /**
     * @var LoggerInterface
     */
    private static $logger;

    public function __construct($message = \null, $code = \null, $previous = \null)
    {
        parent::__construct($message, $code, $previous);

        if (static::$logger !== \null) {

            static::$logger->log(LogLevel::ALERT, $message, array(
                'code'    => $this->getCode(),
                'message' => $this->getMessage(),
                'file'    => $this->getFile(),
                'line'    => $this->getLine(),
                'type'    => 'exceptions'
            ));
        }
    }

    public static function setLogger(LoggerInterface $logger)
    {
        static::$logger = $logger;
    }
}
