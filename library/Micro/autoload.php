<?php

include __DIR__ . '/src/helpers.php';

class MicroLoader
{
    protected static $paths;

    protected static $files = [];

    public static function register()
    {
        static::addPath('Micro\\', __DIR__ . '/src');

        spl_autoload_register(array('MicroLoader', 'autoload'), true, true);
    }

    public static function autoload($class)
    {
        if ($class[0] === '\\') {
            $class = ltrim($class, '\\');
        }

        if (isset(static::$files[$class])) {
            include static::$files[$class];
            return \true;
        }

        $parts  = explode('\\', $class);
        $vendor = $parts[0] . '\\';

        if (isset(static::$paths[$vendor])) {

            $file = static::$paths[$vendor] . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($vendor))) . '.php';

            if (is_file($file)) {
                include $file;
                static::$files[$class] = $file;
                return \true;
            }

        }
    }

    public static function addPath($prefix, $path = \null)
    {
        if (is_array($prefix)) {
            foreach ($prefix as $k => $v) {
                static::addPath($k, $v);
            }
            return;
        }

        if ($path === \null) {
            return;
        }

        static::$paths[ rtrim($prefix, '\\') . '\\'] = rtrim($path, '/\\');
    }

    public static function getFiles()
    {
        return static::$files;
    }

    public static function setFiles(array $files)
    {
        static::$files = $files;
    }
}