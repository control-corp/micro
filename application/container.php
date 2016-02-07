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

$container = new Container(true);

$container->set('config', new Config($config));

return $container;