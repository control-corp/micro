<?php

use Micro\Application\Application;

$container = include __DIR__ . '/container.php';

$app = new Application($container);

$app->map('/', 'App\Index@index', 'home');

return $app;