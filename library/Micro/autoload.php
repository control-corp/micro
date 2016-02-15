<?php

include __DIR__ . '/src/helpers.php';

class MicroLoader
{
    protected static $prefixLengths = [];
    protected static $prefixDirs = [];
    protected static $files = [];
    protected static $autoloaded = [];

    public static function register()
    {
        static::addPath('Micro\\', __DIR__ . '/src');

        spl_autoload_register(['MicroLoader', 'autoload'], true, true);
    }

    public static function autoload($class)
    {
        // work around for PHP 5.3.0 - 5.3.2 https://bugs.php.net/50731
        if ('\\' == $class[0]) {
            $class = substr($class, 1);
        }

        if (isset(static::$files[$class])) {
            if (static::$files[$class] === 'NOT EXISTS') {
                return;
            }
            require static::$files[$class];
            return true;
        }

        // PSR-4 lookup
        $logicalPathPsr4 = strtr($class, '\\', DIRECTORY_SEPARATOR) . '.php';

        $first = $class[0];
        if (isset(static::$prefixLengths[$first])) {
            foreach (static::$prefixLengths[$first] as $prefix => $length) {
                if (0 === strpos($class, $prefix)) {
                    foreach (static::$prefixDirs[$prefix] as $dir) {
                        if (is_file($file = $dir . DIRECTORY_SEPARATOR . substr($logicalPathPsr4, $length))) {
                            require $file;
                            static::$autoloaded[$class] = static::$files[$class] = $file;
                            return true;
                        }
                    }
                }
            }
        }

        static::$files[$class] = 'NOT EXISTS';
    }

    public static function addPath($prefix, $path = null)
    {
        if (is_array($prefix)) {
            foreach ($prefix as $k => $v) {
                static::addPath($k, $v);
            }
            return;
        }

        if ($path === null) {
            return;
        }

        $length = strlen($prefix);

        if ('\\' !== $prefix[$length - 1]) {
            $prefix = $prefix . '\\';
        }

        static::$prefixLengths[$prefix[0]][$prefix] = $length;

        if (!isset(static::$prefixDirs[$prefix])) {
            static::$prefixDirs[$prefix] = (array) $path;
        } else {
            static::$prefixDirs[$prefix] = array_merge(static::$prefixDirs[$prefix], (array) $path);
        }
    }

    public static function getFiles()
    {
        return static::$files;
    }

    public static function getAutoloaded()
    {
        return static::$autoloaded;
    }

    public static function setFiles(array $files)
    {
        static::$files = $files;
    }

    public static function addFiles(array $files)
    {
        static::$files = array_merge(static::$files, $files);
    }
}