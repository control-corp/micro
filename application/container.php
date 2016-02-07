<?php

use Micro\Container\Container;
use Micro\Application\Config;
use Micro\Application\Utils;

include 'library/Micro/autoload.php';

if (is_file($composer = 'vendor/autoload.php')) {
    include $composer;
}

MicroLoader::register();

if ((is_file($classes = 'data/classes.php')) === \true) {
    MicroLoader::setFiles(include $classes);
}

$config = [];

foreach (glob('{application/config/*.php,application/config/packages/*.php}', GLOB_BRACE) as $file) {
    $config = Utils::merge($config, include $file);
}

if (isset($config['packages'])) {
    MicroLoader::addPath($config['packages']);
}

/* $container = new App\Container\Zend([
    'factories' => [
        App\Factory\Test::class => 'App\Factory\Test',
    ],
    'services' => [
        'config' => new Config($config),
    ],
]);

var_dump($container->get(App\Factory\Test::class));

return $container; */

$container = new Container();

$container->set('config', new Config($config));

return $container;