<?php

namespace Micro\Exception;

use Micro\Log\Log;
use Micro\Log\LogInterface;

class Exception extends \Exception
{
    public function __construct($message, $code = 500, $previous = null)
	{
        parent::__construct($message, $code, $previous);

        static::exceptionHandler($this);
    }

	public static function register()
	{
	    set_exception_handler('Micro\Exception\Exception::exceptionHandler');
	}

	public static function exceptionHandler(\Exception $e)
	{
	    if (($logger = Log::getLogger()) instanceof LogInterface) {
	        $logger->write('(' . (int) $e->getCode() . ') ' . \strip_tags($e->getMessage()) . ' - ' . $_SERVER['REMOTE_ADDR'] . ' - ' . $_SERVER['REQUEST_URI'], 'exceptions');
	    }
	}
}