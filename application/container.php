<?php

use Micro\Container\Container;
use Micro\Application\Config;
use Micro\Application\Utils;

$cachedConfigFile = 'data/cache/app_config.php';

$config = [];

if (is_file($cachedConfigFile)) {
    // Try to load the cached config
    $config = include $cachedConfigFile;
} else {
    // Load configuration from autoload path
    foreach (glob('{application/config/*.php,application/config/packages/*.php}', GLOB_BRACE) as $file) {
        $config = Utils::merge($config, include $file);
    }

    // Cache config if enabled
    if (isset($config['config_cache_enabled']) && $config['config_cache_enabled'] === true) {
        file_put_contents($cachedConfigFile, '<?php return ' . var_export($config, true) . ';');
    }
}

$container = new Container(isset($config['dependencies']) ? $config['dependencies'] : []);

$container->set('config', new Config($config));

/* $container->set('logger', function () {
    $monolog = new Monolog\Logger('app');
    $handler = new Monolog\Handler\StreamHandler('data/log/your.html', Monolog\Logger::DEBUG);
    $handler->setFormatter(new Monolog\Formatter\HtmlFormatter());
    $monolog->pushHandler($handler);
    return $monolog;
}); */

$container->set('exception.handler', function () {
    $whoops = new Whoops\Run();
    $whoops->allowQuit(false);
    $whoops->writeToOutput(false);
    $whoops->pushHandler(new Whoops\Handler\PrettyPageHandler());
    return $whoops;
});

return $container;