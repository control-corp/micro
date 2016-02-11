<?php

chdir(dirname(__DIR__));

putenv('APP_ENV=development');

include 'library/Micro/autoload.php';

if (is_file($composer = 'vendor/autoload.php')) {
    include $composer;
}

MicroLoader::register();

if ((is_file($classes = 'data/classes.php')) === \true) {
    MicroLoader::setFiles(include $classes);
}

if (env('development')) {
    error_reporting(E_ALL | E_NOTICE);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

try {

    ob_start('ob_gzhandler') || ob_start();

    $app = include 'application/start.php';

    $app->run();

} catch (\Exception $e) {

    if (env('development')) {
        echo $e->getMessage();
    }
}