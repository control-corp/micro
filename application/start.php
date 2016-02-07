<?php

use Micro\Application\Application;

$container = include __DIR__ . '/container.php';

$app = new Application($container);

//$app->add(new App\Middleware\Test);

$app->map('/', 'App\Index@index');

return $app;