<?php

use Micro\Container\Container;
use Micro\Application\Config;
use Micro\Application\Utils;

$config = [];

foreach (glob('{application/config/*.php,application/config/packages/*.php}', GLOB_BRACE) as $file) {
    $config = Utils::merge($config, include $file);
}

if (isset($config['packages'])) {
    MicroLoader::addPath($config['packages']);
}

$config = new Config($config);

$container = new Container($config->get('dependencies', []));

$container->set('config', $config);

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