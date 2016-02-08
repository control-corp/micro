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
    'services' => [
        'config' => new Config($config),
    ],
]);

return $container; */

$container = new Container(isset($config['dependencies']) ? $config['dependencies'] : []);

$container->set('config', new Config($config));

/* $container->set('logger', function () {
    $monolog = new Monolog\Logger('app');
    $handler = new Monolog\Handler\StreamHandler('data/log/your.html', Monolog\Logger::DEBUG);
    $handler->setFormatter(new Monolog\Formatter\HtmlFormatter());
    $monolog->pushHandler($handler);
    return $monolog;
}); */

/* $container->set('exception.handler', function () {
    $whoops = new Whoops\Run();
    $whoops->allowQuit(false);
    $whoops->writeToOutput(false);
    $whoops->pushHandler(new Whoops\Handler\PrettyPageHandler());
    return $whoops;
}); */

return $container;