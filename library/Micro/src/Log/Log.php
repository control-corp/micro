<?php

namespace Micro\Log;

class Log implements LogInterface
{
    /**
     * @var LogInterface
     */
    protected static $logger;

    /**
     * @param string $message
     * @param string $type
     */
	public function write($message, $type = 'logs')
	{
	    if (static::$logger === \null) {
	        throw new \Exception('Logger is not set');
	    }

	    static::$logger->write($message, $type);
	}

	public static function register()
	{
	    set_error_handler(array('Micro\Log\Log', 'errorHandler'));
	}

	/**
	 * @param int $errno
	 * @param string $errstr
	 * @param string $errfile
	 * @param int $errline
	 * @return boolean
	 */
	public static function errorHandler($errno, $errstr, $errfile, $errline)
	{
	    static::$logger->write('(' . $errno . ') ' . $errstr . ' ' . $errfile . ' ' . $errline, 'errors');

	    return false;
	}

	/**
	 * @param LogInterface $logger
	 */
	public static function setLogger(LogInterface $logger)
	{
        static::$logger = $logger;
	}

	/**
	 * @return LogInterface $logger
	 */
	public static function getLogger()
	{
	    return static::$logger;
	}
}