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
	public static function write($message, $type = 'logs')
	{
	    if (\null === static::$logger) {

    	    if (!config('log.enabled') || (!$handler = config('log.path'))) return;

            file_put_contents(rtrim($handler, '/') . '/' . $type . '.txt', date('d M Y (h:m:i)') . ' - ' . $message . "\n", FILE_APPEND | LOCK_EX);

    	    return;
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
	    self::write('(' . $errno . ') ' . $errstr . ' ' . $errfile . ' ' . $errline, 'errors');

	    return false;
	}

	/**
	 * @param LogInterface $logger
	 */
	public static function setLogger(LogInterface $logger)
	{
        static::$logger = $logger;
	}
}